<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/qr_helper.php';
requireRole('admin');

$pdo = getDB();
$errors = [];
$old = ['username' => '', 'email' => '', 'nama_lengkap' => '', 'tanggal_lahir' => '', 'jenis_kelamin' => 'L'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['nama_lengkap'] = trim($_POST['nama_lengkap'] ?? '');
    $old['tanggal_lahir'] = trim($_POST['tanggal_lahir'] ?? '');
    $old['jenis_kelamin'] = $_POST['jenis_kelamin'] ?? 'L';
    $password = $_POST['password'] ?? '';
    $emergencyName = trim($_POST['emergency_nama'] ?? '');
    $emergencyPhone = trim($_POST['emergency_telepon'] ?? '');

    if ($old['username'] === '' || strlen($old['username']) < 4) $errors[] = 'Username minimal 4 karakter.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if ($old['nama_lengkap'] === '') $errors[] = 'Nama lengkap wajib diisi.';
    if ($old['tanggal_lahir'] === '' || !strtotime($old['tanggal_lahir'])) $errors[] = 'Tanggal lahir tidak valid.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($emergencyName === '' || $emergencyPhone === '') $errors[] = 'Kontak darurat wajib diisi.';

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT COUNT(*) c FROM users WHERE username = ? OR email = ?');
        $check->execute([$old['username'], $old['email']]);
        if ((int) $check->fetch()['c'] > 0) {
            $errors[] = 'Username atau email sudah terdaftar.';
        } else {
            try {
                $pdo->beginTransaction();
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (role, username, email, password) VALUES ("patient", ?, ?, ?)');
                $stmt->execute([$old['username'], $old['email'], $hash]);
                $userId = (int) $pdo->lastInsertId();

                $patientCode = generatePatientCode($pdo);
                $stmt = $pdo->prepare(
                    'INSERT INTO patients (patient_code, user_id, nama_lengkap, tanggal_lahir, jenis_kelamin) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$patientCode, $userId, $old['nama_lengkap'], $old['tanggal_lahir'], $old['jenis_kelamin']]);
                $patientId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    'INSERT INTO emergency_contacts (patient_id, nama_kontak, hubungan, no_telepon, is_primary) VALUES (?, ?, ?, ?, 1)'
                );
                $stmt->execute([$patientId, $emergencyName, trim($_POST['emergency_hubungan'] ?? ''), $emergencyPhone]);

                createQrForPatient($pdo, $patientId);
                $pdo->commit();

                setFlash('success', 'Pasien baru "' . $old['nama_lengkap'] . '" (' . $patientCode . ') berhasil dibuat.');
                redirect(BASE_URL . '/admin/patient_detail.php?id=' . $patientId);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'Gagal membuat pasien: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Tambah Pasien';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title"><h2>Tambah Akun Pasien Baru</h2></div>
    <p class="text-muted">Gunakan form ini untuk membuatkan akun & kartu QR bagi pasien yang tidak bisa mendaftar sendiri.</p>

    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= clean($err) ?></div><?php endforeach; ?>

    <form method="post">
        <fieldset>
            <legend>Akun</legend>
            <div class="form-row">
                <div class="field"><label>Username</label><input type="text" name="username" value="<?= clean($old['username']) ?>" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" value="<?= clean($old['email']) ?>" required></div>
                <div class="field"><label>Password Awal</label><input type="password" name="password" required></div>
            </div>
        </fieldset>
        <fieldset>
            <legend>Identitas</legend>
            <div class="field"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?= clean($old['nama_lengkap']) ?>" required></div>
            <div class="form-row">
                <div class="field"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= clean($old['tanggal_lahir']) ?>" required></div>
                <div class="field">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin">
                        <option value="L" <?= $old['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $old['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
            </div>
        </fieldset>
        <fieldset>
            <legend>Kontak Darurat</legend>
            <div class="form-row">
                <div class="field"><label>Nama Kontak</label><input type="text" name="emergency_nama" required></div>
                <div class="field"><label>Hubungan</label><input type="text" name="emergency_hubungan"></div>
                <div class="field"><label>No. Telepon</label><input type="tel" name="emergency_telepon" required></div>
            </div>
        </fieldset>
        <button type="submit" class="btn btn-primary">Buat Akun &amp; QR Code</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

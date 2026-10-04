<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/qr_helper.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/' . (currentRole() === 'admin' ? 'admin/dashboard.php' : 'patient/dashboard.php'));
}

$errors = [];
$old = ['username' => '', 'email' => '', 'nama_lengkap' => '', 'tanggal_lahir' => '', 'jenis_kelamin' => 'L'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['username'] = trim($_POST['username'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['nama_lengkap'] = trim($_POST['nama_lengkap'] ?? '');
    $old['tanggal_lahir'] = trim($_POST['tanggal_lahir'] ?? '');
    $old['jenis_kelamin'] = $_POST['jenis_kelamin'] ?? 'L';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $emergencyName = trim($_POST['emergency_nama'] ?? '');
    $emergencyPhone = trim($_POST['emergency_telepon'] ?? '');

    if ($old['username'] === '' || strlen($old['username']) < 4) {
        $errors[] = 'Username minimal 4 karakter.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if ($old['nama_lengkap'] === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if ($old['tanggal_lahir'] === '' || !strtotime($old['tanggal_lahir'])) {
        $errors[] = 'Tanggal lahir wajib diisi dengan format yang benar.';
    }
    if (!in_array($old['jenis_kelamin'], ['L', 'P'], true)) {
        $errors[] = 'Jenis kelamin tidak valid.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }
    if ($emergencyName === '' || $emergencyPhone === '') {
        $errors[] = 'Kontak darurat (nama & telepon) wajib diisi.';
    }

    if (empty($errors)) {
        $pdo = getDB();
        try {
            $check = $pdo->prepare('SELECT COUNT(*) c FROM users WHERE username = ? OR email = ?');
            $check->execute([$old['username'], $old['email']]);
            if ((int) $check->fetch()['c'] > 0) {
                $errors[] = 'Username atau email sudah terdaftar.';
            } else {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (role, username, email, password) VALUES ("patient", ?, ?, ?)');
                $stmt->execute([$old['username'], $old['email'], $hash]);
                $userId = (int) $pdo->lastInsertId();

                $patientCode = generatePatientCode($pdo);
                $stmt = $pdo->prepare(
                    'INSERT INTO patients (patient_code, user_id, nama_lengkap, tanggal_lahir, jenis_kelamin)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$patientCode, $userId, $old['nama_lengkap'], $old['tanggal_lahir'], $old['jenis_kelamin']]);
                $patientId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    'INSERT INTO emergency_contacts (patient_id, nama_kontak, hubungan, no_telepon, is_primary)
                     VALUES (?, ?, ?, ?, 1)'
                );
                $stmt->execute([$patientId, $emergencyName, trim($_POST['emergency_hubungan'] ?? ''), $emergencyPhone]);

                createQrForPatient($pdo, $patientId);

                $pdo->commit();

                $_SESSION['user_id'] = $userId;
                $_SESSION['role'] = 'patient';
                $_SESSION['username'] = $old['username'];

                setFlash('success', 'Akun berhasil dibuat! Selamat datang di QRMed Card, ' . $old['nama_lengkap'] . '.');
                redirect(BASE_URL . '/patient/dashboard.php');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Terjadi kesalahan saat mendaftar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun · QRMed Card</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card wide">
        <div class="auth-logo"><?= icon('shield', 'icon-lg') ?> QRMed Card</div>
        <p class="auth-sub">Buat akun pasien untuk mendapatkan kartu kesehatan darurat dengan QR Code Anda sendiri.</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= clean($err) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
            <fieldset>
                <legend>Akun</legend>
                <div class="form-row">
                    <div class="field">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= clean($old['username']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= clean($old['email']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="field">
                        <label for="password_confirm">Konfirmasi Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Data Identitas</legend>
                <div class="field">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= clean($old['nama_lengkap']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="field">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= clean($old['tanggal_lahir']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin">
                            <option value="L" <?= $old['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $old['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                <p class="field-hint">Foto, golongan darah, dan riwayat kesehatan dapat dilengkapi setelah login di dashboard.</p>
            </fieldset>

            <fieldset>
                <legend>Kontak Darurat</legend>
                <div class="form-row">
                    <div class="field">
                        <label for="emergency_nama">Nama Kontak</label>
                        <input type="text" id="emergency_nama" name="emergency_nama" value="<?= clean($_POST['emergency_nama'] ?? '') ?>" required>
                    </div>
                    <div class="field">
                        <label for="emergency_hubungan">Hubungan</label>
                        <input type="text" id="emergency_hubungan" name="emergency_hubungan" placeholder="mis. Ibu, Suami" value="<?= clean($_POST['emergency_hubungan'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="emergency_telepon">No. Telepon</label>
                        <input type="tel" id="emergency_telepon" name="emergency_telepon" value="<?= clean($_POST['emergency_telepon'] ?? '') ?>" required>
                    </div>
                </div>
            </fieldset>

            <button type="submit" class="btn btn-primary" style="width:100%;">Daftar &amp; Buat QR Code</button>
        </form>

        <p class="auth-switch">Sudah punya akun? <a href="<?= BASE_URL ?>/login.php">Masuk di sini</a></p>
    </div>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('patient');

$pdo = getDB();
$patientId = getPatientIdByUser($pdo, (int) $_SESSION['user_id']);
$patient = getPatientById($pdo, $patientId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $tglLahir = trim($_POST['tanggal_lahir'] ?? '');
    $gender = $_POST['jenis_kelamin'] ?? 'L';
    $golDarah = trim($_POST['golongan_darah'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $catatanKhusus = trim($_POST['catatan_khusus'] ?? '');

    $errors = [];
    if ($nama === '') $errors[] = 'Nama lengkap wajib diisi.';
    if ($tglLahir === '' || !strtotime($tglLahir)) $errors[] = 'Tanggal lahir tidak valid.';
    if (!in_array($gender, ['L', 'P'], true)) $errors[] = 'Jenis kelamin tidak valid.';

    $fotoFile = $patient['foto'];
    if (empty($errors)) {
        try {
            if (!empty($_FILES['foto']['name'])) {
                $fotoFile = uploadPhoto($_FILES['foto'], $patient['foto']);
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE patients SET nama_lengkap = ?, tanggal_lahir = ?, jenis_kelamin = ?, golongan_darah = ?,
             foto = ?, alamat = ?, catatan_khusus = ? WHERE patient_id = ?'
        );
        $stmt->execute([$nama, $tglLahir, $gender, $golDarah ?: null, $fotoFile, $alamat ?: null, $catatanKhusus ?: null, $patientId]);

        setFlash('success', 'Profil berhasil diperbarui.');
        redirect(BASE_URL . '/patient/profile.php');
    }

    $patient = array_merge($patient, [
        'nama_lengkap' => $nama, 'tanggal_lahir' => $tglLahir, 'jenis_kelamin' => $gender,
        'golongan_darah' => $golDarah, 'alamat' => $alamat, 'catatan_khusus' => $catatanKhusus,
    ]);
}

$pageTitle = 'Profil Identitas';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title"><h2>Data Identitas</h2></div>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= clean($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p class="text-muted">Catatan: golongan darah baru akan ditampilkan di halaman darurat setelah <strong>diverifikasi oleh admin</strong>. NIK dan alamat lengkap tidak pernah ditampilkan di halaman darurat.</p>

    <form method="post" enctype="multipart/form-data">
        <div class="form-row">
            <div class="field" style="max-width:180px;">
                <label>Foto Saat Ini</label>
                <?php if (!empty($patient['foto'])): ?>
                    <img src="<?= BASE_URL ?>/assets/img/uploads/photos/<?= clean($patient['foto']) ?>" style="width:100%;border-radius:10px;border:1px solid var(--line);">
                <?php else: ?>
                    <p class="text-muted">Belum ada foto.</p>
                <?php endif; ?>
                <input type="file" name="foto" accept=".jpg,.jpeg,.png" style="margin-top:8px;">
            </div>
            <div class="field" style="flex:2;">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?= clean($patient['nama_lengkap']) ?>" required>

                <div class="form-row" style="margin-top:18px;">
                    <div class="field">
                        <label for="tanggal_lahir">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?= clean($patient['tanggal_lahir']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin">
                            <option value="L" <?= $patient['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $patient['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="golongan_darah">Golongan Darah</label>
                        <select id="golongan_darah" name="golongan_darah">
                            <option value="">— Belum diisi —</option>
                            <?php foreach (['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $g): ?>
                                <option value="<?= $g ?>" <?= $patient['golongan_darah'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($patient['gol_darah_verified']): ?>
                            <p class="field-hint" style="color:var(--ok-text);">✓ Terverifikasi admin</p>
                        <?php else: ?>
                            <p class="field-hint">Menunggu verifikasi admin</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="field">
            <label for="alamat">Alamat (privat, tidak tampil di halaman darurat)</label>
            <textarea id="alamat" name="alamat"><?= clean($patient['alamat'] ?? '') ?></textarea>
        </div>

        <div class="field">
            <label for="catatan_khusus">Kondisi Khusus / Catatan Medis Penting</label>
            <textarea id="catatan_khusus" name="catatan_khusus" placeholder="mis. Pengguna alat pacu jantung, penyandang disabilitas, dsb."><?= clean($patient['catatan_khusus'] ?? '') ?></textarea>
            <p class="field-hint">Catatan ini akan tampil di halaman darurat.</p>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

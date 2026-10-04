<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/qr_helper.php';
requireRole('patient');

$pdo = getDB();
$patientId = getPatientIdByUser($pdo, (int) $_SESSION['user_id']);
$patient = getPatientById($pdo, $patientId);
$qr = getQrByPatient($pdo, $patientId);

$countDisease = $pdo->prepare('SELECT COUNT(*) c FROM disease_history WHERE patient_id = ? AND status = "aktif"');
$countDisease->execute([$patientId]);
$countDisease = (int) $countDisease->fetch()['c'];

$countAllergy = $pdo->prepare('SELECT COUNT(*) c FROM allergies WHERE patient_id = ?');
$countAllergy->execute([$patientId]);
$countAllergy = (int) $countAllergy->fetch()['c'];

$countMed = $pdo->prepare('SELECT COUNT(*) c FROM medications WHERE patient_id = ? AND is_active = 1');
$countMed->execute([$patientId]);
$countMed = (int) $countMed->fetch()['c'];

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title">
        <div>
            <h2>Halo, <?= clean($patient['nama_lengkap']) ?> 👋</h2>
            <p class="text-muted mt-0">ID Pasien Anda: <strong><?= clean($patient['patient_code']) ?></strong></p>
        </div>
        <a href="<?= BASE_URL ?>/patient/card.php" class="btn btn-primary">Lihat Kartu Digital</a>
    </div>

    <div class="grid-3">
        <div class="stat-box">
            <div class="stat-icon"><?= icon('pulse') ?></div>
            <div>
                <div class="num"><?= $countDisease ?></div>
                <div class="label">Penyakit aktif</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon"><?= icon('alert') ?></div>
            <div>
                <div class="num"><?= $countAllergy ?></div>
                <div class="label">Alergi tercatat</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon"><?= icon('pill') ?></div>
            <div>
                <div class="num"><?= $countMed ?></div>
                <div class="label">Obat sedang digunakan</div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-title"><h2>Status QR Code</h2></div>
        <?php if ($qr['status'] === 'active'): ?>
            <p><span class="badge badge-active">Aktif</span></p>
            <p class="text-muted">QR Code Anda aktif dan siap dipindai. Data yang tampil di halaman darurat akan otomatis mengikuti data terbaru yang Anda simpan.</p>
        <?php else: ?>
            <p><span class="badge badge-inactive">Nonaktif</span></p>
            <p class="text-muted">QR Code Anda sedang dinonaktifkan. Hubungi admin jika ini bukan yang Anda inginkan.</p>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/patient/card.php" class="btn btn-outline btn-sm">Kelola Kartu</a>
    </div>

    <div class="panel">
        <div class="panel-title"><h2>Lengkapi Profil</h2></div>
        <p class="text-muted">Pastikan data berikut selalu terbaru agar tim medis mendapat informasi yang akurat saat darurat.</p>
        <div class="list-actions" style="flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/patient/profile.php" class="btn btn-ghost btn-sm">Data Identitas</a>
            <a href="<?= BASE_URL ?>/patient/diseases.php" class="btn btn-ghost btn-sm">Riwayat Penyakit</a>
            <a href="<?= BASE_URL ?>/patient/allergies.php" class="btn btn-ghost btn-sm">Alergi</a>
            <a href="<?= BASE_URL ?>/patient/medications.php" class="btn btn-ghost btn-sm">Obat</a>
            <a href="<?= BASE_URL ?>/patient/contacts.php" class="btn btn-ghost btn-sm">Kontak Darurat</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

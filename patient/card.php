<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/qr_helper.php';
require_once __DIR__ . '/../includes/card_generator.php';
requireRole('patient');

$pdo = getDB();
$patientId = getPatientIdByUser($pdo, (int) $_SESSION['user_id']);
$patient = getPatientById($pdo, $patientId);
$qr = getQrByPatient($pdo, $patientId);

// Selalu render ulang QR agar URL di dalamnya mengikuti BASE_URL terbaru
// (misalnya setelah admin mengganti alamat dari localhost ke IP jaringan).
renderQrImage($qr['unique_token']);

// Selalu re-generate PNG kartu terbaru agar data yang tampil di gambar up to date
$cardFile = generateDigitalCard($patient, $qr);

$pageTitle = 'Kartu Digital';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title">
        <h2>Kartu Kesehatan Digital Anda</h2>
        <span class="badge <?= $qr['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
            QR <?= $qr['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?>
        </span>
    </div>

    <div class="card-preview">
        <img src="<?= BASE_URL ?>/assets/img/uploads/cards/<?= clean($cardFile) ?>?v=<?= time() ?>" alt="Kartu QRMed Card">
        <p style="margin-top:16px;">
            <a href="<?= BASE_URL ?>/assets/img/uploads/cards/<?= clean($cardFile) ?>" download class="btn btn-primary">Unduh Kartu (PNG)</a>
        </p>
    </div>

    <p class="text-muted" style="text-align:center;">
        Simpan kartu ini di galeri HP Anda, atau cetak untuk disimpan di dompet.
        Jika data kesehatan Anda berubah, cukup perbarui di dashboard — QR Code pada kartu ini <strong>tidak perlu diganti</strong>.
    </p>
</div>

<div class="panel">
    <div class="panel-title"><h2>Tautan Halaman Darurat</h2></div>
    <p class="text-muted">Ini adalah tautan yang akan terbuka saat QR Code di kartu Anda dipindai:</p>
    <p style="word-break:break-all;"><a href="<?= emergencyUrl($qr['unique_token']) ?>" target="_blank"><?= emergencyUrl($qr['unique_token']) ?></a></p>
    <?php if ($qr['status'] !== 'active'): ?>
        <div class="alert alert-error">QR Code Anda sedang nonaktif. Halaman darurat tidak akan menampilkan data hingga admin mengaktifkannya kembali.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

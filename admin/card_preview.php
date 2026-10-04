<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/qr_helper.php';
require_once __DIR__ . '/../includes/card_generator.php';
requireRole('admin');

$patientId = (int) ($_GET['id'] ?? 0);
$pdo = getDB();
$patient = getPatientById($pdo, $patientId);

if (!$patient) {
    setFlash('error', 'Pasien tidak ditemukan.');
    redirect(BASE_URL . '/admin/patients.php');
}

$qr = getQrByPatient($pdo, $patientId);
renderQrImage($qr['unique_token']);
$cardFile = generateDigitalCard($patient, $qr);

$pageTitle = 'Kartu Digital — ' . $patient['nama_lengkap'];
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title"><h2>Kartu Digital: <?= clean($patient['nama_lengkap']) ?></h2></div>
    <div class="card-preview">
        <img src="<?= BASE_URL ?>/assets/img/uploads/cards/<?= clean($cardFile) ?>?v=<?= time() ?>" alt="Kartu QRMed Card">
        <p style="margin-top:16px;">
            <a href="<?= BASE_URL ?>/assets/img/uploads/cards/<?= clean($cardFile) ?>" download class="btn btn-primary">Unduh Kartu (PNG)</a>
            <a href="<?= BASE_URL ?>/admin/patient_detail.php?id=<?= $patientId ?>" class="btn btn-outline">Kembali</a>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

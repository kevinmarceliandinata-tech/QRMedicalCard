<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$pdo = getDB();

$totalPatients = (int) $pdo->query('SELECT COUNT(*) c FROM patients')->fetch()['c'];
$activeQr = (int) $pdo->query('SELECT COUNT(*) c FROM qr_codes WHERE status = "active"')->fetch()['c'];
$inactiveQr = (int) $pdo->query('SELECT COUNT(*) c FROM qr_codes WHERE status = "inactive"')->fetch()['c'];
$pendingVerif = (int) $pdo->query('SELECT COUNT(*) c FROM patients WHERE golongan_darah IS NOT NULL AND golongan_darah <> "" AND gol_darah_verified = 0')->fetch()['c'];

$recentPatients = $pdo->query('SELECT * FROM patients ORDER BY created_at DESC LIMIT 5')->fetchAll();

$pageTitle = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-title">
        <h2>Panel Admin</h2>
        <a href="<?= BASE_URL ?>/admin/patient_add.php" class="btn btn-primary">+ Tambah Pasien</a>
    </div>
    <div class="grid-3">
        <div class="stat-box">
            <div class="stat-icon"><?= icon('patients') ?></div>
            <div>
                <div class="num"><?= $totalPatients ?></div>
                <div class="label">Total pasien terdaftar</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon"><?= icon('qr') ?></div>
            <div>
                <div class="num"><?= $activeQr ?></div>
                <div class="label">QR Code aktif</div>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon"><?= icon('alert') ?></div>
            <div>
                <div class="num"><?= $inactiveQr ?></div>
                <div class="label">QR Code nonaktif</div>
            </div>
        </div>
    </div>
    <?php if ($pendingVerif > 0): ?>
        <div class="alert alert-info" style="margin-top:20px;">
            Ada <strong><?= $pendingVerif ?></strong> pasien dengan golongan darah menunggu verifikasi.
            <a href="<?= BASE_URL ?>/admin/patients.php">Lihat daftar pasien →</a>
        </div>
    <?php endif; ?>
</div>

<div class="panel">
    <div class="panel-title">
        <h2>Pasien Terbaru</h2>
        <a href="<?= BASE_URL ?>/admin/patients.php" class="btn btn-outline btn-sm">Lihat Semua</a>
    </div>
    <?php if (empty($recentPatients)): ?>
        <div class="empty-state">Belum ada pasien terdaftar.</div>
    <?php else: ?>
        <table>
            <thead><tr><th>ID</th><th>Nama</th><th>Terdaftar</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentPatients as $p): ?>
                <tr>
                    <td><?= clean($p['patient_code']) ?></td>
                    <td><?= clean($p['nama_lengkap']) ?></td>
                    <td><?= formatTanggalIndo($p['created_at']) ?></td>
                    <td><a href="<?= BASE_URL ?>/admin/patient_detail.php?id=<?= $p['patient_id'] ?>" class="btn btn-ghost btn-sm">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

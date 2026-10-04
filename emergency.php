<?php
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';
$pdo = getDB();

$patient = null;
$qr = null;
$diseases = [];
$allergies = [];
$medications = [];
$contacts = [];

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM qr_codes WHERE unique_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $qr = $stmt->fetch();

    if ($qr) {
        $patient = getPatientById($pdo, (int) $qr['patient_id']);

        if ($patient && $qr['status'] === 'active') {
            // Catat akses untuk audit keamanan (opsional tapi berguna)
            try {
                $log = $pdo->prepare('INSERT INTO access_logs (patient_id, ip_address, user_agent) VALUES (?, ?, ?)');
                $log->execute([
                    $patient['patient_id'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250),
                ]);
            } catch (Throwable $e) {
                // Jangan hentikan tampilan hanya karena log gagal
            }

            $stmt = $pdo->prepare('SELECT * FROM disease_history WHERE patient_id = ? ORDER BY status = "aktif" DESC, tanggal_diagnosis DESC');
            $stmt->execute([$patient['patient_id']]);
            $diseases = $stmt->fetchAll();

            $stmt = $pdo->prepare('SELECT * FROM allergies WHERE patient_id = ? ORDER BY created_at DESC');
            $stmt->execute([$patient['patient_id']]);
            $allergies = $stmt->fetchAll();

            $stmt = $pdo->prepare('SELECT * FROM medications WHERE patient_id = ? AND is_active = 1 ORDER BY created_at DESC');
            $stmt->execute([$patient['patient_id']]);
            $medications = $stmt->fetchAll();

            $stmt = $pdo->prepare('SELECT * FROM emergency_contacts WHERE patient_id = ? ORDER BY is_primary DESC, contact_id ASC');
            $stmt->execute([$patient['patient_id']]);
            $contacts = $stmt->fetchAll();
        }
    }
}

$isValid = $patient && $qr && $qr['status'] === 'active';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isValid ? 'Emergency Health Profile — ' . clean($patient['nama_lengkap']) : 'QR Tidak Valid' ?> · QRMed Card</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="emergency-wrap">
<div class="emergency-card">
    <div class="emergency-head">
        <div class="tag"><?= icon('shield') ?> QRMED CARD</div>
        <h1>Emergency Health Profile</h1>
    </div>

    <div class="emergency-body">
        <?php if (!$isValid): ?>
            <div class="emergency-inactive">
                <?= icon('alert', 'icon-lg') ?>
                <h2>QR Code tidak valid atau tidak aktif</h2>
                <p class="text-muted">
                    <?= $qr && $qr['status'] !== 'active'
                        ? 'Kartu ini telah dinonaktifkan oleh pemilik/admin, kemungkinan karena kartu hilang.'
                        : 'Tautan ini tidak terdaftar dalam sistem QRMed Card.' ?>
                </p>
            </div>
        <?php else: ?>

            <div class="emergency-profile-top">
                <?php if (!empty($patient['foto'])): ?>
                    <img class="emergency-photo" src="<?= BASE_URL ?>/assets/img/uploads/photos/<?= clean($patient['foto']) ?>" alt="Foto pasien">
                <?php else: ?>
                    <div class="emergency-photo"></div>
                <?php endif; ?>
                <div>
                    <h2><?= clean($patient['nama_lengkap']) ?></h2>
                    <div class="sub">
                        <?= formatTanggalIndo($patient['tanggal_lahir']) ?>
                        (<?= calculateAge($patient['tanggal_lahir']) ?> tahun) ·
                        <?= $patient['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                    </div>
                    <?php if (!empty($patient['golongan_darah']) && $patient['gol_darah_verified']): ?>
                        <div style="margin-top:8px;">
                            <span class="blood-badge">Gol. Darah <?= clean($patient['golongan_darah']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($patient['catatan_khusus'])): ?>
                <div class="section-block">
                    <h3>Kondisi Khusus</h3>
                    <p style="margin:0;"><?= nl2br(clean($patient['catatan_khusus'])) ?></p>
                </div>
            <?php endif; ?>

            <div class="section-block">
                <h3>Riwayat &amp; Kondisi Penyakit</h3>
                <?php if (empty($diseases)): ?>
                    <p class="text-muted" style="margin:0;">Tidak ada data riwayat penyakit.</p>
                <?php else: ?>
                    <?php foreach ($diseases as $d): ?>
                        <span class="tag-pill <?= $d['status'] === 'aktif' ? '' : 'mild' ?>">
                            <?= clean($d['nama_penyakit']) ?><?= $d['status'] === 'aktif' ? '' : ' (tidak aktif)' ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section-block">
                <h3>Alergi</h3>
                <?php if (empty($allergies)): ?>
                    <p class="text-muted" style="margin:0;">Tidak ada alergi yang tercatat.</p>
                <?php else: ?>
                    <?php foreach ($allergies as $a): ?>
                        <span class="tag-pill"><?= clean($a['jenis_alergi']) ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section-block">
                <h3>Obat yang Sedang Digunakan</h3>
                <?php if (empty($medications)): ?>
                    <p class="text-muted" style="margin:0;">Tidak ada obat aktif yang tercatat.</p>
                <?php else: ?>
                    <?php foreach ($medications as $m): ?>
                        <span class="tag-pill mild">
                            <?= clean($m['nama_obat']) ?><?= $m['dosis'] ? ' · ' . clean($m['dosis']) : '' ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section-block">
                <h3>Kontak Darurat</h3>
                <?php if (empty($contacts)): ?>
                    <p class="text-muted" style="margin:0;">Belum ada kontak darurat.</p>
                <?php else: ?>
                    <?php foreach ($contacts as $c): ?>
                        <div class="emergency-contact-box">
                            <div class="who">
                                <?= clean($c['nama_kontak']) ?>
                                <?php if ($c['hubungan']): ?><span class="rel"> · <?= clean($c['hubungan']) ?></span><?php endif; ?>
                            </div>
                            <a class="call-btn" href="tel:<?= clean($c['no_telepon']) ?>"><?= icon('phone') ?> Hubungi</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p class="text-muted" style="font-size:.78rem;text-align:center;margin-top:26px;">
                Halaman ini menampilkan informasi terbatas untuk keperluan darurat medis.
                ID Pasien: <?= clean($patient['patient_code']) ?>
            </p>
        <?php endif; ?>
    </div>
</div>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/' . (currentRole() === 'admin' ? 'admin/dashboard.php' : 'patient/dashboard.php'));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QRMed Card — Kartu Informasi Kesehatan Darurat Berbasis QR Code</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<section class="landing-hero">
    <div class="landing-inner">
        <div>
            <span class="landing-eyebrow"><?= icon('shield') ?> Kartu kesehatan darurat digital</span>
            <h1>Satu QR Code yang tahu persis apa yang perlu diketahui tim medis, tepat saat dibutuhkan.</h1>
            <p class="lead">QRMed Card menyimpan riwayat penyakit, alergi, obat, dan kontak darurat Anda di balik satu QR Code yang tidak pernah berubah — walau isinya terus Anda perbarui.</p>
            <div class="landing-cta">
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-danger">Buat Kartu Saya</a>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Masuk ke Akun</a>
            </div>
        </div>
        <div class="card-mock">
            <div class="mock-top">
                <strong>QRMed Card</strong>
                <span style="font-size:.72rem;color:#8A9A94;">Emergency</span>
            </div>
            <div class="mock-photo"></div>
            <div class="mock-line" style="width:78%"></div>
            <div class="mock-line short"></div>
            <div class="mock-line" style="width:65%"></div>
            <div class="mock-qr"><?= icon('qr') ?></div>
        </div>
    </div>
</section>

<section class="how-section">
    <div class="how-inner">
        <div class="how-heading">
            <h2>Dari daftar sampai siap dipindai, empat langkah singkat.</h2>
            <p class="text-muted">Tidak perlu aplikasi tambahan — cukup satu kartu yang selalu terhubung ke data terbaru Anda.</p>
        </div>
        <div class="how-steps">
            <div class="how-step">
                <div class="how-step-badge">1</div>
                <h4>Daftar &amp; isi data</h4>
                <p class="text-muted">Buat akun lalu lengkapi identitas dan informasi kesehatan dasar Anda.</p>
            </div>
            <div class="how-step">
                <div class="how-step-badge">2</div>
                <h4>QR Code unik dibuat</h4>
                <p class="text-muted">Sistem membuat Patient ID dan QR Code dinamis yang tertaut ke profil Anda.</p>
            </div>
            <div class="how-step">
                <div class="how-step-badge">3</div>
                <h4>Simpan atau cetak</h4>
                <p class="text-muted">Simpan kartu digital di ponsel, atau cetak untuk dompet dan tas Anda.</p>
            </div>
            <div class="how-step">
                <div class="how-step-badge">4</div>
                <h4>Perbarui kapan saja</h4>
                <p class="text-muted">Tambahkan penyakit atau obat baru — QR Code lama tetap berlaku.</p>
            </div>
        </div>
    </div>
</section>

<footer class="app-footer">
    <p>&copy; <?= date('Y') ?> QRMed Card &mdash; Kartu Informasi Kesehatan Darurat Berbasis QR Code.</p>
    <p style="margin-top:4px;"><a href="<?= BASE_URL ?>/admin/login.php" style="color:inherit;text-decoration:underline;">Masuk sebagai Admin</a></p>
</footer>

</body>
</html>

<?php
/**
 * Header untuk halaman internal (patient & admin) — layout sidebar.
 * Wajib require functions.php sebelum include file ini.
 * Variabel opsional: $pageTitle
 */
$pageTitle = $pageTitle ?? 'QRMed Card';
$role = currentRole();
$currentFile = basename($_SERVER['PHP_SELF']);

function navActive(string $file, string $current): string
{
    return $file === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle) ?> · QRMed Card</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="sidebar-brand" href="<?= BASE_URL ?>/<?= $role === 'admin' ? 'admin/dashboard.php' : 'patient/dashboard.php' ?>">
            <?= icon('shield', 'icon-lg') ?> <span>QRMed Card</span>
        </a>

        <?php if ($role === 'patient'): ?>
            <div class="sidebar-label">Menu Pasien</div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/patient/dashboard.php" class="<?= navActive('dashboard.php', $currentFile) ?>"><?= icon('dashboard') ?><span class="nav-text">Dashboard</span></a>
                <a href="<?= BASE_URL ?>/patient/profile.php" class="<?= navActive('profile.php', $currentFile) ?>"><?= icon('user') ?><span class="nav-text">Profil</span></a>
                <a href="<?= BASE_URL ?>/patient/diseases.php" class="<?= navActive('diseases.php', $currentFile) ?>"><?= icon('pulse') ?><span class="nav-text">Riwayat Penyakit</span></a>
                <a href="<?= BASE_URL ?>/patient/allergies.php" class="<?= navActive('allergies.php', $currentFile) ?>"><?= icon('alert') ?><span class="nav-text">Alergi</span></a>
                <a href="<?= BASE_URL ?>/patient/medications.php" class="<?= navActive('medications.php', $currentFile) ?>"><?= icon('pill') ?><span class="nav-text">Obat</span></a>
                <a href="<?= BASE_URL ?>/patient/contacts.php" class="<?= navActive('contacts.php', $currentFile) ?>"><?= icon('contact') ?><span class="nav-text">Kontak Darurat</span></a>
                <a href="<?= BASE_URL ?>/patient/card.php" class="<?= navActive('card.php', $currentFile) ?>"><?= icon('card') ?><span class="nav-text">Kartu Digital</span></a>
            </nav>
        <?php elseif ($role === 'admin'): ?>
            <div class="sidebar-label">Menu Admin</div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= navActive('dashboard.php', $currentFile) ?>"><?= icon('dashboard') ?><span class="nav-text">Dashboard</span></a>
                <a href="<?= BASE_URL ?>/admin/patients.php" class="<?= navActive('patients.php', $currentFile) ?>"><?= icon('patients') ?><span class="nav-text">Data Pasien</span></a>
                <a href="<?= BASE_URL ?>/admin/patient_add.php" class="<?= navActive('patient_add.php', $currentFile) ?>"><?= icon('add') ?><span class="nav-text">Tambah Pasien</span></a>
            </nav>
        <?php endif; ?>

        <div class="sidebar-spacer"></div>

        <div class="sidebar-user">
            <div class="who">Masuk sebagai <strong><?= clean($_SESSION['username'] ?? '') ?></strong></div>
            <nav class="sidebar-nav">
                <a href="<?= BASE_URL ?>/logout.php" class="logout"><?= icon('logout') ?><span class="nav-text">Keluar</span></a>
            </nav>
        </div>
    </aside>

    <div class="main-area">
        <main class="content">
            <?php $flash = getFlash(); ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= clean($flash['type']) ?>"><?= clean($flash['message']) ?></div>
            <?php endif; ?>

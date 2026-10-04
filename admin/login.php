<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/' . (currentRole() === 'admin' ? 'admin/dashboard.php' : 'patient/dashboard.php'));
}

$error = null;
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($oldUsername === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND role = "admin" LIMIT 1');
        $stmt->execute([$oldUsername, $oldUsername]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Username/email atau password salah.';
        } elseif ($user['status'] !== 'active') {
            $error = 'Akun admin ini tidak aktif.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role'] = 'admin';
            $_SESSION['username'] = $user['username'];

            redirect(BASE_URL . '/admin/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk Admin · QRMed Card</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo"><?= icon('shield', 'icon-lg') ?> QRMed Card</div>
        <p class="auth-sub">Panel Admin &mdash; kelola data pasien, verifikasi golongan darah, dan status QR Code.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= clean($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="field">
                <label for="username">Username Admin</label>
                <input type="text" id="username" name="username" value="<?= clean($oldUsername) ?>" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Masuk sebagai Admin</button>
        </form>

        <p class="auth-switch">Anda pasien? <a href="<?= BASE_URL ?>/login.php">Masuk di sini</a></p>
    </div>
</div>
</body>
</html>

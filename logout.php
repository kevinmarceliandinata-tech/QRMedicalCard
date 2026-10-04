<?php
require_once __DIR__ . '/includes/functions.php';

$wasAdmin = currentRole() === 'admin';

$_SESSION = [];
session_destroy();

redirect(BASE_URL . '/' . ($wasAdmin ? 'admin/login.php' : 'login.php'));

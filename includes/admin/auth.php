<?php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
    header('Location: /admin.php', true, 303);
    exit;
}

$loginError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['admin_action'] ?? '') === 'login') {
    $submittedUser = trim($_POST['username'] ?? '');
    $submittedPass = trim($_POST['password'] ?? '');

    if ($submittedUser === ADMIN_USER && $submittedPass === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: /admin.php', true, 303);
        exit;
    }

    http_response_code(401);
    $loginError = 'Invalid administrator username or password.';
}

if (empty($_SESSION['admin_logged_in'])) {
    require __DIR__ . '/../../views/admin/login.php';
    exit;
}

if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_csrf_token'];
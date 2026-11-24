<?php
/**
 * Session-based administrator authentication and CSRF token helpers.
 */

function ensureSessionStarted(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? null) == 443);
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $isHttps,
            ]);
        }
        session_start();
    }
}

function isAdminLoggedIn(): bool {
    ensureSessionStarted();
    return !empty($_SESSION['admin_logged_in']);
}

function getAdminUsername(): string {
    ensureSessionStarted();
    return (string)($_SESSION['admin_username'] ?? (getenv('ADMIN_USER') ?: 'admin'));
}

function destroyAdminSession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function getCsrfToken(): string {
    ensureSessionStarted();
    if (!isset($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

function verifyCsrfToken(string $submittedToken): bool {
    return hash_equals(getCsrfToken(), $submittedToken);
}

/**
 * Protects HTML pages (index.php, history.php, admin.php, add_service.php).
 * Handles logout, login POSTs, and shows the login view if not signed in.
 */
function requireAdminLogin(): void {
    ensureSessionStarted();

    $adminUser = getenv('ADMIN_USER') ?: 'admin';
    $adminPass = getenv('ADMIN_PASS') !== false ? getenv('ADMIN_PASS') : 'admin123';

    $isPostLogout = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
        && ($_POST['admin_action'] ?? '') === 'logout'
        && verifyCsrfToken((string)($_POST['csrf_token'] ?? ''));

    if ($isPostLogout || isset($_GET['logout'])) {
        destroyAdminSession();
        header('Location: /admin.php', true, 303);
        exit;
    }

    $loginError = null;
    $submittedUsername = '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['admin_action'] ?? '') === 'login') {
        $submittedUser = trim((string)($_POST['username'] ?? ''));
        $submittedPass = (string)($_POST['password'] ?? '');
        $submittedUsername = $submittedUser;

        if (hash_equals($adminUser, $submittedUser) && hash_equals($adminPass, $submittedPass)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $adminUser;

            $redirectTo = $_SESSION['redirect_after_login'] ?? '/index.php';
            unset($_SESSION['redirect_after_login']);
            if (!is_string($redirectTo) || !str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
                $redirectTo = '/index.php';
            }

            header('Location: ' . $redirectTo, true, 303);
            exit;
        }

        http_response_code(401);
        $loginError = 'Invalid administrator username or password.';
    }

    if (!isAdminLoggedIn()) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/index.php';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !str_contains($requestUri, 'logout=')) {
            $_SESSION['redirect_after_login'] = $requestUri;
        }
        require __DIR__ . '/../views/admin/login.php';
        exit;
    }
}

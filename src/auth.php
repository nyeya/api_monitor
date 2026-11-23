<?php
/**
 * Session-based administrator authentication and CSRF token helpers.
 */

function ensureSessionStarted(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isAdminLoggedIn(): bool {
    ensureSessionStarted();
    return !empty($_SESSION['admin_logged_in']);
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

    if (isset($_GET['logout'])) {
        $_SESSION = [];
        session_destroy();
        header('Location: /admin.php', true, 303);
        exit;
    }

    $loginError = null;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['admin_action'] ?? '') === 'login') {
        $submittedUser = trim((string)($_POST['username'] ?? ''));
        $submittedPass = trim((string)($_POST['password'] ?? ''));

        if (hash_equals($adminUser, $submittedUser) && hash_equals($adminPass, $submittedPass)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;

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

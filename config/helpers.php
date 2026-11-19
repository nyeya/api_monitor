<?php
/**
 * API Monitor - Shared Helper Functions & Utilities
 * Centralizes common functions used across web pages and API scripts.
 */

/**
 * Escapes dynamic string output for safe HTML rendering (prevents XSS).
 */
function escapeHtml($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Validates a positive integer ID (e.g. from $_GET or $_POST).
 * Returns the integer ID or false if invalid.
 */
function validateId($value): int|false {
    if (!is_scalar($value)) {
        return false;
    }
    return filter_var((string)$value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
}

/**
 * Retrieves and trims a POST string value safely.
 */
function postString(string $key): string {
    $value = $_POST[$key] ?? '';
    return is_scalar($value) ? trim((string)$value) : '';
}

/**
 * Counts UTF-8 string character length safely.
 */
function stringLength(string $value): int {
    $length = preg_match_all('/./us', $value, $matches);
    return $length === false ? PHP_INT_MAX : $length;
}
/**
 * Returns the CSS badge class for a given operational status.
 */
function getStatusBadgeClass(string $status): string {
    return match ($status) {
        'ONLINE'  => 'badge-green',
        'SLOW'    => 'badge-yellow',
        'OFFLINE' => 'badge-red',
        default   => 'badge-grey',
    };
}

/**
 * Returns the emoji status indicator dot for a given operational status.
 */
function getStatusDot(string $status): string {
    return match ($status) {
        'ONLINE'  => '🟢',
        'SLOW'    => '🟡',
        'OFFLINE' => '🔴',
        default   => '⚪',
    };
}
/**
 * Validates URLs and prevents Server-Side Request Forgery (SSRF).
 * Blocks private IP ranges, loopback, and metadata endpoints.
 */
function isSafeUrl(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $parsed = parse_url($url);
    if (!is_array($parsed) || !in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true)) {
        return false;
    }

     = strtolower($parsed['host'] ?? '');

    // Allow local mock testing API specifically
    if ($host === 'localhost' || $host === '127.0.0.1') {
        return str_starts_with($parsed['path'] ?? '', '/mock_api.php');
    }
    $ip = gethostbyname($host);
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}
<?php
function escapeHtml($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function validateId($value): int|false {
    if (!is_scalar($value)) {
        return false;
    }
    return filter_var((string)$value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
}

function postString(string $key): string {
    $value = $_POST[$key] ?? '';
    return is_scalar($value) ? trim((string)$value) : '';
}

function stringLength(string $value): int {
    $length = preg_match_all('/./us', $value, $matches);
    return $length === false ? PHP_INT_MAX : $length;
}

function isSafeUrl(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $parsed = parse_url($url);
    if (!is_array($parsed) || !in_array(strtolower($parsed['scheme'] ?? ''), ['http', 'https'], true)) {
        return false;
    }

    $host = strtolower($parsed['host'] ?? '');

    if ($host === 'localhost' || $host === '127.0.0.1') {
        return str_starts_with($parsed['path'] ?? '', '/mock_api.php');
    }
    // return true;
    $ip = gethostbyname($host);
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

function getStatusBadgeClass(string $status): string {
    return match ($status) {
        'ONLINE'  => 'badge-green',
        'SLOW'    => 'badge-yellow',
        'OFFLINE' => 'badge-red',
        default   => 'badge-grey',
    };
}

function getStatusDot(string $status): string {
    return match ($status) {
        'ONLINE'  => '🟢',
        'SLOW'    => '🟡',
        'OFFLINE' => '🔴',
        default   => '⚪',
    };
}

<?php
/**
 * Security helpers: Approved external monitoring allowlist & SSRF prevention.
 */

// Only these external hosts are permitted for monitoring.
const APPROVED_HOSTS = [
    'jsonplaceholder.typicode.com',
    'api.open-meteo.com',
    'api.github.com',
    'httpbin.org',
    'dummyjson.com',
];

/**
 * Checks whether a destination URL is safe to monitor:
 * 1. Must be a valid http:// or https:// URL.
 * 2. localhost / 127.0.0.1 is allowed ONLY for /mock_api.php (local demo testing).
 * 3. External hosts must appear in APPROVED_HOSTS.
 * 4. Every resolved IPv4 address must be public (blocks private & reserved ranges).
 */
function isSafeUrl(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    $parsed = parse_url($url);
    $scheme = strtolower($parsed['scheme'] ?? '');
    if (!is_array($parsed) || !in_array($scheme, ['http', 'https'], true)) {
        return false;
    }

    if (isset($parsed['user']) || isset($parsed['pass'])) {
        return false;
    }

    $host = strtolower($parsed['host'] ?? '');
    if ($host === '') {
        return false;
    }

    if ($host === 'localhost' || $host === '127.0.0.1') {
        $path = $parsed['path'] ?? '';
        return $path === '/mock_api.php' || str_starts_with($path, '/mock_api.php/');
    }

    if (!in_array($host, APPROVED_HOSTS, true)) {
        return false;
    }

    $ips = gethostbynamel($host);
    if ($ips === false || empty($ips)) {
        return false;
    }

    foreach ($ips as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
    }

    return true;
}

/**
 * Validates and normalizes service form fields for Add / Edit forms.
 * Returns [$cleanData, $errors].
 */
function validateServiceInput(array $input): array {
    $errors = [];

    if ($input['name'] === '' || stringLength($input['name']) > 150) {
        $errors[] = 'Name is required and must be 150 characters or fewer.';
    }
    if (stringLength($input['description']) > 2000) {
        $errors[] = 'Description must be 2000 characters or fewer.';
    }
    if (!isSafeUrl($input['url'])) {
        $errors[] = 'Enter an approved public HTTP/HTTPS URL (private networks and unapproved hosts are blocked).';
    }
    if (!in_array($input['http_method'], ['GET', 'HEAD'], true)) {
        $errors[] = 'Choose GET or HEAD as the HTTP method.';
    }

    $ranges = [
        'expected_http_code'     => [100, 599, 'Expected HTTP code must be between 100 and 599.'],
        'timeout_seconds'        => [1, 120, 'Timeout must be between 1 and 120 seconds.'],
        'warning_threshold_ms'   => [1, 120000, 'Warning threshold must be between 1 and 120000 ms.'],
        'check_interval_seconds' => [5, 86400, 'Check interval must be between 5 and 86400 seconds.'],
    ];

    $numbers = [];
    foreach ($ranges as $field => [$min, $max, $errorMsg]) {
        $value = filter_var($input[$field], FILTER_VALIDATE_INT);
        if ($value === false || $value < $min || $value > $max) {
            $errors[] = $errorMsg;
            continue;
        }
        $numbers[$field] = $value;
    }

    $clean = [
        'name'                   => $input['name'],
        'description'            => $input['description'],
        'url'                    => $input['url'],
        'http_method'            => $input['http_method'],
        'expected_http_code'     => $numbers['expected_http_code'] ?? 200,
        'timeout_seconds'        => $numbers['timeout_seconds'] ?? 5,
        'warning_threshold_ms'   => $numbers['warning_threshold_ms'] ?? 1000,
        'check_interval_seconds' => $numbers['check_interval_seconds'] ?? 60,
        'enabled'                => (bool)$input['enabled'],
    ];

    return [$clean, $errors];
}

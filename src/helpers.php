<?php
/**
 * Small helpers for HTML escaping, form input reading, ID validation, and status badges.
 */

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
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function defaultServiceFormValues(): array {
    return [
        'id'                     => '',
        'name'                   => '',
        'description'            => '',
        'url'                    => '',
        'http_method'            => 'GET',
        'expected_http_code'     => '200',
        'timeout_seconds'        => '5',
        'warning_threshold_ms'   => '1000',
        'check_interval_seconds' => '60',
        'enabled'                => true,
    ];
}

function readServiceFormPost(string $id = ''): array {
    return [
        'id'                     => $id,
        'name'                   => postString('name'),
        'description'            => postString('description'),
        'url'                    => postString('url'),
        'http_method'            => postString('http_method'),
        'expected_http_code'     => postString('expected_http_code'),
        'timeout_seconds'        => postString('timeout_seconds'),
        'warning_threshold_ms'   => postString('warning_threshold_ms'),
        'check_interval_seconds' => postString('check_interval_seconds'),
        'enabled'                => postString('enabled') === '1',
    ];
}

function getStatusBadgeClass(string $status): string {
    return match ($status) {
        'ONLINE'       => 'badge-green',
        'SLOW'         => 'badge-yellow',
        'RATE_LIMITED' => 'badge-yellow',
        'OFFLINE'      => 'badge-red',
        default        => 'badge-grey',
    };
}

function getStatusDot(string $status): string {
    return match ($status) {
        'ONLINE'       => '🟢',
        'SLOW'         => '🟡',
        'RATE_LIMITED' => '🟠',
        'OFFLINE'      => '🔴',
        default        => '⚪',
    };
}

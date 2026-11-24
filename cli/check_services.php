<?php
/**
 * API Monitor - Background Monitoring Engine
 *
 * Run via Linux cron:
 *   * * * * * /usr/bin/php /path/to/project/cli/check_services.php
 * Or via cli/worker.php on Windows / local development.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/service_repository.php';

$lockFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'api_monitor.lock';
$lockFile = fopen($lockFilePath, 'c');

if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
    echo '[' . date('Y-m-d H:i:s') . "] Another check process is currently active. Exiting.\n";
    exit(0);
}

try {
    $pdo = getDbConnection();
    $services = getDueServices($pdo);

    if (empty($services)) {
        echo '[' . date('Y-m-d H:i:s') . "] No services due for check.\n";
    } else {
        echo '[' . date('Y-m-d H:i:s') . '] Checking ' . count($services) . " service(s)...\n";
        foreach ($services as $service) {
            checkService($pdo, $service);
        }
        echo '[' . date('Y-m-d H:i:s') . "] All due checks completed.\n";
    }
} catch (Exception $e) {
    echo '[' . date('Y-m-d H:i:s') . '] Error during monitoring run: ' . $e->getMessage() . "\n";
} finally {
    if (is_resource($lockFile)) {
        flock($lockFile, LOCK_UN);
        fclose($lockFile);
    }
}

function checkService(PDO $pdo, array $service): void {
    if (!isSafeUrl($service['url'])) {
        saveCheckResult($pdo, (int)$service['id'], null, 0, 'OFFLINE', 'Blocked: destination URL is not on the approved allowlist');
        echo "  [BLOCKED]      {$service['name']} -> Destination URL rejected by security policy\n";
        return;
    }

    $result = performHttpCheck($service);
    [$status, $errorMessage] = evaluateResponse($service, $result);

    saveCheckResult(
        $pdo,
        (int)$service['id'],
        $result['http_code'] ?: null,
        $result['response_time_ms'],
        $status,
        $errorMessage
    );

    $badge = match ($status) {
        'ONLINE'       => '[ONLINE]      ',
        'SLOW'         => '[SLOW]        ',
        'RATE_LIMITED' => '[RATE_LIMITED]',
        'OFFLINE'      => '[OFFLINE]     ',
        default        => '[UNKNOWN]     ',
    };
    $info = $errorMessage !== null
        ? $errorMessage
        : "{$result['http_code']} OK ({$result['response_time_ms']}ms)";

    echo "  {$badge} {$service['name']} -> {$info}\n";
}

function performHttpCheck(array $service): array {
    $allowInsecureSsl = getenv('CURL_INSECURE') === '1';
    $timeout = (int)$service['timeout_seconds'];

    $ch = curl_init();
    $curlOptions = [
        CURLOPT_URL            => $service['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => ($service['http_method'] === 'HEAD'),
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT      => 'ApiMonitor/1.0 (PHP-8; External Service Monitor)',
        CURLOPT_SSL_VERIFYPEER => !$allowInsecureSsl,
        CURLOPT_SSL_VERIFYHOST => $allowInsecureSsl ? 0 : 2,
    ];

    if (!$allowInsecureSsl && defined('CURLSSLOPT_NATIVE_CA')) {
        $curlOptions[CURLOPT_SSL_OPTIONS] = CURLSSLOPT_NATIVE_CA;
    }

    curl_setopt_array($ch, $curlOptions);

    $startTime = microtime(true);
    $body = curl_exec($ch);
    $responseTimeMs = round((microtime(true) - $startTime) * 1000, 2);

    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($ch);

    return [
        'body'             => $body,
        'http_code'        => $httpCode,
        'content_type'     => $contentType,
        'response_time_ms' => $responseTimeMs,
        'curl_error'       => $curlError,
    ];
}

function evaluateResponse(array $service, array $result): array {
    $expectedCode = (int)$service['expected_http_code'];
    $httpCode = $result['http_code'];

    if ($result['body'] === false || $httpCode === 0) {
        return ['OFFLINE', $result['curl_error'] ?: 'Connection failed or timed out'];
    }

    if ($httpCode === 429 && $expectedCode !== 429) {
        return ['RATE_LIMITED', 'Rate limit reached (HTTP 429) — not an outage'];
    }

    if ($httpCode !== $expectedCode) {
        return ['OFFLINE', "Expected HTTP {$expectedCode}, received {$httpCode}"];
    }

    $bodyStr = trim((string)$result['body']);
    $isJson = str_contains(strtolower($result['content_type']), 'application/json')
        || str_starts_with($bodyStr, '{')
        || str_starts_with($bodyStr, '[');

    if ($service['http_method'] !== 'HEAD' && $isJson) {
        $json = json_decode($bodyStr, true);
        if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['OFFLINE', 'Invalid JSON response received'];
        }

        if (str_contains($service['url'], 'open-meteo.com') && !isset($json['current'])) {
            return ['OFFLINE', "Weather response missing required 'current' field"];
        }
        if (str_contains($service['url'], 'dummyjson.com') && !isset($json['id'])) {
            return ['OFFLINE', "Product response missing required 'id' field"];
        }
        if (str_contains($service['url'], 'jsonplaceholder.typicode.com') && !isset($json['id'])) {
            return ['OFFLINE', "Post response missing required 'id' field"];
        }
    }

    $thresholdMs = (int)$service['warning_threshold_ms'];
    if ($result['response_time_ms'] > $thresholdMs) {
        $roundedMs = (int)round($result['response_time_ms']);
        return ['SLOW', "Slow response: {$roundedMs}ms exceeded {$thresholdMs}ms threshold"];
    }

    return ['ONLINE', null];
}

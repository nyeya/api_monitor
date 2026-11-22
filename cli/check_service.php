<?php
require_once __DIR__ . '/../config/db.php';

$lockFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'api_monitor.lock';
$lockFile = fopen($lockFilePath, 'c');
if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another check process is currently active. Exiting.\n";
    exit(0);
}

try {
    $pdo = getDbConnection();

    $sql = "
        SELECT s.*
        FROM services s
        LEFT JOIN (
            SELECT service_id, MAX(checked_at) AS last_checked
            FROM monitoring_history
            GROUP BY service_id
        ) h ON s.id = h.service_id
        WHERE s.enabled = TRUE
          AND (h.last_checked IS NULL OR h.last_checked <= NOW() - (s.check_interval_seconds || ' seconds')::INTERVAL)
        ORDER BY s.id ASC
    ";
    $services = $pdo->query($sql)->fetchAll();

    if (empty($services)) {
        echo "[" . date('Y-m-d H:i:s') . "] No services due for check.\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Checking " . count($services) . " service(s)...\n";
        foreach ($services as $service) {
            checkService($pdo, $service);
        }
        echo "[" . date('Y-m-d H:i:s') . "] All due checks completed.\n";
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Error during monitoring run: " . $e->getMessage() . "\n";
} finally {
    if (is_resource($lockFile)) {
        flock($lockFile, LOCK_UN);
        fclose($lockFile);
    }
}

function checkService(PDO $pdo, array $service): void {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $service['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => ($service['http_method'] === 'HEAD'),
        CURLOPT_TIMEOUT        => (int)$service['timeout_seconds'],
        CURLOPT_CONNECTTIMEOUT => (int)$service['timeout_seconds'],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT      => 'ApiMonitor/1.0 (+https://example.com/bot; PHP/8)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $startTime = microtime(true);
    $response = curl_exec($ch);
    $responseTimeMs = (int)round((microtime(true) - $startTime) * 1000);

    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $checkFailed = false;
    $errorMessage = null;

    if ($response === false || $httpCode === 0) {
        $checkFailed = true;
        $errorMessage = $curlError ?: 'Connection failed or timed out';
    } elseif ($httpCode === 429) {
        $checkFailed = true;
        $errorMessage = 'Rate limit exceeded (HTTP 429)';
    } elseif ($httpCode !== (int)$service['expected_http_code']) {
        $checkFailed = true;
        $errorMessage = "Expected HTTP {$service['expected_http_code']}, received {$httpCode}";
    } else {
        $isJson = str_contains(strtolower($contentType), 'application/json')
                  || str_starts_with(trim((string)$response), '{')
                  || str_starts_with(trim((string)$response), '[');

        if ($service['http_method'] !== 'HEAD' && $isJson) {
            $json = json_decode((string)$response, true);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                $checkFailed = true;
                $errorMessage = 'Invalid JSON response received';
            } else {
                if (str_contains($service['url'], 'open-meteo.com') && !isset($json['current'])) {
                    $checkFailed = true;
                    $errorMessage = "Weather response missing required 'current' field";
                } elseif (str_contains($service['url'], 'dummyjson.com') && !isset($json['id'])) {
                    $checkFailed = true;
                    $errorMessage = "Product response missing required 'id' field";
                } elseif (str_contains($service['url'], 'jsonplaceholder.typicode.com') && !isset($json['id'])) {
                    $checkFailed = true;
                    $errorMessage = "Post response missing required 'id' field";
                }
            }
        }
    }

    if ($checkFailed) {
        $status = 'OFFLINE';
    } else {
        $status = ($responseTimeMs > (int)$service['warning_threshold_ms']) ? 'SLOW' : 'ONLINE';
    }

    $stmt = $pdo->prepare("
        INSERT INTO monitoring_history (service_id, checked_at, http_code, response_time_ms, status, error_message)
        VALUES (?, NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([
        $service['id'],
        $httpCode ?: null,
        $responseTimeMs,
        $status,
        $checkFailed ? $errorMessage : null
    ]);

    $badge = match ($status) {
        'ONLINE'  => '[ONLINE]',
        'SLOW'    => '[SLOW]  ',
        'OFFLINE' => '[OFFLINE]',
        default   => '[UNKNOWN]',
    };
    $info = $checkFailed ? "Error: {$errorMessage}" : "{$httpCode} OK ({$responseTimeMs}ms)";
    echo "  {$badge} {$service['name']} -> {$info}\n";
}

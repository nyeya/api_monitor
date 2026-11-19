<?php
/**
 * API Monitor - Background Monitoring Engine
 * Performs scheduled HTTP checks using cURL, evaluates status,
 * and records results strictly in the monitoring_history table.
 */

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDbConnection();

    // Fetch enabled services
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
        echo "[" . date('Y-m-d H:i:s') . "] No enabled services found.\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Checking " . count($services) . " service(s)...\n";
        foreach ($services as $service) {
            checkService($pdo, $service);
        }
        echo "[" . date('Y-m-d H:i:s') . "] All checks completed.\n";
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Error during monitoring run: " . $e->getMessage() . "\n";
}

function checkService(PDO $pdo, array $service): void {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $service['url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => ($service['http_method'] === 'HEAD'),
        CURLOPT_TIMEOUT        => (int)$service['timeout_seconds'],
        CURLOPT_CONNECTTIMEOUT => (int)$service['timeout_seconds'],
        CURLOPT_FOLLOWLOCATION => false, // Prevent SSRF / open redirect following
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT      => 'ApiMonitor/1.0 (+https://example.com/bot; PHP/8)',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $startTime = microtime(true);
    $response = curl_exec($ch);
    $responseTimeMs = (int)round((microtime(true) - $startTime) * 1000);

    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $checkFailed = false;
    $errorMessage = null;

    if ($response === false || $httpCode === 0) {
        $checkFailed = true;
        $errorMessage = $curlError ?: 'Connection failed or timed out';
    } elseif ($httpCode !== (int)$service['expected_http_code']) {
        $checkFailed = true;
        $errorMessage = "Expected HTTP {$service['expected_http_code']}, received {$httpCode}";
    }

    if ($checkFailed) {
        $historyStatus = 'OFFLINE';
    } else {
        $historyStatus = ($responseTimeMs > (int)$service['warning_threshold_ms']) ? 'SLOW' : 'ONLINE';
    }

    $stmt = $pdo->prepare("
        INSERT INTO monitoring_history (service_id, checked_at, http_code, response_time_ms, status, error_message)
        VALUES (?, NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([
        $service['id'],
        $httpCode ?: null,
        $responseTimeMs,
        $historyStatus,
        $errorMessage
    ]);

    $badge = match ($historyStatus) {
        'ONLINE'  => '[ONLINE]',
        'SLOW'    => '[SLOW]  ',
        'OFFLINE' => '[OFFLINE]',
        default   => '[UNKNOWN]',
    };
    $info = $checkFailed ? "Error: {$errorMessage}" : "{$httpCode} OK ({$responseTimeMs}ms)";
    echo "  {$badge} {$service['name']} -> {$info}\n";
}
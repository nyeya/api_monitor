<?php
/**
 * API Monitor - Background Monitoring Engine
 * Performs scheduled HTTP checks using cURL, evaluates status,
 * and records results strictly in the monitoring_history table.
 */

require_once __DIR__ . '/../config/db.php';

// Prevent overlapping cron executions using cross-platform file locking
$lockFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'api_monitor.lock';
$lockFile = fopen($lockFilePath, 'c');
if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another check process is currently active. Exiting.\n";
    exit(0);
}

try {
    $pdo = getDbConnection();

    // Fetch enabled services due for checking based on latest check in monitoring_history
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

/**
 * Checks an individual service endpoint, evaluates health, and logs results in monitoring_history.
 */
function checkService(PDO $pdo, array $service): void {
    $ch = curl_init();

    // Security & configuration options
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
    $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Evaluate check failure conditions
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
        // Validate JSON payload for endpoints returning JSON
        $isJson = str_contains(strtolower($contentType), 'application/json')
                  || str_starts_with(trim((string)$response), '{')
                  || str_starts_with(trim((string)$response), '[');

        if ($service['http_method'] !== 'HEAD' && $isJson) {
            $json = json_decode((string)$response, true);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                $checkFailed = true;
                $errorMessage = 'Invalid JSON response received';
            } else {
                // Validate expected payload attributes
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

    // Inspect recent history to calculate consecutive failures
    $stmtRecent = $pdo->prepare("
        SELECT status, http_code, error_message
        FROM monitoring_history
        WHERE service_id = ?
        ORDER BY checked_at DESC, id DESC
        LIMIT 5
    ");
    $stmtRecent->execute([$service['id']]);
    $recentChecks = $stmtRecent->fetchAll();

    $prevStatus = !empty($recentChecks) ? $recentChecks[0]['status'] : 'UNKNOWN';

    // Count recent consecutive failures
    $consecutiveFailures = 0;
    foreach ($recentChecks as $check) {
        if ($check['status'] === 'OFFLINE' || (isset($check['error_message']) && str_starts_with($check['error_message'], 'Warning (Attempt'))) {
            $consecutiveFailures++;
        } else {
            break;
        }
    }

    // Require 3 consecutive failures before marking service OFFLINE
    if ($checkFailed) {
        $consecutiveFailures++;
        if ($consecutiveFailures >= 3) {
            $historyStatus = 'OFFLINE';
            $finalError = $errorMessage;
        } else {
            // Keep status ONLINE until 3 consecutive fails are reached
            $historyStatus = ($prevStatus === 'UNKNOWN') ? 'ONLINE' : $prevStatus;
            $finalError = "Warning (Attempt {$consecutiveFailures}/3): {$errorMessage}";
        }
    } else {
        $consecutiveFailures = 0;
        $historyStatus = ($responseTimeMs > (int)$service['warning_threshold_ms']) ? 'SLOW' : 'ONLINE';
        $finalError = null;
    }

    // Persist result strictly in monitoring_history
    $stmtHistory = $pdo->prepare("
        INSERT INTO monitoring_history (service_id, checked_at, http_code, response_time_ms, status, error_message)
        VALUES (?, NOW(), ?, ?, ?, ?)
    ");
    $stmtHistory->execute([
        $service['id'],
        $httpCode ?: null,
        $responseTimeMs,
        $historyStatus,
        $finalError
    ]);

    // Display formatted output in CLI
    $badge = match ($historyStatus) {
        'ONLINE'  => '[ONLINE]',
        'SLOW'    => '[SLOW]  ',
        'OFFLINE' => '[OFFLINE]',
        default   => '[UNKNOWN]',
    };
    $info = $checkFailed ? "Error: {$errorMessage}" : "{$httpCode} OK ({$responseTimeMs}ms)";
    echo "  {$badge} {$service['name']} -> {$info} (Consecutive fails: {$consecutiveFailures})\n";
}

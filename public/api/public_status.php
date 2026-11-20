<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = getDbConnection();

    // Query safe public information only, joining monitoring_history for telemetry
    $sql = "
        SELECT 
            s.id,
            s.name,
            s.description,
            CASE 
                WHEN s.enabled = FALSE THEN 'DISABLED'
                ELSE COALESCE(latest.status, 'UNKNOWN')
            END AS current_status,
            latest.response_time_ms,
            latest.checked_at,
            ROUND(
                COUNT(CASE WHEN h.checked_at >= NOW() - INTERVAL '24 hours' AND h.status != 'OFFLINE' THEN 1 END)::DECIMAL
                / NULLIF(COUNT(CASE WHEN h.checked_at >= NOW() - INTERVAL '24 hours' THEN 1 END), 0) * 100,
                1
            ) AS uptime_24h
        FROM services s
        LEFT JOIN (
            SELECT DISTINCT ON (service_id)
                service_id, status, response_time_ms, checked_at
            FROM monitoring_history
            ORDER BY service_id, checked_at DESC, id DESC
        ) latest ON s.id = latest.service_id
        LEFT JOIN monitoring_history h ON s.id = h.service_id
        WHERE s.enabled = TRUE
        GROUP BY s.id, s.name, s.description, s.enabled, latest.status, latest.response_time_ms, latest.checked_at
        ORDER BY s.id ASC
    ";

    $services = $pdo->query($sql)->fetchAll();

    // Determine overall system health
    $hasOffline = false;
    $hasSlow = false;
    foreach ($services as $svc) {
        if ($svc['current_status'] === 'OFFLINE') {
            $hasOffline = true;
        } elseif ($svc['current_status'] === 'SLOW') {
            $hasSlow = true;
        }
    }

    $systemStatus = 'OPERATIONAL';
    if ($hasOffline) {
        $systemStatus = 'OUTAGE';
    } elseif ($hasSlow) {
        $systemStatus = 'DEGRADED';
    }

    echo json_encode([
        'system_status' => $systemStatus,
        'checked_at'    => date('c'),
        'services'      => $services
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch public status: ' . $e->getMessage()]);
}

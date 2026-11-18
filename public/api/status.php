<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = getDbConnection();

    // Query configuration from services and latest operational telemetry from monitoring_history
    $sql = "
        SELECT 
            s.id,
            s.name,
            s.description,
            s.url,
            s.enabled,
            s.warning_threshold_ms,
            CASE 
                WHEN s.enabled = FALSE THEN 'DISABLED'
                ELSE COALESCE(h.status, 'UNKNOWN')
            END AS current_status,
            h.http_code,
            h.response_time_ms,
            h.checked_at,
            h.error_message
        FROM services s
        LEFT JOIN (
            SELECT DISTINCT ON (service_id) 
                service_id, status, http_code, response_time_ms, checked_at, error_message
            FROM monitoring_history
            ORDER BY service_id, checked_at DESC, id DESC
        ) h ON s.id = h.service_id
        ORDER BY s.id ASC
    ";

    echo json_encode($pdo->query($sql)->fetchAll());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch status data: ' . $e->getMessage()]);
}

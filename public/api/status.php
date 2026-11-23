<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo = getDbConnection();

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
                ELSE COALESCE(latest.status, 'UNKNOWN')
            END AS current_status,
            latest.http_code,
            latest.response_time_ms,
            latest.checked_at,
            latest.error_message,
            stats.uptime_24h,
            stats.uptime_7d
        FROM services s
        LEFT JOIN (
            SELECT DISTINCT ON (service_id) 
                service_id, status, http_code, response_time_ms, checked_at, error_message
            FROM monitoring_history
            ORDER BY service_id, checked_at DESC, id DESC
        ) latest ON s.id = latest.service_id
        LEFT JOIN (
            SELECT 
                service_id,
                ROUND(
                    COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '24 hours' AND status != 'OFFLINE' THEN 1 END)::DECIMAL
                    / NULLIF(COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '24 hours' THEN 1 END), 0) * 100,
                    1
                ) AS uptime_24h,
                ROUND(
                    COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '7 days' AND status != 'OFFLINE' THEN 1 END)::DECIMAL
                    / NULLIF(COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '7 days' THEN 1 END), 0) * 100,
                    1
                ) AS uptime_7d
            FROM monitoring_history
            WHERE checked_at >= NOW() - INTERVAL '7 days'
            GROUP BY service_id
        ) stats ON s.id = stats.service_id
        ORDER BY s.id ASC
    ";

    echo json_encode($pdo->query($sql)->fetchAll());
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch status data: ' . $e->getMessage()]);
}

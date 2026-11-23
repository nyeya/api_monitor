<?php
/**
 * Database queries for services and monitoring history.
 * All queries use PDO parameterized statements.
 */

function getAllServices(PDO $pdo): array {
    $sql = 'SELECT id, name, description, url, http_method, expected_http_code, timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled FROM services ORDER BY id ASC';
    return $pdo->query($sql)->fetchAll();
}

function getServiceById(PDO $pdo, int $serviceId): ?array {
    $stmt = $pdo->prepare('SELECT id, name, description, url, http_method, expected_http_code, timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled FROM services WHERE id = ?');
    $stmt->execute([$serviceId]);
    return $stmt->fetch() ?: null;
}

function createService(PDO $pdo, array $data): void {
    $stmt = $pdo->prepare("
        INSERT INTO services (
            name, description, url, http_method, expected_http_code,
            timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['name'],
        $data['description'],
        $data['url'],
        $data['http_method'],
        $data['expected_http_code'],
        $data['timeout_seconds'],
        $data['warning_threshold_ms'],
        $data['check_interval_seconds'],
        $data['enabled'] ? 'true' : 'false',
    ]);
}

function updateService(PDO $pdo, int $serviceId, array $data): bool {
    $stmt = $pdo->prepare("
        UPDATE services SET
            name = ?, description = ?, url = ?, http_method = ?,
            expected_http_code = ?, timeout_seconds = ?, warning_threshold_ms = ?,
            check_interval_seconds = ?, enabled = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['name'],
        $data['description'],
        $data['url'],
        $data['http_method'],
        $data['expected_http_code'],
        $data['timeout_seconds'],
        $data['warning_threshold_ms'],
        $data['check_interval_seconds'],
        $data['enabled'] ? 'true' : 'false',
        $serviceId,
    ]);
    return $stmt->rowCount() > 0;
}

function toggleServiceEnabled(PDO $pdo, int $serviceId): ?bool {
    $service = getServiceById($pdo, $serviceId);
    if (!$service) {
        return null;
    }
    $newState = !filter_var($service['enabled'], FILTER_VALIDATE_BOOLEAN);
    $stmt = $pdo->prepare('UPDATE services SET enabled = ? WHERE id = ?');
    $stmt->execute([$newState ? 'true' : 'false', $serviceId]);
    return $newState;
}

function deleteService(PDO $pdo, int $serviceId): bool {
    $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
    $stmt->execute([$serviceId]);
    return $stmt->rowCount() > 0;
}

function getDueServices(PDO $pdo): array {
    $sql = "
        SELECT s.*
        FROM services s
        LEFT JOIN (
            SELECT service_id, MAX(checked_at) AS last_checked
            FROM monitoring_history
            GROUP BY service_id
        ) h ON s.id = h.service_id
        WHERE s.enabled = TRUE
          AND (
              h.last_checked IS NULL
              OR h.last_checked <= NOW() - (s.check_interval_seconds || ' seconds')::INTERVAL
          )
        ORDER BY s.id ASC
    ";
    return $pdo->query($sql)->fetchAll();
}

function saveCheckResult(
    PDO $pdo,
    int $serviceId,
    ?int $httpCode,
    int $responseTimeMs,
    string $status,
    ?string $errorMessage
): void {
    $stmt = $pdo->prepare("
        INSERT INTO monitoring_history (service_id, checked_at, http_code, response_time_ms, status, error_message)
        VALUES (?, NOW(), ?, ?, ?, ?)
    ");
    $stmt->execute([$serviceId, $httpCode, $responseTimeMs, $status, $errorMessage]);
}

/**
 * Returns live status + 24h/7d availability for all services.
 * When $publicOnly is true, returns only enabled services and omits internal fields.
 */
function getServicesStatus(PDO $pdo, bool $publicOnly = false): array {
    $selectColumns = $publicOnly
        ? "
            s.name,
            s.description,
            COALESCE(latest.status, 'UNKNOWN') AS current_status,
            latest.response_time_ms,
            latest.checked_at,
            stats.uptime_24h,
            stats.uptime_7d
        "
        : "
            s.id,
            s.name,
            s.description,
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
        ";

    $whereClause = $publicOnly ? 'WHERE s.enabled = TRUE' : '';

    $sql = "
        SELECT {$selectColumns}
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
        {$whereClause}
        ORDER BY s.id ASC
    ";

    return $pdo->query($sql)->fetchAll();
}

function getServiceStats(PDO $pdo, int $serviceId): array {
    $stmt = $pdo->prepare("
        SELECT
            ROUND(AVG(CASE WHEN status != 'OFFLINE' THEN response_time_ms END), 2) AS avg_response_time_ms,
            ROUND(
                COUNT(CASE WHEN status != 'OFFLINE' THEN 1 END)::DECIMAL
                / NULLIF(COUNT(*), 0) * 100,
                2
            ) AS uptime_percentage,
            ROUND(
                COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '24 hours' AND status != 'OFFLINE' THEN 1 END)::DECIMAL
                / NULLIF(COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '24 hours' THEN 1 END), 0) * 100,
                2
            ) AS uptime_24h,
            ROUND(
                COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '7 days' AND status != 'OFFLINE' THEN 1 END)::DECIMAL
                / NULLIF(COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '7 days' THEN 1 END), 0) * 100,
                2
            ) AS uptime_7d,
            MAX(CASE WHEN status != 'OFFLINE' THEN checked_at END) AS last_successful_check,
            MAX(CASE WHEN status = 'OFFLINE' THEN checked_at END) AS last_failed_check
        FROM monitoring_history
        WHERE service_id = ?
    ");
    $stmt->execute([$serviceId]);
    return $stmt->fetch() ?: [];
}

function getRecentServiceHistory(PDO $pdo, int $serviceId, int $limit = 50): array {
    $stmt = $pdo->prepare("
        SELECT id, checked_at, http_code, response_time_ms, status, error_message
        FROM (
            SELECT id, checked_at, http_code, response_time_ms, status, error_message
            FROM monitoring_history
            WHERE service_id = ?
            ORDER BY checked_at DESC, id DESC
            LIMIT ?
        ) recent_checks
        ORDER BY checked_at ASC, id ASC
    ");
    $stmt->bindValue(1, $serviceId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

<?php
/**
 * Public, read-only JSON API for the Public Status Page (/status.php).
 * Exposes ONLY non-sensitive health metrics for enabled services.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/service_repository.php';

try {
    $pdo = getDbConnection();
    echo json_encode(getServicesStatus($pdo, true));
} catch (Exception $e) {
    error_log('Could not fetch public status: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch public status data.']);
}

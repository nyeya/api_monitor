<?php

/**
 * Private JSON API for the authenticated Operational Dashboard (/index.php).
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/service_repository.php';

if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

try {
    $pdo = getDbConnection();
    echo json_encode(getServicesStatus($pdo, false));
} catch (Exception $e) {
    error_log('Could not fetch dashboard status: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not fetch status data.']);
}

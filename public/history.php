<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/service_repository.php';

requireAdminLogin();

$serviceId = validateId($_GET['id'] ?? null);
$service = null;
$stats = [];
$history = [];
$pageError = null;

if ($serviceId === false) {
    http_response_code(400);
    $pageError = 'A valid service ID is required.';
} else {
    try {
        $pdo = getDbConnection();
        $service = getServiceById($pdo, $serviceId);

        if (!$service) {
            http_response_code(404);
            $pageError = 'Service not found.';
        } else {
            $stats = getServiceStats($pdo, $serviceId);
            $history = getRecentServiceHistory($pdo, $serviceId, 50);
        }
    } catch (PDOException $e) {
        error_log('Could not load service history: ' . $e->getMessage());
        http_response_code(500);
        $pageError = 'Could not load service history.';
    }
}

$responsePoints = array_values(array_filter($history, fn($p) => $p['response_time_ms'] !== null));
$chartValues = array_map(fn($p) => (float)$p['response_time_ms'], $responsePoints);
$chartLabels = array_map(fn($p) => date('Y-m-d H:i:s', strtotime($p['checked_at'])), $responsePoints);

$currentStatus = $history ? $history[count($history) - 1]['status'] : 'UNKNOWN';
$statusDot = getStatusDot($currentStatus);
$statusBadgeClass = getStatusBadgeClass($currentStatus);

require __DIR__ . '/../views/history.php';

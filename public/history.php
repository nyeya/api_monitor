<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/service_repository.php';

requireAdminLogin();

$serviceId = validateId($_GET['id'] ?? null);
$allowedLimits = [25, 50, 100];
$requestedLimit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$historyLimit = in_array($requestedLimit, $allowedLimits, true) ? $requestedLimit : 50;

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
            $history = getRecentServiceHistory($pdo, $serviceId, $historyLimit);
        }
    } catch (PDOException $e) {
        error_log('Could not load service history: ' . $e->getMessage());
        http_response_code(500);
        $pageError = 'Could not load service history.';
    }
}

$responsePoints = array_values(array_filter($history, fn($p) => $p['response_time_ms'] !== null));
$chartValues = array_map(fn($p) => (float)$p['response_time_ms'], $responsePoints);
$chartLabels = array_map(fn($p) => date('M j, H:i:s', strtotime($p['checked_at'])), $responsePoints);

if ($service && !filter_var($service['enabled'], FILTER_VALIDATE_BOOLEAN)) {
    $currentStatus = 'DISABLED';
} else {
    $currentStatus = $history ? $history[count($history) - 1]['status'] : 'UNKNOWN';
}
$statusDot = getStatusDot($currentStatus);
$statusBadgeClass = getStatusBadgeClass($currentStatus);
$statusLabel = getStatusLabel($currentStatus);

require __DIR__ . '/../views/history.php';

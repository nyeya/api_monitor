<?php
/**
 * API Monitor - Local Mock API for Predictable Testing
 * Deliberately simulates various HTTP status codes, latencies, and timeouts.
 * 
 * Usage:
 *   /mock_api.php               -> HTTP 200 OK
 *   /mock_api.php?status=404    -> HTTP 404 Not Found
 *   /mock_api.php?status=500    -> HTTP 500 Internal Server Error
 *   /mock_api.php?status=503    -> HTTP 503 Service Unavailable
 *   /mock_api.php?status=429    -> HTTP 429 Too Many Requests (Rate limit)
 *   /mock_api.php?delay=3       -> HTTP 200 with 3s sleep (Triggers SLOW warning)
 *   /mock_api.php?timeout=1     -> Sleep for 10s (Triggers connection timeout)
 */

$status = isset($_GET['status']) ? (int)$_GET['status'] : 200;
$delay = isset($_GET['delay']) ? (int)$_GET['delay'] : 0;
$timeout = isset($_GET['timeout']) ? (bool)$_GET['timeout'] : false;

// Simulate timeout (sleep longer than typical 5s timeout)
if ($timeout) {
    sleep(10);
} elseif ($delay > 0) {
    sleep(min($delay, 30));
}

http_response_code($status);
header('Content-Type: application/json');

if ($status === 429) {
    header('Retry-After: 60');
    echo json_encode([
        'error' => 'Rate limit exceeded',
        'http_status' => 429,
        'message' => 'Too many requests. Please slow down.'
    ]);
} elseif ($status >= 400) {
    echo json_encode([
        'error' => "Simulated HTTP error {$status}",
        'http_status' => $status,
        'message' => 'Deliberate test failure response from Local Mock API'
    ]);
} else {
    echo json_encode([
        'status' => 'ok',
        'http_status' => 200,
        'id' => 1,
        'message' => 'Local Mock API is responding normally.',
        'timestamp' => date('c')
    ]);
}

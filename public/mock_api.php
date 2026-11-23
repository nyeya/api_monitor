<?php
/**
 * Local Mock API for predictable testing of HTTP codes, delays, and timeouts.
 *
 * Examples:
 *   /mock_api.php               -> HTTP 200 OK
 *   /mock_api.php?status=404    -> HTTP 404 Not Found
 *   /mock_api.php?status=500    -> HTTP 500 Internal Server Error
 *   /mock_api.php?status=503    -> HTTP 503 Service Unavailable
 *   /mock_api.php?status=429    -> HTTP 429 Rate Limit Reached
 *   /mock_api.php?delay=3       -> HTTP 200 after 3s delay (triggers SLOW)
 *   /mock_api.php?timeout=1     -> Sleeps 10s (triggers timeout)
 */

$status  = isset($_GET['status']) ? (int)$_GET['status'] : 200;
$delay   = isset($_GET['delay']) ? (int)$_GET['delay'] : 0;
$timeout = !empty($_GET['timeout']);

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
        'error'       => 'Rate limit exceeded',
        'http_status' => 429,
        'message'     => 'Too many requests. Please slow down.',
    ]);
} elseif ($status >= 400) {
    echo json_encode([
        'error'       => "Simulated HTTP error {$status}",
        'http_status' => $status,
        'message'     => 'Deliberate test failure response from Local Mock API.',
    ]);
} else {
    echo json_encode([
        'status'      => 'ok',
        'http_status' => $status,
        'id'          => 1,
        'message'     => 'Local Mock API is responding normally.',
        'timestamp'   => date('c'),
    ]);
}

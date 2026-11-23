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
/*
 * Supported status codes:
 *
 *  2xx  Success
 *       200  OK
 *       201  Created
 *       202  Accepted
 *       204  No Content
 *       206  Partial Content
 *
 *  3xx  Redirection
 *       301  Moved Permanently
 *       302  Found  (Temporary Redirect)
 *       303  See Other
 *       304  Not Modified
 *       307  Temporary Redirect
 *       308  Permanent Redirect
 *
 *  4xx  Client Errors
 *       400  Bad Request
 *       401  Unauthorized
 *       402  Payment Required
 *       403  Forbidden
 *       404  Not Found
 *       405  Method Not Allowed
 *       406  Not Acceptable
 *       408  Request Timeout
 *       409  Conflict
 *       410  Gone
 *       411  Length Required
 *       412  Precondition Failed
 *       413  Content Too Large
 *       414  URI Too Long
 *       415  Unsupported Media Type
 *       416  Range Not Satisfiable
 *       418  I'm a Teapot
 *       422  Unprocessable Entity
 *       423  Locked
 *       425  Too Early
 *       426  Upgrade Required
 *       428  Precondition Required
 *       429  Too Many Requests
 *       431  Request Header Fields Too Large
 *       451  Unavailable For Legal Reasons
 *
 *  5xx  Server Errors
 *       500  Internal Server Error
 *       501  Not Implemented
 *       502  Bad Gateway
 *       503  Service Unavailable
 *       504  Gateway Timeout
 *       505  HTTP Version Not Supported
 *       507  Insufficient Storage
 *       511  Network Authentication Required
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

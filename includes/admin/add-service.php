<?php
$formValues = [
    'id'                      => '',
    'name'                    => '',
    'description'             => '',
    'url'                     => '',
    'http_method'             => 'GET',
    'expected_http_code'      => '200',
    'timeout_seconds'         => '5',
    'warning_threshold_ms'    => '1000',
    'check_interval_seconds'  => '60',
    'enabled'                 => true,
];
$editingService = false;
$pageError      = null;
$formAction     = '/add-service.php';

$message = ($_GET['message'] ?? null) === 'added' ? 'Service added successfully.' : '';

try {
    $pdo = getDbConnection();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!hash_equals($csrfToken, postString('csrf_token'))) {
            http_response_code(403);
            $pageError = 'The security token has expired or is invalid. Please try again.';
        } elseif (postString('action') !== 'add') {
            http_response_code(400);
            $pageError = 'Invalid form action.';
        } else {
            $formValues['name']                   = postString('name');
            $formValues['description']            = postString('description');
            $formValues['url']                    = postString('url');
            $formValues['http_method']            = postString('http_method');
            $formValues['expected_http_code']     = postString('expected_http_code');
            $formValues['timeout_seconds']        = postString('timeout_seconds');
            $formValues['warning_threshold_ms']   = postString('warning_threshold_ms');
            $formValues['check_interval_seconds'] = postString('check_interval_seconds');
            $formValues['enabled']                = postString('enabled') === '1';

            $errors = [];

            if ($formValues['name'] === '' || stringLength($formValues['name']) > 150) {
                $errors[] = 'Name is required and must be 150 characters or fewer.';
            }
            if (stringLength($formValues['description']) > 2000) {
                $errors[] = 'Description must be 2000 characters or fewer.';
            }
            print($formValues['url']);
            if (!isSafeUrl($formValues['url'])) {
                $errors[] = 'Enter a valid public HTTP or HTTPS URL (Private networks and internal addresses are blocked).';
            }
            if (!in_array($formValues['http_method'], ['GET', 'HEAD'], true)) {
                $errors[] = 'Choose GET or HEAD as the HTTP method.';
            }

            $numbers = [];
            foreach ([
                'expected_http_code'     => [100, 599],
                'timeout_seconds'        => [1, 120],
                'warning_threshold_ms'   => [1, 120000],
                'check_interval_seconds' => [5, 86400],
            ] as $field => $range) {
                $number = filter_var($formValues[$field], FILTER_VALIDATE_INT);
                if ($number === false || $number < $range[0] || $number > $range[1]) {
                    $errors[] = 'Please check the values entered for numeric settings.';
                    break;
                }
                $numbers[$field] = $number;
            }

            if ($errors) {
                http_response_code(400);
                $pageError = implode(' ', $errors);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO services (
                        name, description, url, http_method, expected_http_code,
                        timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $formValues['name'],
                    $formValues['description'],
                    $formValues['url'],
                    $formValues['http_method'],
                    $numbers['expected_http_code'],
                    $numbers['timeout_seconds'],
                    $numbers['warning_threshold_ms'],
                    $numbers['check_interval_seconds'],
                    $formValues['enabled'] ? 'true' : 'false',
                ]);
                header('Location: /add-service.php?message=added', true, 303);
                exit;
            }
        }
    }
} catch (PDOException $exception) {
    error_log('Could not process add-service request: ' . $exception->getMessage());
    http_response_code(500);
    $pageError = 'Database operation error.';
}

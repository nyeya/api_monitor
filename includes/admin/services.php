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
$services       = [];
$editingService = false;
$confirmDelete  = null;
$pageError      = null;
$formAction     = '/admin.php';

$messages = [
    'updated' => 'Service updated successfully.',
    'toggled' => 'Service monitoring status updated.',
    'deleted' => 'Service and its history deleted.',
];
$message = is_string($_GET['message'] ?? null) ? ($messages[$_GET['message']] ?? '') : '';

try {
    $pdo = getDbConnection();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!hash_equals($csrfToken, postString('csrf_token'))) {
            http_response_code(403);
            $pageError = 'The security token has expired or is invalid. Please try again.';
        } else {
            $action = postString('action');

            if ($action === 'save') {
                $formValues['id'] = postString('id');
                $serviceId        = validateId($formValues['id']);

                if ($serviceId === false) {
                    http_response_code(400);
                    $pageError = 'Invalid service ID. Use Add Service to create a new service.';
                } else {
                    $editingService = true;

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
                            UPDATE services SET
                                name = ?, description = ?, url = ?, http_method = ?,
                                expected_http_code = ?, timeout_seconds = ?, warning_threshold_ms = ?,
                                check_interval_seconds = ?, enabled = ?
                            WHERE id = ?
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
                            $serviceId,
                        ]);
                        if ($stmt->rowCount() === 0) {
                            http_response_code(404);
                            $pageError = 'Service not found.';
                        } else {
                            header('Location: /admin.php?message=updated', true, 303);
                            exit;
                        }
                    }
                }
            } elseif ($action === 'toggle') {
                $serviceId = validateId(postString('id'));
                if ($serviceId === false) {
                    http_response_code(400);
                    $pageError = 'Invalid service ID.';
                } else {
                    $stmt = $pdo->prepare('SELECT enabled FROM services WHERE id = ?');
                    $stmt->execute([$serviceId]);
                    $service = $stmt->fetch();
                    if (!$service) {
                        http_response_code(404);
                        $pageError = 'Service not found.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE services SET enabled = ? WHERE id = ?');
                        $stmt->execute([
                            filter_var($service['enabled'], FILTER_VALIDATE_BOOLEAN) ? 'false' : 'true',
                            $serviceId,
                        ]);
                        header('Location: /admin.php?message=toggled', true, 303);
                        exit;
                    }
                }
            } elseif ($action === 'delete' && postString('confirm_delete') === '1') {
                $serviceId = validateId(postString('id'));
                if ($serviceId === false) {
                    http_response_code(400);
                    $pageError = 'Invalid service ID.';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
                    $stmt->execute([$serviceId]);
                    if ($stmt->rowCount() === 0) {
                        http_response_code(404);
                        $pageError = 'Service not found.';
                    } else {
                        header('Location: /admin.php?message=deleted', true, 303);
                        exit;
                    }
                }
            } else {
                http_response_code(400);
                $pageError = 'Invalid form action.';
            }
        }
    } elseif (isset($_GET['edit'])) {
        $serviceId = validateId($_GET['edit']);
        if ($serviceId === false) {
            http_response_code(400);
            $pageError = 'Invalid service ID.';
        } else {
            $stmt = $pdo->prepare('SELECT id, name, description, url, http_method, expected_http_code, timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled FROM services WHERE id = ?');
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();
            if (!$service) {
                http_response_code(404);
                $pageError = 'Service not found.';
            } else {
                $editingService = true;
                foreach ([
                    'id', 'name', 'description', 'url', 'http_method', 'expected_http_code',
                    'timeout_seconds', 'warning_threshold_ms', 'check_interval_seconds',
                ] as $field) {
                    $formValues[$field] = $service[$field] ?? '';
                }
                $formValues['enabled'] = filter_var($service['enabled'], FILTER_VALIDATE_BOOLEAN);
            }
        }
    } elseif (isset($_GET['confirm_delete'])) {
        $serviceId = validateId($_GET['confirm_delete']);
        if ($serviceId === false) {
            http_response_code(400);
            $pageError = 'Invalid service ID.';
        } else {
            $stmt = $pdo->prepare('SELECT id, name FROM services WHERE id = ?');
            $stmt->execute([$serviceId]);
            $confirmDelete = $stmt->fetch();
            if (!$confirmDelete) {
                http_response_code(404);
                $pageError = 'Service not found.';
            }
        }
    }

    $services = $pdo->query('SELECT id, name, url, http_method, expected_http_code, timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled FROM services ORDER BY id ASC')->fetchAll();
} catch (PDOException $exception) {
    error_log('Could not process admin request: ' . $exception->getMessage());
    http_response_code(500);
    $pageError = 'Database operation error.';
}

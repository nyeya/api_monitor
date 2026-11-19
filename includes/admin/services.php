<?php
$formValues = [
    'id' => '',
    'name' => '',
    'description' => '',
    'url' => '',
    'http_method' => 'GET',
    'expected_http_code' => '200',
    'timeout_seconds' => '5',
    'warning_threshold_ms' => '1000',
    'check_interval_seconds' => '60',
    'enabled' => true,
];
$services = [];
$editingService = false;
$confirmDelete = null;
$pageError = null;
$messages = [
    'added' => 'Service added successfully.',
    'updated' => 'Service updated successfully.',
    'toggled' => 'Service monitoring status updated.',
    'deleted' => 'Service and its history deleted.',
];
$messageKey = $_GET['message'] ?? '';
$message = is_string($messageKey) ? ($messages[$messageKey] ?? '') : '';

try {
    $pdo = getDbConnection();
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($requestMethod === 'POST') {
        $submittedToken = postString('csrf_token');
        if (!hash_equals($csrfToken, $submittedToken)) {
            http_response_code(403);
            $pageError = 'The security token has expired or is invalid. Please try again.';
        } else {
            $action = postString('action');

            if ($action === 'save') {
                $formValues['id'] = postString('id');
                $formValues['name'] = postString('name');
                $formValues['description'] = postString('description');
                $formValues['url'] = postString('url');
                $formValues['http_method'] = postString('http_method');
                $formValues['expected_http_code'] = postString('expected_http_code');
                $formValues['timeout_seconds'] = postString('timeout_seconds');
                $formValues['warning_threshold_ms'] = postString('warning_threshold_ms');
                $formValues['check_interval_seconds'] = postString('check_interval_seconds');
                $formValues['enabled'] = postString('enabled') === '1';

                $errors = [];
                $serviceId = null;
                if ($formValues['id'] !== '') {
                    $serviceId = validateId($formValues['id']);
                    if ($serviceId === false) {
                        $errors[] = 'Invalid service ID.';
                    } else {
                        $editingService = true;
                    }
                }

                if ($formValues['name'] === '' || stringLength($formValues['name']) > 150) {
                    $errors[] = 'Name is required and must be 150 characters or fewer.';
                }
                if (stringLength($formValues['description']) > 2000) {
                    $errors[] = 'Description must be 2000 characters or fewer.';
                }
                if (!filter_var($formValues['url'], FILTER_VALIDATE_URL)) {
                    $errors[] = 'Enter a valid public HTTP or HTTPS URL (Private networks and internal addresses are blocked).';
                }
                if (!in_array($formValues['http_method'], ['GET', 'HEAD'], true)) {
                    $errors[] = 'Choose GET or HEAD as the HTTP method.';
                }

                $numberFields = [
                    'expected_http_code' => [100, 599],
                    'timeout_seconds' => [1, 120],
                    'warning_threshold_ms' => [1, 120000],
                    'check_interval_seconds' => [5, 86400],
                ];
                $numbers = [];
                foreach ($numberFields as $field => $range) {
                    $number = filter_var($formValues[$field], FILTER_VALIDATE_INT);
                    if ($number === false || $number < $range[0] || $number > $range[1]) {
                        $errors[] = 'Please check the values entered for numeric settings.';
                        break;
                    }
                    $numbers[$field] = $number;
                }

                if ($errors) {
                    http_response_code(400);
                    $pageError = implode(' ', array_unique($errors));
                } else {
                    $values = [
                        $formValues['name'],
                        $formValues['description'],
                        $formValues['url'],
                        $formValues['http_method'],
                        $numbers['expected_http_code'],
                        $numbers['timeout_seconds'],
                        $numbers['warning_threshold_ms'],
                        $numbers['check_interval_seconds'],
                        $formValues['enabled'] ? 'true' : 'false',
                    ];

                    if ($serviceId === null) {
                        $stmt = $pdo->prepare("
                            INSERT INTO services (
                                name, description, url, http_method, expected_http_code,
                                timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute($values);
                        header('Location: /admin.php?message=added', true, 303);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE services SET
                                name = ?, description = ?, url = ?, http_method = ?,
                                expected_http_code = ?, timeout_seconds = ?, warning_threshold_ms = ?,
                                check_interval_seconds = ?, enabled = ?
                            WHERE id = ?
                        ");
                        $values[] = $serviceId;
                        $stmt->execute($values);
                        if ($stmt->rowCount() === 0) {
                            http_response_code(404);
                            $pageError = 'Service not found.';
                        } else {
                            header('Location: /admin.php?message=updated');
                        }
                    }
                    if ($pageError === null) {
                        exit;
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
                        $enabled = filter_var($service['enabled'], FILTER_VALIDATE_BOOLEAN);
                        $stmt = $pdo->prepare('UPDATE services SET enabled = ? WHERE id = ?');
                        $stmt->execute([$enabled ? 'false' : 'true', $serviceId]);
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
            $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
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
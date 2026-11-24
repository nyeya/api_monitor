<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/service_repository.php';

requireAdminLogin();
$csrfToken = getCsrfToken();

$formValues     = defaultServiceFormValues();
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
        if (!verifyCsrfToken(postString('csrf_token'))) {
            http_response_code(403);
            $pageError = 'The security token has expired or is invalid. Please try again.';
        } else {
            $action = postString('action');

            if ($action === 'save') {
                $formValues['id'] = postString('id');
                $serviceId = validateId($formValues['id']);

                if ($serviceId === false) {
                    http_response_code(400);
                    $pageError = 'Invalid service ID. Use Add Service to create a new service.';
                } else {
                    $editingService = true;
                    $formValues = readServiceFormPost((string)$serviceId);

                    [$cleanData, $errors] = validateServiceInput($formValues);
                    if ($errors) {
                        http_response_code(400);
                        $pageError = implode(' ', $errors);
                    } elseif (!updateService($pdo, $serviceId, $cleanData)) {
                        http_response_code(404);
                        $pageError = 'Service not found.';
                    } else {
                        header('Location: /admin.php?message=updated', true, 303);
                        exit;
                    }
                }
            } elseif ($action === 'toggle') {
                $serviceId = validateId(postString('id'));
                if ($serviceId === false) {
                    http_response_code(400);
                    $pageError = 'Invalid service ID.';
                } elseif (toggleServiceEnabled($pdo, $serviceId) === null) {
                    http_response_code(404);
                    $pageError = 'Service not found.';
                } else {
                    header('Location: /admin.php?message=toggled', true, 303);
                    exit;
                }
            } elseif ($action === 'delete' && postString('confirm_delete') === '1') {
                $serviceId = validateId(postString('id'));
                if ($serviceId === false) {
                    http_response_code(400);
                    $pageError = 'Invalid service ID.';
                } elseif (!deleteService($pdo, $serviceId)) {
                    http_response_code(404);
                    $pageError = 'Service not found.';
                } else {
                    header('Location: /admin.php?message=deleted', true, 303);
                    exit;
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
            $service = getServiceById($pdo, $serviceId);
            if (!$service) {
                http_response_code(404);
                $pageError = 'Service not found.';
            } else {
                $editingService = true;
                foreach ($formValues as $field => $_) {
                    if ($field === 'enabled') {
                        $formValues['enabled'] = filter_var($service['enabled'], FILTER_VALIDATE_BOOLEAN);
                    } else {
                        $formValues[$field] = $service[$field] ?? '';
                    }
                }
            }
        }
    } elseif (isset($_GET['confirm_delete'])) {
        $serviceId = validateId($_GET['confirm_delete']);
        if ($serviceId === false) {
            http_response_code(400);
            $pageError = 'Invalid service ID.';
        } else {
            $confirmDelete = getServiceById($pdo, $serviceId);
            if (!$confirmDelete) {
                http_response_code(404);
                $pageError = 'Service not found.';
            }
        }
    }

    $services = getAllServices($pdo);
} catch (PDOException $e) {
    error_log('Could not process admin request: ' . $e->getMessage());
    http_response_code(500);
    $pageError = 'Database operation error.';
}

require __DIR__ . '/../views/admin/manage_services.php';

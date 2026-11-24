<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/service_repository.php';

requireAdminLogin();
$csrfToken      = getCsrfToken();
$formValues     = defaultServiceFormValues();
$editingService = false;
$pageError      = null;
$formAction     = '/add_service.php';
$message        = ($_GET['message'] ?? null) === 'added' ? 'Service added successfully.' : '';

try {
    $pdo = getDbConnection();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!verifyCsrfToken(postString('csrf_token'))) {
            http_response_code(403);
            $pageError = 'The security token has expired or is invalid. Please try again.';
        } elseif (postString('action') !== 'add') {
            http_response_code(400);
            $pageError = 'Invalid form action.';
        } else {
            $formValues = readServiceFormPost();
            [$cleanData, $errors] = validateServiceInput($formValues);

            if ($errors) {
                http_response_code(400);
                $pageError = implode(' ', $errors);
            } else {
                createService($pdo, $cleanData);
                header('Location: /add_service.php?message=added', true, 303);
                exit;
            }
        }
    }
} catch (PDOException $e) {
    error_log('Could not add service: ' . $e->getMessage());
    http_response_code(500);
    $pageError = 'Database operation error.';
}

require __DIR__ . '/../views/admin/add_service.php';

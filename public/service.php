<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt =$pdo->prepare("SELECT * FROM services WHERE id = ?");
                $stmt->execute([(int)$_GET['id']]);
                $service =$stmt->fetch();
                if (!$service) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Service not found']);
                    exit;
                }
                echo json_encode($service);
            } else {
                $stmt =$pdo->query("SELECT * FROM services ORDER BY id DESC");
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['name']) || empty($data['url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and URL are required.']);
                exit;
            }

            if (!isSafeUrl($data['url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or restricted URL (Private IPs not allowed).']);
                exit;
            }

            $stmt =$pdo->prepare("
                INSERT INTO services (
                    name, description, url, http_method, expected_http_code,
                    timeout_seconds, warning_threshold_ms, check_interval_seconds, enabled
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id
            ");

            $stmt->execute([
                trim($data['name']),
                trim($data['description'] ?? ''),
                trim($data['url']),
                in_array(strtoupper($data['http_method'] ?? ''), ['GET', 'HEAD']) ? strtoupper($data['http_method']) : 'GET',
                (int)($data['expected_http_code'] ?? 200),
                max(1, (int)($data['timeout_seconds'] ?? 5)),
                max(100, (int)($data['warning_threshold_ms'] ?? 1000)),
                max(10, (int)($data['check_interval_seconds'] ?? 60)),
                isset($data['enabled']) ? (bool)$data['enabled'] : true
            ]);

            http_response_code(201);
            echo json_encode(['status' => 'success', 'id' => $stmt->fetchColumn()]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);

            if ($id <= 0 || empty($data['name']) || empty($data['url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Valid ID, Name, and URL are required.']);
                exit;
            }

            if (!isSafeUrl($data['url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or restricted URL.']);
                exit;
            }

            $stmt =$pdo->prepare("
                UPDATE services SET
                    name = ?, description = ?, url = ?, http_method = ?,
                    expected_http_code = ?, timeout_seconds = ?, warning_threshold_ms = ?,
                    check_interval_seconds = ?, enabled = ?
                WHERE id = ?
            ");

            $stmt->execute([
                trim($data['name']),
                trim($data['description'] ?? ''),
                trim($data['url']),
                in_array(strtoupper($data['http_method'] ?? ''), ['GET', 'HEAD']) ? strtoupper($data['http_method']) : 'GET',
                (int)($data['expected_http_code'] ?? 200),
                max(1, (int)($data['timeout_seconds'] ?? 5)),
                max(100, (int)($data['warning_threshold_ms'] ?? 1000)),
                max(10, (int)($data['check_interval_seconds'] ?? 60)), (bool)($data['enabled'] ?? true),
                $id
            ]);

            echo json_encode(['status' => 'success']);
            break;

        case 'DELETE':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid service ID']);
                exit;
            }

            $stmt =$pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

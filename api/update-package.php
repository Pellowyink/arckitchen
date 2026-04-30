<?php
/**
 * API: Update Package
 * Updates package details
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Package ID required']);
    exit;
}

// Extract and sanitize data
$name = trim($data['name'] ?? '');
$serves = trim($data['serves'] ?? '');
$description = trim($data['description'] ?? '');
$total_price = (float)($data['total_price'] ?? 0);
$is_active = (int)($data['is_active'] ?? 1);

if (empty($name) || $total_price <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and valid price are required']);
    exit;
}

$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$stmt = $connection->prepare(
    "UPDATE packages SET name = ?, serves = ?, description = ?, total_price = ?, is_active = ? WHERE id = ?"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare failed']);
    exit;
}

$stmt->bind_param('sssdis', $name, $serves, $description, $total_price, $is_active, $id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Package updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update package']);
}

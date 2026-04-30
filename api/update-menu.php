<?php
/**
 * API: Update Menu Item
 * Updates menu item details
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
    echo json_encode(['success' => false, 'message' => 'Menu item ID required']);
    exit;
}

// Extract and sanitize data
$name = trim($data['name'] ?? '');
$category = trim($data['category'] ?? '');
$description = trim($data['description'] ?? '');
$price = (float)($data['price'] ?? 0);
$is_active = (int)($data['is_active'] ?? 1);

if (empty($name) || empty($category) || $price <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name, category, and valid price are required']);
    exit;
}

$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$stmt = $connection->prepare(
    "UPDATE menu_items SET name = ?, category = ?, description = ?, price = ?, is_active = ? WHERE id = ?"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare failed']);
    exit;
}

$stmt->bind_param('sssdis', $name, $category, $description, $price, $is_active, $id);
$result = $stmt->execute();
$stmt->close();

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Menu item updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update menu item']);
}

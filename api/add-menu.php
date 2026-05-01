<?php
/**
 * API: Add Menu Item
 * Creates a new menu item and returns the created item data
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

// Extract and sanitize data
$name = trim($data['name'] ?? '');
$category = trim($data['category'] ?? '');
$description = trim($data['description'] ?? '');
$price = (float)($data['price'] ?? 0);
$is_active = (int)($data['is_active'] ?? 1);

// Validation
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Item name is required']);
    exit;
}

if (empty($category)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Category is required']);
    exit;
}

if ($price <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
    exit;
}

$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check for duplicate name
$checkStmt = $connection->prepare("SELECT id FROM menu_items WHERE name = ?");
$checkStmt->bind_param('s', $name);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'A menu item with this name already exists']);
    exit;
}
$checkStmt->close();

// Insert new menu item
$stmt = $connection->prepare(
    "INSERT INTO menu_items (name, category, description, price, image, is_active) VALUES (?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare failed']);
    exit;
}

$image = 'assets/images/food-placeholder.svg';
$stmt->bind_param('sssdss', $name, $category, $description, $price, $image, $is_active);
$result = $stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

if ($result) {
    // Get the newly created item
    $newItem = getMenuItem($newId, true);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Menu item added successfully',
        'item' => $newItem,
        'active_count' => countActiveMenuItems()
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to add menu item']);
}

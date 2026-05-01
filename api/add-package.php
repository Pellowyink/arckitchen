<?php
/**
 * API: Add Package
 * Creates a new package with items_json for selected menu items
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
$serves = trim($data['serves'] ?? '');
$description = trim($data['description'] ?? '');
$total_price = (float)($data['total_price'] ?? 0);
$is_active = (int)($data['is_active'] ?? 1);
$items = $data['items'] ?? []; // Array of menu item IDs

// Validation
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Package name is required']);
    exit;
}

if (empty($serves)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Serves count is required']);
    exit;
}

if ($total_price <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Total price must be greater than 0']);
    exit;
}

if (empty($items) || !is_array($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'At least one menu item must be selected']);
    exit;
}

$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check for duplicate name
$checkStmt = $connection->prepare("SELECT id FROM packages WHERE name = ?");
$checkStmt->bind_param('s', $name);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'A package with this name already exists']);
    exit;
}
$checkStmt->close();

// Prepare items_json
$items_json = json_encode($items);

// Insert new package
$stmt = $connection->prepare(
    "INSERT INTO packages (name, description, total_price, serves, is_active, items_json) VALUES (?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database prepare failed']);
    exit;
}

$stmt->bind_param('ssdiss', $name, $description, $total_price, $serves, $is_active, $items_json);
$result = $stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

if ($result) {
    // Insert package items into package_items table for relational integrity
    $itemStmt = $connection->prepare("INSERT INTO package_items (package_id, menu_item_id, quantity) VALUES (?, ?, 1)");
    foreach ($items as $menuItemId) {
        $menuItemId = (int)$menuItemId;
        $itemStmt->bind_param('ii', $newId, $menuItemId);
        $itemStmt->execute();
    }
    $itemStmt->close();
    
    // Get the newly created package
    $newPackage = getPackage($newId, true);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Package added successfully',
        'package' => $newPackage,
        'active_count' => countActivePackages()
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to add package']);
}

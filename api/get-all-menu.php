<?php
/**
 * API: Get All Menu Items (Including Inactive)
 * Returns all menu items for the package builder
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

$connection = getDbConnection();
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get all menu items including inactive for the builder
$sql = "SELECT id, name, category, description, price, is_active FROM menu_items ORDER BY category, name";
$result = $connection->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch menu items']);
    exit;
}

$items = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

// Group by category
$grouped = [];
foreach ($items as $item) {
    $cat = $item['category'];
    if (!isset($grouped[$cat])) {
        $grouped[$cat] = [];
    }
    $grouped[$cat][] = $item;
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'grouped' => $grouped,
    'total' => count($items)
]);

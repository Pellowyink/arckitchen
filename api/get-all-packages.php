<?php
/**
 * API: Get All Packages
 * Returns all packages for the menu picker
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$connection = getDbConnection();
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get all active packages
$sql = "SELECT id, name, description, total_price, is_active FROM packages WHERE is_active = 1 ORDER BY name";
$result = $connection->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch packages']);
    exit;
}

$packages = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

// Format packages for display
$formatted = [];
foreach ($packages as $pkg) {
    $formatted[] = [
        'id' => $pkg['id'],
        'name' => $pkg['name'],
        'description' => $pkg['description'],
        'price' => $pkg['total_price'],
        'is_package' => 1,
        'type' => 'package',
        'category' => 'Package'
    ];
}

echo json_encode([
    'success' => true,
    'items' => $formatted,
    'total' => count($formatted)
]);

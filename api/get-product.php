<?php
/**
 * API: Get Single Product
 * Returns menu item details as JSON
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;
$includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === '1';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$product = getMenuItem((int)$id, $includeInactive);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $product
]);

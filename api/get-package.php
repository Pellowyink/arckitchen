<?php
/**
 * API: Get Package Details
 * Returns package with items as JSON
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Package ID required']);
    exit;
}

$package = getPackage((int)$id);

if (!$package) {
    echo json_encode(['success' => false, 'message' => 'Package not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $package
]);

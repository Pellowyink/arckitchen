<?php
/**
 * API: Get Package Details
 * Returns package with items as JSON
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
$includeInactive = isset($_GET['include_inactive']) && $_GET['include_inactive'] === '1';

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Package ID required']);
    exit;
}

$package = getPackage($id, $includeInactive);

if (!$package) {
    echo json_encode(['success' => false, 'message' => 'Package not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $package
]);

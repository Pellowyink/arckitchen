<?php
/**
 * AJAX Endpoint to Update Inquiry
 * Handles: status updates, details updates
 * Security: Requires admin session
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
$inquiry_id = (int)($data['id'] ?? 0);

if ($inquiry_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
    exit;
}

$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Update inquiry with all fields
$stmt = $connection->prepare(
    "UPDATE inquiries SET event_date = ?, event_type = ?, guest_count = ?, items_json = ?, total_amount = ?, message = ?, updated_at = NOW() WHERE id = ?"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
    exit;
}

$event_date = $data['event_date'] ?? '';
$event_type = $data['event_type'] ?? '';
$guest_count = (int)($data['guest_count'] ?? 0);
$items_json = $data['items_json'] ?? '[]';
$total_amount = (float)($data['total_amount'] ?? 0);
$special_requests = $data['special_requests'] ?? '';

$stmt->bind_param('ssisdsi', $event_date, $event_type, $guest_count, $items_json, $total_amount, $special_requests, $inquiry_id);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Inquiry updated successfully']);
} else {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update inquiry']);
}

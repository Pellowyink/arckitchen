<?php
/**
 * AJAX Endpoint to Update Booking
 * Handles: status updates, items updates, details updates
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
$booking_id = (int)($data['id'] ?? 0);

if ($booking_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Update booking
$stmt = $connection->prepare(
    "UPDATE bookings SET event_date = ?, event_type = ?, guest_count = ?, 
     items_json = ?, total_amount = ?, special_requests = ?, status = ?, updated_at = NOW() 
     WHERE id = ?"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$event_date = $data['event_date'] ?? '';
$event_type = $data['event_type'] ?? '';
$guest_count = (int)($data['guest_count'] ?? 0);
$items_json = $data['items_json'] ?? '[]';
$total_amount = (float)($data['total_amount'] ?? 0);
$special_requests = $data['special_requests'] ?? '';
$status = $data['status'] ?? 'pending';

$stmt->bind_param(
    'ssiisssi',
    $event_date,
    $event_type,
    $guest_count,
    $items_json,
    $total_amount,
    $special_requests,
    $status,
    $booking_id
);

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
} else {
    $stmt->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update booking']);
}

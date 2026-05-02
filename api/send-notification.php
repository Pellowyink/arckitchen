<?php
/**
 * Send Quick Customer Notification
 * Handles "Ready for Pickup" and "On The Way" notifications
 */

require_once __DIR__ . '/../includes/functions.php';

// Set JSON headers
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$booking_id = (int)($data['booking_id'] ?? 0);
$type = $data['type'] ?? '';

if (!$booking_id || !in_array($type, ['ready_pickup', 'on_the_way'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

// Get booking details
$booking = getBookingById($booking_id);
if (!$booking) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

// Send notification email
$extraData = [];
if ($type === 'on_the_way') {
    $extraData['venue'] = $booking['venue'] ?? 'Your venue';
}

$result = sendCustomerNotification($type, $booking_id, $extraData);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Notification sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $result['message']]);
}

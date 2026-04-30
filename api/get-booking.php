<?php
/**
 * AJAX Endpoint to Get Booking Details
 * Returns: JSON with booking data + items + total + payment info
 * Security: Requires admin session
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

$booking_id = (int)($_GET['id'] ?? 0);

if ($booking_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

// Get booking from database
$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt = $connection->prepare("SELECT * FROM bookings WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

// Get items from items_json or inquiry
$items = [];
if (!empty($booking['items_json'])) {
    $items = json_decode($booking['items_json'], true) ?? [];
} elseif (!empty($booking['inquiry_id'])) {
    $items = getInquiryItems((int)$booking['inquiry_id']);
}

// Calculate total
$total = !empty($booking['total_amount']) ? (float)$booking['total_amount'] : calculateOrderTotal($items);

// Add payment fields if not present
if (!isset($booking['down_payment'])) $booking['down_payment'] = 0;
if (!isset($booking['full_payment'])) $booking['full_payment'] = 0;

// Calculate payment status
$totalPaid = (float)$booking['down_payment'] + (float)$booking['full_payment'];
$balance = $total - $totalPaid;

echo json_encode([
    'success' => true,
    'record' => $booking,
    'items' => $items,
    'total' => $total,
    'total_paid' => $totalPaid,
    'balance' => $balance,
    'payment_status' => $balance <= 0 ? 'fully_paid' : ($totalPaid > 0 ? 'partial' : 'pending')
]);

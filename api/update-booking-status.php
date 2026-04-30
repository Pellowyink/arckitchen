<?php
/**
 * AJAX Endpoint to Update Booking Status
 * Handles: status transitions (pending->confirmed->completed)
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
$status = $data['status'] ?? $data['action'] ?? '';  // Handle both status and action from payment calculator

if ($booking_id <= 0 || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'blocked'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Get payment data if provided (for confirmation)
$down_payment = isset($data['down_payment']) ? (float)$data['down_payment'] : null;
$full_payment = isset($data['full_payment']) ? (float)$data['full_payment'] : null;
$total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : null;

// If confirming or completing with payment data
if (($status === 'confirmed' || $status === 'completed') && ($down_payment !== null || $full_payment !== null)) {
    if (updateBookingStatusWithPayment($booking_id, $status, $down_payment, $full_payment, $total_amount)) {
        $message = $status === 'completed' ? 'Booking completed with payment recorded' : 'Booking confirmed with payment recorded';
        echo json_encode([
            'success' => true,
            'message' => $message,
            'new_status' => $status,
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status with payment']);
    }
} else {
    // Simple status update
    if (updateBookingStatus($booking_id, $status)) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking status updated to ' . $status,
            'new_status' => $status,
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
}

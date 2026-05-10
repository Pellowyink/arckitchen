<?php
/**
 * Soft Delete Booking API
 * Marks a booking as deleted with reason tracking
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

// Set timezone and error handling
date_default_timezone_set('Asia/Manila');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $bookingId = (int)($data['id'] ?? 0);
    $reason = sanitize($data['reason'] ?? 'No reason provided');
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if ($bookingId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit;
    }

    if ($adminId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin not authenticated']);
        exit;
    }

    // Perform soft delete
    $result = softDeleteBooking($bookingId, $adminId, $reason);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete booking'
        ]);
    }

} catch (Exception $e) {
    error_log("Soft delete booking exception: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the booking'
    ]);
}

ob_end_flush();
exit;

<?php
/**
 * Restore Booking API
 * Restores a soft-deleted booking
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

    // Perform restore
    $result = restoreBooking($bookingId, $adminId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Booking restored successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to restore booking'
        ]);
    }

} catch (Exception $e) {
    error_log("Restore booking exception: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while restoring the booking'
    ]);
}

ob_end_flush();
exit;

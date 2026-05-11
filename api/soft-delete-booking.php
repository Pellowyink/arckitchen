<?php
/**
 * Hard Delete Booking API
 * Permanently deletes a booking regardless of status.
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

function respondJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    if (ob_get_length() !== false) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondJson(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respondJson(['success' => false, 'message' => 'Invalid JSON request'], 400);
    }

    $bookingId = (int)($data['id'] ?? 0);
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if ($bookingId <= 0) {
        respondJson(['success' => false, 'message' => 'Invalid booking ID'], 400);
    }

    if ($adminId <= 0) {
        respondJson(['success' => false, 'message' => 'Admin not authenticated'], 401);
    }

    // Perform hard delete
    $result = hardDeleteBooking($bookingId);

    if ($result) {
        respondJson([
            'success' => true,
            'message' => 'Booking permanently deleted'
        ]);
    } else {
        respondJson([
            'success' => false,
            'message' => 'Failed to delete booking'
        ], 500);
    }

} catch (Throwable $e) {
    error_log("Soft delete booking exception: " . $e->getMessage());
    respondJson([
        'success' => false,
        'message' => 'An error occurred while deleting the booking'
    ], 500);
}

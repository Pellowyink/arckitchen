<?php
ob_start();

/**
 * Hard Delete Booking API
 * Permanently deletes any booking regardless of status.
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

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
    if ($bookingId <= 0) {
        respondJson(['success' => false, 'message' => 'Invalid booking ID'], 400);
    }

    if (!hardDeleteBooking($bookingId)) {
        respondJson(['success' => false, 'message' => 'Booking not found or could not be deleted'], 404);
    }

    respondJson([
        'success' => true,
        'message' => 'Booking permanently deleted'
    ]);
} catch (Throwable $e) {
    error_log("Hard delete booking exception: " . $e->getMessage());
    respondJson(['success' => false, 'message' => 'An error occurred while deleting the booking'], 500);
}

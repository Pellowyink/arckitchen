<?php
/**
 * Bulk Hard Delete Bookings API
 * Permanently deletes multiple bookings regardless of status.
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

    $bookingIds = $data['ids'] ?? [];
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if (empty($bookingIds) || !is_array($bookingIds)) {
        respondJson(['success' => false, 'message' => 'No booking IDs provided'], 400);
    }

    // Sanitize all IDs
    $bookingIds = array_map('intval', $bookingIds);
    $bookingIds = array_filter($bookingIds, function($id) { return $id > 0; });

    if (empty($bookingIds)) {
        respondJson(['success' => false, 'message' => 'Invalid booking IDs'], 400);
    }

    if ($adminId <= 0) {
        respondJson(['success' => false, 'message' => 'Admin not authenticated'], 401);
    }

    // Perform bulk hard delete
    $result = bulkHardDeleteBookings($bookingIds);

    if ($result['success']) {
        respondJson([
            'success' => true,
            'message' => $result['message'],
            'deleted_count' => $result['deleted_count']
        ]);
    } else {
        respondJson([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

} catch (Throwable $e) {
    error_log("Bulk delete bookings exception: " . $e->getMessage());
    respondJson([
        'success' => false,
        'message' => 'An error occurred while deleting bookings'
    ], 500);
}

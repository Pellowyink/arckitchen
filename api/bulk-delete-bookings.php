<?php
/**
 * Bulk Delete Bookings API
 * Soft deletes multiple bookings with reason tracking
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
    $bookingIds = $data['ids'] ?? [];
    $reason = sanitize($data['reason'] ?? 'Bulk delete operation');
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if (empty($bookingIds) || !is_array($bookingIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No booking IDs provided']);
        exit;
    }

    // Sanitize all IDs
    $bookingIds = array_map('intval', $bookingIds);
    $bookingIds = array_filter($bookingIds, function($id) { return $id > 0; });

    if (empty($bookingIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid booking IDs']);
        exit;
    }

    if ($adminId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin not authenticated']);
        exit;
    }

    // Perform bulk soft delete
    $result = bulkSoftDeleteBookings($bookingIds, $adminId, $reason);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'deleted_count' => $result['deleted_count']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    error_log("Bulk delete bookings exception: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting bookings'
    ]);
}

ob_end_flush();
exit;

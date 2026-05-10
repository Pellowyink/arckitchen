<?php
/**
 * Soft Delete Inquiry API
 * Marks an inquiry as deleted with reason tracking
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
    $inquiryId = (int)($data['id'] ?? 0);
    $reason = sanitize($data['reason'] ?? 'No reason provided');
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if ($inquiryId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
        exit;
    }

    if ($adminId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin not authenticated']);
        exit;
    }

    // Perform soft delete
    $result = softDeleteInquiry($inquiryId, $adminId, $reason);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Inquiry deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete inquiry'
        ]);
    }

} catch (Exception $e) {
    error_log("Soft delete inquiry exception: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the inquiry'
    ]);
}

ob_end_flush();
exit;

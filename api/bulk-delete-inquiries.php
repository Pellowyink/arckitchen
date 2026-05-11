<?php
/**
 * Bulk Hard Delete Inquiries API
 * Permanently deletes multiple inquiries regardless of status.
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

    $inquiryIds = $data['ids'] ?? [];
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if (empty($inquiryIds) || !is_array($inquiryIds)) {
        respondJson(['success' => false, 'message' => 'No inquiry IDs provided'], 400);
    }

    // Sanitize all IDs
    $inquiryIds = array_map('intval', $inquiryIds);
    $inquiryIds = array_filter($inquiryIds, function($id) { return $id > 0; });

    if (empty($inquiryIds)) {
        respondJson(['success' => false, 'message' => 'Invalid inquiry IDs'], 400);
    }

    if ($adminId <= 0) {
        respondJson(['success' => false, 'message' => 'Admin not authenticated'], 401);
    }

    // Perform bulk hard delete
    $result = bulkHardDeleteInquiries($inquiryIds);

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
    error_log("Bulk delete inquiries exception: " . $e->getMessage());
    respondJson([
        'success' => false,
        'message' => 'An error occurred while deleting inquiries'
    ], 500);
}

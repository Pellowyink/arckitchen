<?php
/**
 * AJAX Endpoint to Update Inquiry Status
 * Handles: Approval, Rejection transitions
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
$inquiry_id = (int)($data['id'] ?? 0);
$action = $data['action'] ?? '';

if ($inquiry_id <= 0 || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if ($action === 'approve') {
    if (approveInquiry($inquiry_id)) {
        echo json_encode(['success' => true, 'message' => 'Inquiry approved and booking created']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to approve inquiry']);
    }
} elseif ($action === 'reject') {
    if (rejectInquiry($inquiry_id)) {
        echo json_encode(['success' => true, 'message' => 'Inquiry rejected']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to reject inquiry']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

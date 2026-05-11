<?php
ob_start();

/**
 * AJAX Endpoint to Update Inquiry Status
 * Handles: Approval, Rejection transitions
 * Security: Requires admin session
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

    $inquiry_id = (int)($data['id'] ?? 0);
    $action = $data['action'] ?? '';

    if ($inquiry_id <= 0 || !$action) {
        respondJson(['success' => false, 'message' => 'Invalid request'], 400);
    }

    $down_payment = isset($data['down_payment']) ? (float)$data['down_payment'] : 0;
    $full_payment = isset($data['full_payment']) ? (float)$data['full_payment'] : 0;
    $total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0;

    $lastError = null;
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$lastError) {
        $lastError = "PHP Error: $errstr in $errfile:$errline";
        error_log($lastError);
        return true;
    });

    if ($action === 'approve') {
        $conn = getDbConnection();
        if (!$conn) {
            respondJson(['success' => false, 'message' => 'Database connection failed'], 500);
        }

        $checkStmt = $conn->prepare("SELECT id, status FROM inquiries WHERE id = ?");
        if (!$checkStmt) {
            respondJson(['success' => false, 'message' => 'Database error'], 500);
        }

        $checkStmt->bind_param('i', $inquiry_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $inquiryCheck = $checkResult->fetch_assoc();
        $checkStmt->close();

        if (!$inquiryCheck) {
            respondJson(['success' => false, 'message' => "Inquiry ID $inquiry_id not found in database"], 404);
        }

        if ($inquiryCheck['status'] !== 'pending') {
            respondJson(['success' => false, 'message' => "Inquiry status is '{$inquiryCheck['status']}', must be 'pending' to approve"], 400);
        }

        $result = approveInquiryWithPayment($inquiry_id, $down_payment, $full_payment, $total_amount);
        if (!$result) {
            $errorMsg = $lastError ?: 'Function returned false - check error logs';
            error_log("Failed to approve inquiry ID: $inquiry_id. Error: $errorMsg");
            respondJson(['success' => false, 'message' => $errorMsg], 500);
        }

        if (is_string($result)) {
            error_log("Failed to approve inquiry ID: $inquiry_id. Error: $result");
            respondJson(['success' => false, 'message' => $result], 500);
        }

        $bookingStmt = $conn->prepare("SELECT id FROM bookings WHERE inquiry_id = ? ORDER BY id DESC LIMIT 1");
        if (!$bookingStmt) {
            respondJson(['success' => false, 'message' => 'Inquiry approved, but booking lookup failed'], 500);
        }

        $bookingStmt->bind_param('i', $inquiry_id);
        $bookingStmt->execute();
        $bookingData = $bookingStmt->get_result()->fetch_assoc();
        $bookingStmt->close();

        if (!$bookingData) {
            respondJson(['success' => false, 'message' => 'Inquiry approved, but booking record was not found'], 500);
        }

        $bookingId = (int)$bookingData['id'];
        $amountPaidNow = $full_payment > 0 ? $full_payment : $down_payment;
        $emailResult = sendCustomerNotification('final_receipt', $bookingId, [
            'amount_paid_now' => $amountPaidNow,
        ]);

        if (!$emailResult['success']) {
            error_log("Failed to send payment receipt for booking #$bookingId from inquiry #$inquiry_id: " . $emailResult['message']);
        }

        respondJson([
            'success' => true,
            'message' => 'Inquiry approved and booking created with payment recorded',
            'booking_id' => $bookingId,
        ]);
    }

    if ($action === 'reject') {
        if (rejectInquiry($inquiry_id)) {
            respondJson(['success' => true, 'message' => 'Inquiry rejected']);
        }

        respondJson(['success' => false, 'message' => 'Failed to reject inquiry'], 500);
    }

    respondJson(['success' => false, 'message' => 'Invalid action'], 400);
} catch (Throwable $e) {
    error_log("update-inquiry-status fatal: " . $e->getMessage());
    respondJson(['success' => false, 'message' => 'Server error while updating inquiry status'], 500);
}

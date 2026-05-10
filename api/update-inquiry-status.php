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

// Get payment data if provided
$down_payment = isset($data['down_payment']) ? (float)$data['down_payment'] : 0;
$full_payment = isset($data['full_payment']) ? (float)$data['full_payment'] : 0;
$total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : 0;

// Enable error reporting for debugging - SHOW ERRORS DIRECTLY
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capture any errors and include in response
$lastError = null;
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$lastError) {
    $lastError = "PHP Error: $errstr in $errfile:$errline";
    error_log($lastError);
    return false;
});

if ($action === 'approve') {
    // Check if inquiry exists and is pending first
    $conn = getDbConnection();
    $checkStmt = $conn->prepare("SELECT id, status FROM inquiries WHERE id = ?");
    $checkStmt->bind_param('i', $inquiry_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $inquiryCheck = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    if (!$inquiryCheck) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => "Inquiry ID $inquiry_id not found in database"]);
        exit;
    }
    
    if ($inquiryCheck['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Inquiry status is '{$inquiryCheck['status']}', must be 'pending' to approve"]);
        exit;
    }
    
    // Approve inquiry with payment data
    try {
        $result = approveInquiryWithPayment($inquiry_id, $down_payment, $full_payment, $total_amount);
        if ($result) {
            // Send confirmation email to customer
            $conn = getDbConnection();
            $inquiryStmt = $conn->prepare("SELECT full_name, email, event_date FROM inquiries WHERE id = ?");
            $inquiryStmt->bind_param('i', $inquiry_id);
            $inquiryStmt->execute();
            $inquiryData = $inquiryStmt->get_result()->fetch_assoc();
            $inquiryStmt->close();
            
            if ($inquiryData) {
                // Get inquiry details including payment information
                $conn = getDbConnection();
                $detailStmt = $conn->prepare("
                    SELECT i.*, GROUP_CONCAT(
                        CONCAT(
                            '{\"name\":\"', REPLACE(mi.name, '\"', '\\\\\"'), '\",\"quantity\":', ii.quantity, ',\"unit_price\":', ii.unit_price, ',\"subtotal\":', ii.subtotal, ',\"is_package\":', IF(ii.is_package=1, 'true', 'false'), '}'
                        ) SEPARATOR ','
                    ) as items_json
                    FROM inquiries i
                    LEFT JOIN inquiry_items ii ON i.id = ii.inquiry_id
                    LEFT JOIN menu_items mi ON ii.menu_item_id = mi.id
                    WHERE i.id = ?
                    GROUP BY i.id
                ");
                $detailStmt->bind_param('i', $inquiry_id);
                $detailStmt->execute();
                $detailData = $detailStmt->get_result()->fetch_assoc();
                $detailStmt->close();

                $items = [];
                if ($detailData && $detailData['items_json']) {
                    $itemsJson = '[' . $detailData['items_json'] . ']';
                    $items = json_decode($itemsJson, true) ?: [];
                }

                $emailData = [
                    'customer_name' => $inquiryData['full_name'],
                    'booking_id' => $inquiry_id,
                    'event_date' => $inquiryData['event_date'],
                    'total_amount' => $total_amount,
                    'down_payment' => $down_payment,
                    'full_payment' => $full_payment,
                    'items' => $items
                ];
                
                $emailResult = sendArcEmail(
                    $inquiryData['email'],
                    'Your Order is Confirmed! - Arc Kitchen',
                    'inquiry_confirmed',
                    $emailData
                );
                
                if (!$emailResult['success']) {
                    error_log("Failed to send confirmation email for inquiry #$inquiry_id: " . $emailResult['message']);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Inquiry approved and booking created with payment recorded']);
        } else {
            $errorMsg = $lastError ?: 'Function returned false - check error logs';
            error_log("Failed to approve inquiry ID: $inquiry_id. Error: $errorMsg");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
    } catch (Exception $e) {
        $errorMsg = 'Exception: ' . $e->getMessage();
        error_log("Exception in inquiry approval: " . $errorMsg);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    } catch (Error $e) {
        $errorMsg = 'Fatal Error: ' . $e->getMessage();
        error_log("Fatal Error in inquiry approval: " . $errorMsg);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $errorMsg]);
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

<?php
/**
 * AJAX Endpoint to Update Booking Status
 * Handles: status transitions (pending->confirmed->completed->in-progress)
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
$booking_id = (int)($data['id'] ?? 0);
$status = $data['status'] ?? $data['action'] ?? '';

if ($booking_id <= 0 || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate status (now includes 'in-progress')
$valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'blocked', 'in-progress'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Get ETA if provided (for in-progress status)
$eta = isset($data['eta']) ? trim($data['eta']) : null;

// Get payment data if provided (for confirmation)
$down_payment = isset($data['down_payment']) ? (float)$data['down_payment'] : null;
$full_payment = isset($data['full_payment']) ? (float)$data['full_payment'] : null;
$total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : null;

// Handle in-progress with ETA
if ($status === 'in-progress') {
    $conn = getDbConnection();
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Auto-add eta column if it doesn't exist
    $checkColumn = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'eta'");
    if ($checkColumn->num_rows === 0) {
        $conn->query("ALTER TABLE inquiries ADD COLUMN eta VARCHAR(100) DEFAULT NULL AFTER status");
    }
    
    // Update status and save ETA
    $sql = "UPDATE inquiries SET status = ?, eta = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ssi", $status, $eta, $booking_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Send in-progress email notification
            $extraData = [];
            if ($eta) {
                $extraData['eta'] = $eta;
            }
            
            $emailResult = sendCustomerNotification('in_progress', $booking_id, $extraData);
            
            if (!$emailResult['success']) {
                error_log("Failed to send in-progress email for booking #$booking_id: " . $emailResult['message']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking marked as in-progress' . ($eta ? ' with ETA: ' . $eta : ''),
                'new_status' => $status,
            ]);
        } else {
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// Function to auto-archive completed bookings
function autoArchiveCompleted($booking_id) {
    $conn = getDbConnection();
    if (!$conn) return false;
    
    // Check if archived_at column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'archived_at'");
    if ($checkColumn->num_rows === 0) {
        $conn->query("ALTER TABLE inquiries ADD COLUMN archived_at DATETIME DEFAULT NULL AFTER updated_at");
    }
    
    // Archive the booking
    $sql = "UPDATE inquiries SET archived_at = NOW() WHERE id = ? AND archived_at IS NULL";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}

// If confirming or completing with payment data
if (($status === 'confirmed' || $status === 'completed') && ($down_payment !== null || $full_payment !== null)) {
    if (updateBookingStatusWithPayment($booking_id, $status, $down_payment, $full_payment, $total_amount)) {
        // Auto-archive if completed and send Final Receipt
        if ($status === 'completed') {
            autoArchiveCompleted($booking_id);
            
            // Get booking details for Final Receipt
            $booking = getBookingById($booking_id);
            if ($booking) {
                // Parse items from JSON
                $items = [];
                if (!empty($booking['items_json'])) {
                    $items = json_decode($booking['items_json'], true) ?: [];
                }
                
                // Calculate total paid
                $totalPaid = (float)($booking['down_payment'] ?? 0) + (float)($booking['full_payment'] ?? 0);
                
                $receiptData = [
                    'customer_name' => $booking['customer_name'],
                    'booking_id' => $booking_id,
                    'event_date' => $booking['event_date'],
                    'event_time' => $booking['event_time'] ?? '',
                    'event_location' => $booking['event_location'] ?? ($booking['venue'] ?? ''),
                    'total_amount' => $booking['total_amount'],
                    'total_paid' => $totalPaid,
                    'items' => $items
                ];
                
                $emailResult = sendCustomerNotification('final_receipt', $booking_id, $receiptData);
                if (!$emailResult['success']) {
                    error_log("Failed to send final receipt for booking #$booking_id: " . $emailResult['message']);
                }
            }
        }
        
        $message = $status === 'completed' ? 'Booking completed and archived to Sales Report. Final receipt sent to customer.' : 'Booking confirmed with payment recorded';
        echo json_encode([
            'success' => true,
            'message' => $message,
            'new_status' => $status,
            'auto_archived' => $status === 'completed'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status with payment']);
    }
} else {
    // Simple status update
    if (updateBookingStatus($booking_id, $status)) {
        // Auto-archive if completed
        if ($status === 'completed') {
            autoArchiveCompleted($booking_id);
            
            $emailResult = sendCustomerNotification('completed', $booking_id);
            if (!$emailResult['success']) {
                error_log("Failed to send completion email for booking #$booking_id: " . $emailResult['message']);
            }
        }
        
        $message = $status === 'completed' ? 'Booking completed and archived to Sales Report' : 'Booking status updated to ' . $status;
        echo json_encode([
            'success' => true,
            'message' => $message,
            'new_status' => $status,
            'auto_archived' => $status === 'completed'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
}

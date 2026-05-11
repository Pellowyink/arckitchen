<?php
ob_start();

/**
 * AJAX Endpoint to Update Booking Status
 * Handles: status transitions (pending->confirmed->completed->in-progress)
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

function autoArchiveCompleted($booking_id) {
    $conn = getDbConnection();
    if (!$conn) {
        error_log("autoArchiveCompleted: No database connection");
        return false;
    }

    $checkColumn = $conn->query("SHOW COLUMNS FROM bookings LIKE 'archived_at'");
    if ($checkColumn && $checkColumn->num_rows === 0) {
        error_log("autoArchiveCompleted: Adding archived_at column to bookings table");
        $conn->query("ALTER TABLE bookings ADD COLUMN archived_at DATETIME DEFAULT NULL AFTER updated_at");
    }

    $sql = "UPDATE bookings SET archived_at = NOW() WHERE id = ? AND archived_at IS NULL";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $booking_id);
        $result = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        error_log("autoArchiveCompleted: booking_id=$booking_id, result=$result, affected_rows=$affectedRows");
        $stmt->close();
        return $result && $affectedRows > 0;
    }

    error_log("autoArchiveCompleted: Prepare failed: " . $conn->error);
    return false;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondJson(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respondJson(['success' => false, 'message' => 'Invalid JSON request'], 400);
    }

    $booking_id = (int)($data['id'] ?? 0);
    $status = $data['status'] ?? $data['action'] ?? '';

    if ($booking_id <= 0 || !$status) {
        respondJson(['success' => false, 'message' => 'Invalid request'], 400);
    }

    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'blocked', 'in-progress'];
    if (!in_array($status, $valid_statuses, true)) {
        respondJson(['success' => false, 'message' => 'Invalid status'], 400);
    }

    $eta = isset($data['eta']) ? trim($data['eta']) : null;
    $down_payment = isset($data['down_payment']) ? (float)$data['down_payment'] : null;
    $full_payment = isset($data['full_payment']) ? (float)$data['full_payment'] : null;
    $total_amount = isset($data['total_amount']) ? (float)$data['total_amount'] : null;

    error_log("update-booking-status: booking_id=$booking_id, status=$status, down_payment=$down_payment, full_payment=$full_payment, total_amount=$total_amount");

    if ($status === 'in-progress') {
        $conn = getDbConnection();
        if (!$conn) {
            respondJson(['success' => false, 'message' => 'Database connection failed'], 500);
        }

        $checkColumn = $conn->query("SHOW COLUMNS FROM inquiries LIKE 'eta'");
        if ($checkColumn && $checkColumn->num_rows === 0) {
            $conn->query("ALTER TABLE inquiries ADD COLUMN eta VARCHAR(100) DEFAULT NULL AFTER status");
        }

        $sql = "UPDATE inquiries SET status = ?, eta = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            respondJson(['success' => false, 'message' => 'Database error'], 500);
        }

        $stmt->bind_param("ssi", $status, $eta, $booking_id);
        $updated = $stmt->execute();
        $stmt->close();

        if (!$updated) {
            respondJson(['success' => false, 'message' => 'Failed to update status'], 500);
        }

        $extraData = [];
        if ($eta) {
            $extraData['eta'] = $eta;
        }

        $emailResult = sendCustomerNotification('in_progress', $booking_id, $extraData);
        if (!$emailResult['success']) {
            error_log("Failed to send in-progress email for booking #$booking_id: " . $emailResult['message']);
        }

        respondJson([
            'success' => true,
            'message' => 'Booking marked as in-progress' . ($eta ? ' with ETA: ' . $eta : ''),
            'new_status' => $status,
        ]);
    }

    if (($status === 'confirmed' || $status === 'completed') && ($down_payment !== null || $full_payment !== null)) {
        if (!updateBookingStatusWithPayment($booking_id, $status, $down_payment, $full_payment, $total_amount)) {
            respondJson(['success' => false, 'message' => 'Failed to update status with payment'], 500);
        }

        if ($status === 'completed') {
            autoArchiveCompleted($booking_id);
        }

        $amountPaidNow = $full_payment !== null && $full_payment > 0 ? $full_payment : (float)($down_payment ?? 0);
        $emailResult = sendCustomerNotification('final_receipt', $booking_id, [
            'amount_paid_now' => $amountPaidNow,
        ]);

        if (!$emailResult['success']) {
            error_log("Failed to send payment receipt for booking #$booking_id: " . $emailResult['message']);
        }

        respondJson([
            'success' => true,
            'message' => $status === 'completed' ? 'Booking completed and archived to Sales Report. Final receipt sent to customer.' : 'Booking confirmed with payment recorded. Receipt sent to customer.',
            'new_status' => $status,
            'auto_archived' => $status === 'completed'
        ]);
    }

    if (!updateBookingStatus($booking_id, $status)) {
        respondJson(['success' => false, 'message' => 'Failed to update status'], 500);
    }

    if ($status === 'completed') {
        autoArchiveCompleted($booking_id);

        $emailResult = sendCustomerNotification('completed', $booking_id);
        if (!$emailResult['success']) {
            error_log("Failed to send completion email for booking #$booking_id: " . $emailResult['message']);
        }
    }

    respondJson([
        'success' => true,
        'message' => $status === 'completed' ? 'Booking completed and archived to Sales Report' : 'Booking status updated to ' . $status,
        'new_status' => $status,
        'auto_archived' => $status === 'completed'
    ]);
} catch (Throwable $e) {
    error_log("update-booking-status fatal: " . $e->getMessage());
    respondJson(['success' => false, 'message' => 'Server error while updating booking status'], 500);
}

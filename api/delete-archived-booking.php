<?php
/**
 * Delete Archived Booking API
 * Permanently deletes an archived booking from the database
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

if ($booking_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$conn = getDbConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Only allow deletion of archived bookings
$sql = "DELETE FROM inquiries WHERE id = ? AND archived_at IS NOT NULL";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $booking_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    echo json_encode([
        'success' => true,
        'message' => 'Archived booking deleted permanently'
    ]);
} else {
    $stmt->close();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Booking not found or not archived'
    ]);
}

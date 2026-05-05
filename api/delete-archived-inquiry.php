<?php
/**
 * Delete Archived Inquiry API
 * Permanently deletes an archived inquiry from the database
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

if ($inquiry_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
    exit;
}

$conn = getDbConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Only allow deletion of archived inquiries
$sql = "DELETE FROM inquiries WHERE id = ? AND archived_at IS NOT NULL";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $inquiry_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $stmt->close();
    echo json_encode([
        'success' => true,
        'message' => 'Archived inquiry deleted permanently'
    ]);
} else {
    $stmt->close();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Inquiry not found or not archived'
    ]);
}

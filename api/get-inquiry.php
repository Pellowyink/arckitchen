<?php
/**
 * AJAX Endpoint to Get Inquiry Details
 * Returns: JSON with inquiry data + items + total
 * Security: Requires admin session
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

$inquiry_id = (int)($_GET['id'] ?? 0);

if ($inquiry_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
    exit;
}

// Get inquiry from database
$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt = $connection->prepare("SELECT * FROM inquiries WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $inquiry_id);
$stmt->execute();
$result = $stmt->get_result();
$inquiry = $result->fetch_assoc();
$stmt->close();

if (!$inquiry) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
    exit;
}

// Get inquiry items
$items = getInquiryItems($inquiry_id);

// Calculate total
$total = calculateOrderTotal($items);

// Add payment fields if not present
if (!isset($inquiry['down_payment'])) $inquiry['down_payment'] = 0;
if (!isset($inquiry['full_payment'])) $inquiry['full_payment'] = 0;

// Calculate payment status
$totalPaid = (float)$inquiry['down_payment'] + (float)$inquiry['full_payment'];
$balance = $total - $totalPaid;

echo json_encode([
    'success' => true,
    'record' => $inquiry,
    'items' => $items,
    'total' => $total,
    'total_paid' => $totalPaid,
    'balance' => $balance,
    'payment_status' => $balance <= 0 ? 'fully_paid' : ($totalPaid > 0 ? 'partial' : 'pending')
]);

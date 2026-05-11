<?php
/**
 * Get Order Details by Date API
 * Returns order details for a specific event date
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$date = $_GET['date'] ?? '';

if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

$conn = getDbConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get booking details for the date
$sql = "SELECT 
    b.id,
    b.customer_name,
    b.customer_email,
    b.customer_phone,
    b.event_date,
    b.event_type,
    b.guest_count,
    b.total_amount,
    b.payment_status,
    b.down_payment,
    b.full_payment,
    b.special_requests,
    b.status,
    b.created_at
FROM inquiries b 
WHERE b.event_date = ? 
AND (b.status = 'confirmed' OR b.status = 'completed' OR b.status = 'pending')
ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    // Calculate payment info
    $total = (float)($row['total_amount'] ?? 0);
    $downPayment = (float)($row['down_payment'] ?? 0);
    $fullPayment = (float)($row['full_payment'] ?? 0);
    $paid = $downPayment + $fullPayment;
    $balance = max(0, $total - $paid);
    
    $row['calculated_balance'] = $balance;
    $row['calculated_paid'] = $paid;
    
    // Format payment status
    $paymentStatus = $row['payment_status'] ?? 'pending';
    $row['payment_status_display'] = getPaymentStatusDisplay($paymentStatus);
    
    // Get order items if available
    $row['order_items'] = getOrderItems($row['id'], $conn);
    
    $bookings[] = $row;
}

$stmt->close();

$dateSetting = getCalendarSettingByDate($date);
$capacityNote = $dateSetting['admin_note'] ?? null;
$overallStatus = getCalendarAvailabilityClass($dateSetting);

echo json_encode([
    'success' => true,
    'date' => $date,
    'status' => $overallStatus,
    'capacity_note' => $capacityNote,
    'max_slots' => (int)($dateSetting['max_slots'] ?? 3),
    'current_slots' => (int)($dateSetting['current_slots'] ?? count($bookings)),
    'booking_count' => count($bookings),
    'bookings' => $bookings
]);

function getPaymentStatusDisplay($status) {
    switch ($status) {
        case 'fully_paid':
            return ['label' => 'Fully Paid', 'badge' => 'badge-success', 'color' => '#4CAF50'];
        case 'partial':
            return ['label' => 'Partial Payment', 'badge' => 'badge-warning', 'color' => '#FF9800'];
        default:
            return ['label' => 'Payment Pending', 'badge' => 'badge-pending', 'color' => '#9e9e9e'];
    }
}

function getOrderItems($bookingId, $conn) {
    // Try to get items from a related table if it exists
    $items = [];
    
    // Check if order_items table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'order_items'");
    if ($checkTable->num_rows > 0) {
        $itemSql = "SELECT item_name, quantity, unit_price, total_price 
                    FROM order_items 
                    WHERE booking_id = ?";
        $itemStmt = $conn->prepare($itemSql);
        $itemStmt->bind_param("i", $bookingId);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        while ($itemRow = $itemResult->fetch_assoc()) {
            $items[] = $itemRow;
        }
        $itemStmt->close();
    }
    
    // If no items found, return placeholder based on event type
    if (empty($items)) {
        $items[] = [
            'item_name' => 'Catering Package',
            'quantity' => 1,
            'unit_price' => 0,
            'total_price' => 0,
            'placeholder' => true
        ];
    }
    
    return $items;
}

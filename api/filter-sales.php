<?php
/**
 * Filter Sales API
 * Returns filtered sales data with metrics for dynamic updates
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build date condition
$dateCondition = "";
$params = [];
$types = "";

switch ($filter) {
    case 'weekly':
        $dateCondition = "AND event_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'monthly':
        $dateCondition = "AND MONTH(event_date) = MONTH(CURRENT_DATE()) AND YEAR(event_date) = YEAR(CURRENT_DATE())";
        break;
    case 'yearly':
        $dateCondition = "AND YEAR(event_date) = YEAR(CURRENT_DATE())";
        break;
    case 'custom':
        if ($dateFrom && $dateTo) {
            $dateCondition = "AND event_date BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $types .= "ss";
        }
        break;
    default:
        $dateCondition = "";
}

// Get filtered bookings from bookings table (NOT inquiries!)
$conn = getDbConnection();
$sql = "SELECT * FROM bookings 
        WHERE status = 'completed' 
        $dateCondition 
        ORDER BY event_date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("filter-sales: Prepare failed: " . $conn->error);
    error_log("filter-sales: SQL: $sql");
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$executeResult = $stmt->execute();
if (!$executeResult) {
    error_log("filter-sales: Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();
error_log("filter-sales: Query executed, row count: " . ($result ? $result->num_rows : 0));

$bookings = [];
$totalRevenue = 0;
$totalCollected = 0;
$totalBalance = 0;
$totalDownPayments = 0;
$totalFullPayments = 0;
$fullyPaidCount = 0;
$partialPaidCount = 0;
$pendingPaymentCount = 0;

while ($row = $result->fetch_assoc()) {
    $total = (float)($row['total_amount'] ?? 0);
    $downPayment = (float)($row['down_payment'] ?? 0);
    $fullPayment = (float)($row['full_payment'] ?? 0);
    $paid = $downPayment + $fullPayment;
    $balance = max(0, $total - $paid);
    
    $row['calculated_balance'] = $balance;
    $row['calculated_paid'] = $paid;
    
    $paymentStatus = $row['payment_status'] ?? 'pending';
    $row['payment_status_label'] = getPaymentStatusLabel($paymentStatus);
    
    $bookings[] = $row;
    
    $totalRevenue += $total;
    $totalCollected += $paid;
    $totalBalance += $balance;
    $totalDownPayments += $downPayment;
    $totalFullPayments += $fullPayment;
    
    if ($paymentStatus === 'fully_paid') {
        $fullyPaidCount++;
    } elseif ($paymentStatus === 'partial') {
        $partialPaidCount++;
    } else {
        $pendingPaymentCount++;
    }
}

// Get comparative data (previous period)
$prevRevenue = 0;
$prevCollected = 0;
$growthPercent = 0;

if ($filter === 'monthly') {
    $prevSql = "SELECT SUM(total_amount) as prev_revenue, 
                SUM(COALESCE(down_payment, 0) + COALESCE(full_payment, 0)) as prev_collected
                FROM bookings 
                WHERE status = 'completed'
                AND MONTH(event_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                AND YEAR(event_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
    $prevResult = $conn->query($prevSql);
    if ($prevRow = $prevResult->fetch_assoc()) {
        $prevRevenue = (float)($prevRow['prev_revenue'] ?? 0);
        $prevCollected = (float)($prevRow['prev_collected'] ?? 0);
    }
}

if ($prevRevenue > 0) {
    $growthPercent = round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1);
}

// Return response
echo json_encode([
    'success' => true,
    'filter' => $filter,
    'metrics' => [
        'total_revenue' => $totalRevenue,
        'total_collected' => $totalCollected,
        'total_balance' => $totalBalance,
        'total_down_payments' => $totalDownPayments,
        'total_full_payments' => $totalFullPayments,
        'fully_paid_count' => $fullyPaidCount,
        'partial_paid_count' => $partialPaidCount,
        'pending_payment_count' => $pendingPaymentCount,
        'growth_percent' => $growthPercent,
        'booking_count' => count($bookings)
    ],
    'bookings' => $bookings
]);

function getPaymentStatusLabel($status) {
    switch ($status) {
        case 'fully_paid':
            return ['label' => 'Fully Paid', 'badge' => 'badge-success', 'icon' => '✅'];
        case 'partial':
            return ['label' => 'Partial', 'badge' => 'badge-warning', 'icon' => '💳'];
        default:
            return ['label' => 'Pending', 'badge' => 'badge-pending', 'icon' => '⏳'];
    }
}

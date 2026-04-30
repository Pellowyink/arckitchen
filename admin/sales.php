<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

// Get bookings with payment data
$bookings = getBookings();

// Calculate comprehensive sales stats
$totalRevenue = 0;
$totalDownPayments = 0;
$totalFullPayments = 0;
$totalBalance = 0;
$confirmedEvents = 0;
$completedEvents = 0;
$fullyPaidCount = 0;
$partialPaidCount = 0;
$pendingPaymentCount = 0;

foreach ($bookings as $booking) {
    $total = (float)($booking['total_amount'] ?? 0);
    $downPayment = (float)($booking['down_payment'] ?? 0);
    $fullPayment = (float)($booking['full_payment'] ?? 0);
    $paid = $downPayment + $fullPayment;
    $balance = $total - $paid;
    
    $totalRevenue += $total;
    $totalDownPayments += $downPayment;
    $totalFullPayments += $fullPayment;
    $totalBalance += max(0, $balance);
    
    if ($booking['status'] === 'confirmed') {
        $confirmedEvents++;
    }
    if ($booking['status'] === 'completed') {
        $completedEvents++;
    }
    
    $paymentStatus = $booking['payment_status'] ?? 'pending';
    if ($paymentStatus === 'fully_paid') {
        $fullyPaidCount++;
    } elseif ($paymentStatus === 'partial') {
        $partialPaidCount++;
    } else {
        $pendingPaymentCount++;
    }
}

$totalCollected = $totalDownPayments + $totalFullPayments;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">💰 Sales Report</h1>
            </div>

            <!-- Payment Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">�</div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💵</div>
                    <div class="stat-label">Total Collected</div>
                    <div class="stat-value" style="color: #4CAF50;"><?php echo formatCurrency($totalCollected); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">�</div>
                    <div class="stat-label">Pending Balance</div>
                    <div class="stat-value" style="color: #f44336;"><?php echo formatCurrency($totalBalance); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-label">Fully Paid</div>
                    <div class="stat-value" style="color: #4CAF50;"><?php echo $fullyPaidCount; ?></div>
                </div>
            </div>

            <!-- Payment Breakdown -->
            <div class="admin-card">
                <h2>💳 Payment Breakdown</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                    <div class="payment-stat-box">
                        <div class="payment-stat-label">Down Payments</div>
                        <div class="payment-stat-value"><?php echo formatCurrency($totalDownPayments); ?></div>
                    </div>
                    <div class="payment-stat-box">
                        <div class="payment-stat-label">Full Payments</div>
                        <div class="payment-stat-value"><?php echo formatCurrency($totalFullPayments); ?></div>
                    </div>
                    <div class="payment-stat-box">
                        <div class="payment-stat-label">Partially Paid</div>
                        <div class="payment-stat-value" style="color: #FF9800;"><?php echo $partialPaidCount; ?> bookings</div>
                    </div>
                    <div class="payment-stat-box">
                        <div class="payment-stat-label">Payment Pending</div>
                        <div class="payment-stat-value" style="color: #9e9e9e;"><?php echo $pendingPaymentCount; ?> bookings</div>
                    </div>
                </div>
            </div>

            <!-- Payment Status Summary -->
            <div class="admin-card">
                <h2>📊 Payment Status Overview</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Payment Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-success">✅ Fully Paid</span></td>
                            <td><?php echo $fullyPaidCount; ?></td>
                            <td><?php echo count($bookings) > 0 ? round(($fullyPaidCount / count($bookings)) * 100, 1) : 0; ?>%</td>
                        </tr>
                        <tr>
                            <td><span class="badge" style="background: #FF9800;">💳 Partial</span></td>
                            <td><?php echo $partialPaidCount; ?></td>
                            <td><?php echo count($bookings) > 0 ? round(($partialPaidCount / count($bookings)) * 100, 1) : 0; ?>%</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-pending">⏳ Pending</span></td>
                            <td><?php echo $pendingPaymentCount; ?></td>
                            <td><?php echo count($bookings) > 0 ? round(($pendingPaymentCount / count($bookings)) * 100, 1) : 0; ?>%</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Recent Transactions -->
            <div class="admin-card">
                <h2>📋 Recent Bookings with Payments</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Event Date</th>
                                <th>Total Cost</th>
                                <th>Down Payment</th>
                                <th>Full Payment</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Show last 10 bookings with payment data
                            $recentBookings = array_slice($bookings, 0, 10);
                            foreach ($recentBookings as $booking): 
                                $total = (float)($booking['total_amount'] ?? 0);
                                $downPayment = (float)($booking['down_payment'] ?? 0);
                                $fullPayment = (float)($booking['full_payment'] ?? 0);
                                $paid = $downPayment + $fullPayment;
                                $balance = max(0, $total - $paid);
                                $paymentStatus = $booking['payment_status'] ?? 'pending';
                                $statusInfo = getPaymentStatusInfo($paymentStatus);
                            ?>
                            <tr>
                                <td><strong><?php echo escape($booking['customer_name'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo !empty($booking['event_date']) ? date('M d, Y', strtotime($booking['event_date'])) : '--'; ?></td>
                                <td><?php echo formatCurrency($total); ?></td>
                                <td style="color: #4CAF50;"><?php echo $downPayment > 0 ? formatCurrency($downPayment) : '-'; ?></td>
                                <td style="color: #4CAF50;"><?php echo $fullPayment > 0 ? formatCurrency($fullPayment) : '-'; ?></td>
                                <td style="color: <?php echo $balance > 0 ? '#f44336' : '#4CAF50'; ?>;">
                                    <?php echo $balance > 0 ? formatCurrency($balance) : 'PAID'; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['icon'] . ' ' . $statusInfo['label']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentBookings)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #888;">No bookings found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <style>
                .payment-stat-box {
                    background: #f9f7f4;
                    padding: 1rem;
                    border-radius: 10px;
                    text-align: center;
                }
                .payment-stat-label {
                    color: #888;
                    font-size: 0.85rem;
                    margin-bottom: 0.5rem;
                }
                .payment-stat-value {
                    font-size: 1.3rem;
                    font-weight: 700;
                    color: #4a1414;
                }
            </style>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
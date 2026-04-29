<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

// Calculate sales stats
$bookings = getInquiries();
$total_revenue = 0;
$confirmed_events = 0;

foreach ($bookings as $booking) {
    if ($booking['status'] === 'Confirmed') {
        $confirmed_events++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-shell">
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">💰 Sales Report</h1>
            </div>

            <!-- Sales Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-label">Total Bookings</div>
                    <div class="stat-value"><?php echo count($bookings); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-label">Confirmed Events</div>
                    <div class="stat-value"><?php echo $confirmed_events; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Total Guests</div>
                    <div class="stat-value">
                        <?php 
                        $total_guests = array_reduce($bookings, function($sum, $booking) {
                            return $sum + (int)$booking['guest_count'];
                        }, 0);
                        echo $total_guests;
                        ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-value">
                        <?php 
                        echo count(array_filter($bookings, function($b) {
                            return $b['status'] === 'Pending';
                        }));
                        ?>
                    </div>
                </div>
            </div>

            <!-- Bookings by Status -->
            <div class="admin-card">
                <h2>Bookings by Status</h2>
                <?php 
                $status_counts = array_count_values(array_column($bookings, 'status'));
                ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($status_counts as $status => $count): ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?php echo strtolower($status); ?>">
                                    <?php echo escape($status); ?>
                                </span>
                            </td>
                            <td><?php echo $count; ?></td>
                            <td><?php echo round(($count / count($bookings)) * 100, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
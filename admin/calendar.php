<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

$bookings = getInquiries();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - ARC Kitchen Admin</title>
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
                <h1 class="admin-title">📆 Event Calendar</h1>
            </div>

            <!-- Calendar Card -->
            <div class="admin-card">
                <h2>Event Schedule</h2>
                <p style="color: var(--text-soft); margin-top: 1rem; text-align: center; padding: 2rem;">
                    Calendar visualization coming soon. 
                    <br>
                    <small>In the meantime, view bookings in the <strong>Bookings</strong> section.</small>
                </p>
                
                <?php if (!empty($bookings)): ?>
                    <table class="admin-table" style="margin-top: 1.5rem;">
                        <thead>
                            <tr>
                                <th>Event Date</th>
                                <th>Customer</th>
                                <th>Guest Count</th>
                                <th>Event Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><strong><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></strong></td>
                                <td><?php echo escape($booking['full_name']); ?></td>
                                <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                <td><?php echo escape($booking['event_type']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo escape($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
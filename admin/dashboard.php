<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

// Get dashboard counters using state-machine logic
$pending_inquiries = countPendingInquiries();
$confirmed_bookings = countConfirmedBookings();
$completed_bookings = countCompletedBookings();
$active_packages = countActivePackages();
$active_menu_items = countActiveMenuItems();

// Get recent data for preview
$recent_inquiries = getInquiriesFiltered(['status' => 'pending']);
$recent_bookings = getBookings(['status' => 'confirmed']);
$recent_messages = getContactMessages();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ARC Kitchen Admin</title>
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
                <h1 class="admin-title">📊 Dashboard Overview</h1>
                <p class="admin-subtitle">Real-time system status and key metrics</p>
            </div>

            <!-- ========================================
                 STATE MACHINE STATUS CARDS (5 Cards)
                 ======================================== -->
            <div class="stats-grid">
                <!-- Card 1: Pending Inquiries -->
                <div class="stat-card stat-card-inquiries">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <div class="stat-label">Pending Inquiries</div>
                        <div class="stat-value"><?php echo $pending_inquiries; ?></div>
                        <div class="stat-subtitle">Awaiting approval</div>
                    </div>
                    <a href="inquiries.php?status=pending" class="stat-link">View →</a>
                </div>

                <!-- Card 2: Confirmed Bookings -->
                <div class="stat-card stat-card-confirmed">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-label">Confirmed Bookings</div>
                        <div class="stat-value"><?php echo $confirmed_bookings; ?></div>
                        <div class="stat-subtitle">Ready for execution</div>
                    </div>
                    <a href="bookings.php?status=confirmed" class="stat-link">View →</a>
                </div>

                <!-- Card 3: Completed Bookings -->
                <div class="stat-card stat-card-completed">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-content">
                        <div class="stat-label">Completed Bookings</div>
                        <div class="stat-value"><?php echo $completed_bookings; ?></div>
                        <div class="stat-subtitle">Successfully delivered</div>
                    </div>
                    <a href="bookings.php?status=completed" class="stat-link">View →</a>
                </div>

                <!-- Card 4: Active Packages -->
                <div class="stat-card stat-card-packages">
                    <div class="stat-icon">📦</div>
                    <div class="stat-content">
                        <div class="stat-label">Active Packages</div>
                        <div class="stat-value"><?php echo $active_packages; ?></div>
                        <div class="stat-subtitle">Available for booking</div>
                    </div>
                    <a href="packages.php" class="stat-link">Manage →</a>
                </div>

                <!-- Card 5: Active Menu Items -->
                <div class="stat-card stat-card-menu">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-content">
                        <div class="stat-label">Active Menu Items</div>
                        <div class="stat-value"><?php echo $active_menu_items; ?></div>
                        <div class="stat-subtitle">In service catalog</div>
                    </div>
                    <a href="menu-manager.php" class="stat-link">Manage →</a>
                </div>
            </div>

            <!-- ========================================
                 RECENT PENDING INQUIRIES
                 ======================================== -->
            <div class="admin-card">
                <div class="card-header">
                    <h2>📋 Recent Pending Inquiries</h2>
                    <a href="inquiries.php?status=pending" class="btn-admin btn-secondary-admin btn-small">View All</a>
                </div>
                
                <?php if (!empty($recent_inquiries)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Event Date</th>
                                    <th>Guests</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_inquiries, 0, 5) as $inquiry): ?>
                                <tr>
                                    <td><strong><?php echo escape($inquiry['full_name']); ?></strong></td>
                                    <td><?php echo escape($inquiry['email']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                    <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                    <td><?php echo date('M d', strtotime($inquiry['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="inquiries.php#inquiry-<?php echo (int)$inquiry['id']; ?>" class="btn-admin btn-secondary-admin btn-small">View</a>
                                            <a href="inquiries.php?action=approve&id=<?php echo (int)$inquiry['id']; ?>" class="btn-admin btn-primary-admin btn-small">Approve</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>🎉 No pending inquiries! All caught up.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========================================
                 RECENT CONFIRMED BOOKINGS
                 ======================================== -->
            <div class="admin-card">
                <div class="card-header">
                    <h2>✅ Recent Confirmed Bookings</h2>
                    <a href="bookings.php?status=confirmed" class="btn-admin btn-secondary-admin btn-small">View All</a>
                </div>
                
                <?php if (!empty($recent_bookings)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Event Date</th>
                                    <th>Guests</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_bookings, 0, 5) as $booking): ?>
                                <tr>
                                    <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                    <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                    <td>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-confirmed">Confirmed</span>
                                    </td>
                                    <td>
                                        <a href="bookings.php#booking-<?php echo (int)$booking['id']; ?>" class="btn-admin btn-secondary-admin btn-small">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No confirmed bookings at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========================================
                 RECENT CONTACT MESSAGES
                 ======================================== -->
            <div class="admin-card">
                <div class="card-header">
                    <h2>💬 Recent Contact Messages</h2>
                </div>
                
                <?php if (!empty($recent_messages)): ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_messages, 0, 5) as $message): ?>
                                <tr>
                                    <td><strong><?php echo escape($message['full_name']); ?></strong></td>
                                    <td><?php echo escape($message['email']); ?></td>
                                    <td><?php echo escape($message['subject']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-admin btn-secondary-admin btn-small" onclick="viewMessage(<?php echo (int)$message['id']; ?>)">View</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No contact messages yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
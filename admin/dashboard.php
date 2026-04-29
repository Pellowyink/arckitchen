<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

$inquiries = getInquiries();
$messages = getContactMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-shell">
        <!-- Sidebar (included via admin_sidebar.php) -->

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">Dashboard Overview</h1>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-label">Total Inquiries</div>
                    <div class="stat-value"><?php echo countRows('inquiries'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-label">Contact Messages</div>
                    <div class="stat-value"><?php echo countRows('contact_messages'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-label">Menu Items</div>
                    <div class="stat-value"><?php echo countRows('menu_items'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-label">Packages</div>
                    <div class="stat-value"><?php echo countRows('packages'); ?></div>
                </div>
            </div>

            <!-- Recent Inquiries Card -->
            <div class="admin-card">
                <h2>📋 Recent Inquiries</h2>
                <?php if (!empty($inquiries)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Event Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($inquiries, 0, 5) as $inquiry): ?>
                            <tr>
                                <td><strong><?php echo escape($inquiry['full_name']); ?></strong></td>
                                <td><?php echo escape($inquiry['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($inquiry['status']); ?>">
                                        <?php echo escape($inquiry['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="inquiries.php" class="btn-admin btn-secondary-admin btn-small">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No inquiries yet. Check back later!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Messages Card -->
            <div class="admin-card">
                <h2>💬 Recent Contact Messages</h2>
                <?php if (!empty($messages)): ?>
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
                            <?php foreach (array_slice($messages, 0, 5) as $message): ?>
                            <tr>
                                <td><strong><?php echo escape($message['full_name']); ?></strong></td>
                                <td><?php echo escape($message['email']); ?></td>
                                <td><?php echo escape($message['subject']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                <td>
                                    <a href="#" class="btn-admin btn-secondary-admin btn-small">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                </div>
                <div class="metric-card">
                    <span>Menu Items</span>
                    <strong><?php echo countRows('menu_items'); ?></strong>
                </div>
                <div class="metric-card">
                    <span>Packages</span>
                    <strong><?php echo countRows('packages'); ?></strong>
                </div>
            </div>

            <div class="admin-grid">
                <div class="table-card">
                    <h2>Latest Inquiries</h2>
                    <?php if ($inquiries): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($inquiries, 0, 5) as $inquiry): ?>
                                    <tr>
                                        <td><?php echo escape($inquiry['full_name']); ?></td>
                                        <td><?php echo escape($inquiry['event_type']); ?></td>
                                        <td><?php echo escape($inquiry['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">No inquiries yet.</div>
                    <?php endif; ?>
                </div>

                <div class="table-card">
                    <h2>Latest Contact Messages</h2>
                    <?php if ($messages): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Subject</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($messages, 0, 5) as $message): ?>
                                    <tr>
                                        <td><?php echo escape($message['full_name']); ?></td>
                                        <td><?php echo escape($message['subject']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">No contact messages yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>


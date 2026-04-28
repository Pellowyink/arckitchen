<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$page = basename($_SERVER['PHP_SELF']);
$inquiries = getInquiries();
$messages = getContactMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARC Kitchen Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="brand brand-light">
                <span class="brand-mark" aria-hidden="true">
                    <img src="../assets/images/arc-logo.png" alt="ARC Kitchen logo" class="brand-logo-image">
                </span>
                <span>ARC Kitchen</span>
            </div>
            <div class="sidebar-note">
                Logged in as <strong><?php echo escape($_SESSION['admin_username'] ?? 'admin'); ?></strong>
            </div>
            <nav>
                <a href="dashboard.php" class="<?php echo $page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="bookings.php">Bookings</a>
                <a href="menu-manager.php">Menu Manager</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <h1 class="admin-title">Dashboard Overview</h1>
            <div class="stats-grid">
                <div class="metric-card">
                    <span>Total Inquiries</span>
                    <strong><?php echo countRows('inquiries'); ?></strong>
                </div>
                <div class="metric-card">
                    <span>Contact Messages</span>
                    <strong><?php echo countRows('contact_messages'); ?></strong>
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


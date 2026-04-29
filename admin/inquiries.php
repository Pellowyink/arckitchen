<?php

require_once __DIR__ . '/../includes/functions.php';

$inquiries = getInquiries();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries - ARC Kitchen Admin</title>
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
                <h1 class="admin-title">💬 Booking Inquiries</h1>
            </div>

            <!-- Inquiries Table -->
            <div class="admin-card">
                <?php if (!empty($inquiries)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Event Date</th>
                                <th>Guest Count</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inquiries as $inquiry): ?>
                            <tr>
                                <td><strong><?php echo escape($inquiry['full_name']); ?></strong></td>
                                <td><?php echo escape($inquiry['email']); ?></td>
                                <td><?php echo escape($inquiry['phone']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($inquiry['status']); ?>">
                                        <?php echo escape($inquiry['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn-admin btn-secondary-admin btn-small">View</button>
                                        <button class="btn-admin btn-primary-admin btn-small">Update</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No inquiries found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
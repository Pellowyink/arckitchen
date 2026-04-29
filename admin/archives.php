<?php

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

$messages = getContactMessages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives - ARC Kitchen Admin</title>
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
                <h1 class="admin-title">📁 Archives & Messages</h1>
            </div>

            <!-- Contact Messages -->
            <div class="admin-card">
                <h2>📨 Contact Form Messages</h2>
                <?php if (!empty($messages)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                            <tr>
                                <td><strong><?php echo escape($message['full_name']); ?></strong></td>
                                <td><?php echo escape($message['email']); ?></td>
                                <td><?php echo escape($message['subject']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn-admin btn-secondary-admin btn-small">View</button>
                                        <button class="btn-admin btn-secondary-admin btn-small">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No messages found in archives.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Completed Events Archive -->
            <div class="admin-card" style="margin-top: 1.5rem;">
                <h2>📋 Completed Events</h2>
                <p style="color: var(--text-soft);">
                    Events that have been completed are archived here for record-keeping purposes.
                </p>
                <div class="empty-state" style="margin-top: 1rem;">
                    <p>No completed events archived yet.</p>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
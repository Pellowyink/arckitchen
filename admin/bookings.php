<?php

require_once __DIR__ . '/../includes/functions.php';

if (isPostRequest()) {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'update_status') {
        updateInquiryStatus($id, $_POST['status'] ?? 'Pending');
    }

    if ($action === 'delete') {
        deleteInquiry($id);
    }

    redirect('bookings.php');
}

$inquiries = getInquiries();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - ARC Kitchen Admin</title>
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
                <h1 class="admin-title">📅 Manage Bookings</h1>
            </div>

            <!-- Bookings Table Card -->
            <div class="admin-card">
                <?php if ($inquiries): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Event Type</th>
                                <th>Date</th>
                                <th>Guests</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inquiries as $inquiry): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo escape($inquiry['full_name']); ?></strong><br>
                                        <small><?php echo escape($inquiry['email']); ?></small><br>
                                        <small><?php echo escape($inquiry['phone']); ?></small>
                                    </td>
                                    <td><?php echo escape($inquiry['event_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                    <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                    <td>
                                        <form method="post" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo (int) $inquiry['id']; ?>">
                                            <select name="status" style="padding: 0.4rem; border-radius: 6px; border: 1px solid rgba(53, 21, 15, 0.15); font-size: 0.85rem;">
                                                <?php foreach (bookingStatuses() as $status): ?>
                                                    <option value="<?php echo escape($status); ?>" <?php echo $status === $inquiry['status'] ? 'selected' : ''; ?>>
                                                        <?php echo escape($status); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-admin btn-primary-admin btn-small">Save</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $inquiry['id']; ?>">
                                            <button type="submit" class="btn-admin btn-secondary-admin btn-small" onclick="return confirm('Are you sure?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>📭 No bookings found yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
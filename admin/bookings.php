<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

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

$inquiries = getInquiries();
$page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARC Kitchen Admin Bookings</title>
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
            <div class="sidebar-note">Booking controls</div>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="bookings.php" class="<?php echo $page === 'bookings.php' ? 'active' : ''; ?>">Bookings</a>
                <a href="menu-manager.php">Menu Manager</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <h1 class="admin-title">Manage Inquiries</h1>
            <div class="table-card">
                <?php if ($inquiries): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Event</th>
                                <th>Guests</th>
                                <th>Preferred Package</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inquiries as $inquiry): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo escape($inquiry['full_name']); ?></strong><br>
                                        <?php echo escape($inquiry['email']); ?><br>
                                        <?php echo escape($inquiry['phone']); ?>
                                    </td>
                                    <td>
                                        <?php echo escape($inquiry['event_type']); ?><br>
                                        <small><?php echo escape($inquiry['event_date']); ?></small>
                                    </td>
                                    <td><?php echo escape((string) $inquiry['guest_count']); ?></td>
                                    <td><?php echo escape($inquiry['package_interest']); ?></td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo (int) $inquiry['id']; ?>">
                                            <select name="status">
                                                <?php foreach (bookingStatuses() as $status): ?>
                                                    <option value="<?php echo escape($status); ?>" <?php echo $status === $inquiry['status'] ? 'selected' : ''; ?>>
                                                        <?php echo escape($status); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="button button-small spacer-top-sm">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <form method="post">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $inquiry['id']; ?>">
                                                <button type="submit" class="button button-small">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No inquiries found yet. Submit a booking form from the customer site to populate this list.</div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>


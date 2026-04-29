<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$data_type = 'bookings';

// Get active bookings (pending, confirmed)
$active_bookings = getBookings(['status' => 'confirmed']);
$pending_bookings = getBookings(['status' => 'pending']);
$active_bookings = array_merge($pending_bookings, $active_bookings);

// Get completed and cancelled separately
$completed_bookings = getBookings(['status' => 'completed']);
$cancelled_bookings = getBookings(['status' => 'cancelled']);

?><!DOCTYPE html>
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
                <h1 class="admin-title">📅 Bookings Management</h1>
                <p class="admin-subtitle">Manage event bookings and track execution status</p>
            </div>

            <!-- ========================================
                 FILTER BAR (Date filters)
                 ======================================== -->
            <?php require_once __DIR__ . '/../includes/filter_bar.php'; ?>

            <!-- ========================================
                 ACTIVE BOOKINGS (Pending + Confirmed)
                 ======================================== -->
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">🟢 Active Bookings (<?php echo count($active_bookings); ?>)</h2>
                    <span class="card-subtitle">Pending and Confirmed events</span>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Guests</th>
                                <th>Total Amount</th>
                                <th>Event Date</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="active-bookings-body">
                            <?php if (!empty($active_bookings)): ?>
                                <?php foreach ($active_bookings as $booking): 
                                    $status = strtolower($booking['status']);
                                ?>
                                <tr id="booking-<?php echo (int)$booking['id']; ?>" class="booking-row" data-booking-id="<?php echo (int)$booking['id']; ?>">
                                    <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                    <td><?php echo escape($booking['customer_email']); ?></td>
                                    <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                    <td><strong>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                    <td><?php echo escape(substr($booking['special_requests'] ?? '—', 0, 30)); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $booking['status'])); ?>">
                                            <?php echo escape($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(<?php echo (int)$booking['id']; ?>, 'booking')">Edit</button>
                                            <?php if ($status === 'pending'): ?>
                                                <button class="btn-admin btn-primary-admin btn-small" onclick="updateBookingStatus(<?php echo (int)$booking['id']; ?>, 'confirmed')">Confirm</button>
                                                <button class="btn-admin btn-danger-admin btn-small" onclick="updateBookingStatus(<?php echo (int)$booking['id']; ?>, 'cancelled')">Cancel</button>
                                            <?php elseif ($status === 'confirmed'): ?>
                                                <button class="btn-admin btn-success-admin btn-small" onclick="updateBookingStatus(<?php echo (int)$booking['id']; ?>, 'completed')">Complete</button>
                                                <button class="btn-admin btn-danger-admin btn-small" onclick="updateBookingStatus(<?php echo (int)$booking['id']; ?>, 'cancelled')">Cancel</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-cell">No active bookings found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================
                 COMPLETED BOOKINGS
                 ======================================== -->
            <div class="admin-card" style="margin-top: 30px;">
                <div class="card-header">
                    <h2 class="card-title">✅ Completed Bookings (<?php echo count($completed_bookings); ?>)</h2>
                    <span class="card-subtitle">Successfully finished events</span>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Guests</th>
                                <th>Total Amount</th>
                                <th>Event Date</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="completed-bookings-body">
                            <?php if (!empty($completed_bookings)): ?>
                                <?php foreach ($completed_bookings as $booking): ?>
                                <tr id="booking-<?php echo (int)$booking['id']; ?>" class="booking-row" data-booking-id="<?php echo (int)$booking['id']; ?>">
                                    <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                    <td><?php echo escape($booking['customer_email']); ?></td>
                                    <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                    <td><strong>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                    <td><?php echo escape(substr($booking['special_requests'] ?? '—', 0, 30)); ?></td>
                                    <td>
                                        <span class="badge badge-completed"><?php echo escape($booking['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(<?php echo (int)$booking['id']; ?>, 'booking')">Edit</button>
                                            <span class="badge badge-success">✓ Done</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-cell">No completed bookings yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================
                 CANCELLED BOOKINGS
                 ======================================== -->
            <div class="admin-card" style="margin-top: 30px; opacity: 0.85;">
                <div class="card-header">
                    <h2 class="card-title">❌ Cancelled Bookings (<?php echo count($cancelled_bookings); ?>)</h2>
                    <span class="card-subtitle">Cancelled or blocked events</span>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Guests</th>
                                <th>Total Amount</th>
                                <th>Event Date</th>
                                <th>Notes</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cancelled-bookings-body">
                            <?php if (!empty($cancelled_bookings)): ?>
                                <?php foreach ($cancelled_bookings as $booking): ?>
                                <tr id="booking-<?php echo (int)$booking['id']; ?>" class="booking-row" data-booking-id="<?php echo (int)$booking['id']; ?>">
                                    <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                    <td><?php echo escape($booking['customer_email']); ?></td>
                                    <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                    <td><strong>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                    <td><?php echo escape(substr($booking['special_requests'] ?? '—', 0, 30)); ?></td>
                                    <td>
                                        <span class="badge badge-cancelled"><?php echo escape($booking['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(<?php echo (int)$booking['id']; ?>, 'booking')">Edit</button>
                                            <span class="badge badge-danger">Cancelled</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-cell">No cancelled bookings.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ========================================
         EDITABLE MODULAR SIDEBAR (AJAX)
         ======================================== -->
    <?php require_once __DIR__ . '/../includes/edit_sidebar.php'; ?>

    <!-- ========================================
         ACTION SCRIPTS
         ======================================== -->
    <script>
        /**
         * Refresh all booking tables by reloading the page
         * (Simple approach to ensure all sections stay in sync)
         */
        function refreshAllTables() {
            window.location.reload();
        }

        /**
         * Update booking status
         */
        function updateBookingStatus(bookingId, newStatus) {
            if (!confirm(`Change status to ${newStatus}?`)) return;
            
            fetch(`../api/update-booking-status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: bookingId, status: newStatus }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    refreshAllTables();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Set up date filter listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Apply date filters on change
            document.getElementById('date-from')?.addEventListener('change', () => {
                const dateFrom = document.getElementById('date-from').value;
                const dateTo = document.getElementById('date-to').value;
                filterBookingsByDate(dateFrom, dateTo);
            });
            document.getElementById('date-to')?.addEventListener('change', () => {
                const dateFrom = document.getElementById('date-from').value;
                const dateTo = document.getElementById('date-to').value;
                filterBookingsByDate(dateFrom, dateTo);
            });

            // Clear filters button - reset date filters
            document.getElementById('clear-filters')?.addEventListener('click', () => {
                document.getElementById('date-from').value = '';
                document.getElementById('date-to').value = '';
                window.location.reload();
            });
        });

        /**
         * Filter bookings tables by date range
         */
        function filterBookingsByDate(dateFrom, dateTo) {
            const rows = document.querySelectorAll('.booking-row');
            rows.forEach(row => {
                const dateCell = row.querySelector('td:nth-child(5)'); // Event Date column
                if (dateCell) {
                    const rowDate = new Date(dateCell.textContent);
                    let show = true;
                    if (dateFrom && rowDate < new Date(dateFrom)) show = false;
                    if (dateTo && rowDate > new Date(dateTo)) show = false;
                    row.style.display = show ? '' : 'none';
                }
            });
        }
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
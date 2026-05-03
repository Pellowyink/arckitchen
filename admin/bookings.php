<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$data_type = 'bookings';

// Get active bookings (pending, confirmed) - exclude archived
$active_bookings = getBookings(['status' => 'confirmed', 'archived' => false]);
$pending_bookings = getBookings(['status' => 'pending', 'archived' => false]);
$active_bookings = array_merge($pending_bookings, $active_bookings);

// Get completed and cancelled separately - exclude archived
$completed_bookings = getBookings(['status' => 'completed', 'archived' => false]);
$cancelled_bookings = getBookings(['status' => 'cancelled', 'archived' => false]);

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
                                                <button class="btn-admin btn-primary-admin btn-small" onclick="changeBookingStatus(<?php echo (int)$booking['id']; ?>, 'confirmed')">Confirm</button>
                                                <button class="btn-admin btn-danger-admin btn-small" onclick="changeBookingStatus(<?php echo (int)$booking['id']; ?>, 'cancelled')">Cancel</button>
                                            <?php elseif ($status === 'confirmed'): ?>
                                                <button class="btn-admin btn-warning-admin btn-small" onclick="event.stopPropagation(); showETAModal(<?php echo (int)$booking['id']; ?>); return false;">👨‍🍳 In-Progress</button>
                                                <button class="btn-admin btn-success-admin btn-small" onclick="changeBookingStatus(<?php echo (int)$booking['id']; ?>, 'completed')">Complete & Pay</button>
                                                <button class="btn-admin btn-danger-admin btn-small" onclick="changeBookingStatus(<?php echo (int)$booking['id']; ?>, 'cancelled')">Cancel</button>
                                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #ddd;">
                                                    <small style="color: #666; display: block; margin-bottom: 4px;">📢 Customer Alerts:</small>
                                                    <button id="pickupBtn-<?php echo (int)$booking['id']; ?>" class="btn-admin btn-secondary-admin btn-small" onclick="sendQuickNotification(<?php echo (int)$booking['id']; ?>, 'ready_pickup')" style="background: #8a2927; color: white; margin-right: 4px;">📦 Ready</button>
                                                    <button id="onwayBtn-<?php echo (int)$booking['id']; ?>" class="btn-admin btn-secondary-admin btn-small" onclick="sendQuickNotification(<?php echo (int)$booking['id']; ?>, 'on_the_way')" style="background: #8a2927; color: white;">🚚 On The Way</button>
                                                </div>
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
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="archiveItem(<?php echo (int)$booking['id']; ?>, 'booking')">📦 Archive</button>
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
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="archiveItem(<?php echo (int)$booking['id']; ?>, 'booking')">📦 Archive</button>
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
         PAYMENT CALCULATOR SIDEBAR
         ======================================== -->
    <?php require_once __DIR__ . '/../includes/payment_calculator.php'; ?>

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
         * Complete booking with payment - opens payment calculator
         */
        function completeWithPayment(bookingId) {
            openPaymentCalculator(bookingId, 'booking', 'completed');
        }

        /**
         * Update booking status
         */
        function changeBookingStatus(bookingId, newStatus) {
            
            // Complete booking with payment calculator
            if (newStatus === 'completed') {
                completeWithPayment(bookingId);
                return;
            }
            
            if (typeof showArcConfirm === 'function') {
                showArcConfirm(`Change status to ${newStatus}?`, function(confirmed) {
                    if (confirmed) {
                        doUpdateStatus(bookingId, newStatus);
                    }
                });
            } else {
                if (!confirm(`Change status to ${newStatus}?`)) return;
                doUpdateStatus(bookingId, newStatus);
            }
        }
        
        function doUpdateStatus(bookingId, newStatus) {
            // Show loading animation
            if (typeof showArcLoading === 'function') {
                showArcLoading('Updating status...');
            }
            
            fetch(`../api/update-booking-status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: bookingId, status: newStatus }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess(data.message, function() {
                            refreshAllTables();
                        });
                    } else {
                        alert('✅ ' + data.message);
                        refreshAllTables();
                    }
                } else {
                    if (typeof showArcError === 'function') {
                        showArcError(data.message || 'Failed to update status');
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showArcError === 'function') {
                    showArcError('Network error. Please try again.');
                }
            });
        }

        // Set up date filter listeners
        document.addEventListener('DOMContentLoaded', () => {
            // ETA Modal: Close when clicking overlay (outside modal)
            const etaOverlay = document.getElementById('etaModalOverlay');
            const etaModal = document.getElementById('etaModal');
            
            if (etaOverlay) {
                etaOverlay.addEventListener('click', function(e) {
                    if (e.target === etaOverlay) {
                        closeETAModal();
                    }
                });
            }
            
            // Stop modal content clicks from closing modal
            if (etaModal) {
                etaModal.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
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

        /**
         * Archive a booking or inquiry
         */
        function archiveItem(id, type) {
            if (typeof showArcConfirm === 'function') {
                showArcConfirm('Are you sure you want to archive this ' + type + '?', function(confirmed) {
                    if (confirmed) {
                        doArchiveItem(id, type);
                    }
                });
            } else {
                if (!confirm('Are you sure you want to archive this ' + type + '?')) {
                    return;
                }
                doArchiveItem(id, type);
            }
        }
        
        function doArchiveItem(id, type) {
            fetch('../api/archive-item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, type: type })
            })
            .then(r => r.json())
            .then(result => {
                if (result.success) {
                    // Remove the row from the table
                    const row = document.getElementById(type + '-' + id);
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess('Item archived successfully!', function() {
                            location.reload();
                        });
                    } else {
                        alert('Item archived successfully!');
                        location.reload();
                    }
                } else {
                    if (typeof showArcError === 'function') {
                        showArcError(result.message || 'Failed to archive item');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to archive item'));
                    }
                }
            })
            .catch(err => {
                console.error('Archive error:', err);
                if (typeof showArcError === 'function') {
                    showArcError('Failed to archive item. Please try again.');
                } else {
                    alert('Failed to archive item. Please try again.');
                }
            });
        }
        
        // ============================================
        // ETA MODAL & CUSTOMER NOTIFICATIONS
        // ============================================
        
        let currentBookingId = null;
        let currentAction = null;
        
        /**
         * Show ETA Modal when transitioning to In-Progress
         */
        function showETAModal(bookingId) {
            currentBookingId = bookingId;
            currentAction = 'in-progress';
            
            const overlay = document.getElementById('etaModalOverlay');
            const modal = document.getElementById('etaModal');
            
            if (overlay && modal) {
                overlay.style.display = 'block';
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Clear previous ETA input
                const etaInput = document.getElementById('etaInput');
                if (etaInput) {
                    etaInput.value = '';
                }
                
                // Reset submit button
                const submitBtn = document.getElementById('etaSubmitBtn');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Confirm & Notify Customer';
                }
            } else {
                console.error('ETA Modal elements not found');
                alert('Error: Modal not found. Please refresh the page.');
            }
            
            return false; // Prevent default action
        }
        
        /**
         * Close ETA Modal
         */
        function closeETAModal() {
            document.getElementById('etaModalOverlay').style.display = 'none';
            document.getElementById('etaModal').style.display = 'none';
            document.body.style.overflow = '';
            currentBookingId = null;
            currentAction = null;
        }
        
        /**
         * Submit ETA and update status
         */
        function submitETA() {
            const eta = document.getElementById('etaInput').value;
            if (!eta) {
                if (typeof showArcError === 'function') {
                    showArcError('Please enter an estimated completion time.');
                } else {
                    alert('Please enter an estimated completion time.');
                }
                return;
            }
            
            // Show loading animation
            if (typeof showArcLoading === 'function') {
                showArcLoading('Updating status...');
            }
            
            // Disable button to prevent duplicate submissions
            const submitBtn = document.getElementById('etaSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending...';
            
            fetch('../api/update-booking-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id: currentBookingId, 
                    status: 'in-progress',
                    eta: eta,
                    send_email: true
                }),
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirm & Notify Customer';
                
                // Hide loading animation
                if (typeof hideArcLoading === 'function') {
                    hideArcLoading();
                }
                
                if (data.success) {
                    closeETAModal();
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess('Status updated and customer notified!', function() {
                            refreshAllTables();
                        });
                    } else {
                        alert('✅ Status updated and customer notified!');
                        refreshAllTables();
                    }
                } else {
                    if (typeof showArcError === 'function') {
                        showArcError(data.message || 'Failed to update status');
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirm & Notify Customer';
                
                // Hide loading animation
                if (typeof hideArcLoading === 'function') {
                    hideArcLoading();
                }
                
                if (typeof showArcError === 'function') {
                    showArcError('Network error. Please try again.');
                }
            });
        }
        
        /**
         * Send Quick Notification (Ready for Pickup / On The Way)
         */
        function sendQuickNotification(bookingId, type) {
            const typeLabels = {
                'ready_pickup': 'Ready for Pickup',
                'on_the_way': 'On The Way'
            };
            
            if (typeof showArcConfirm === 'function') {
                showArcConfirm(`Send "${typeLabels[type]}" notification to customer?`, function(confirmed) {
                    if (confirmed) {
                        doSendNotification(bookingId, type);
                    }
                });
            } else {
                if (!confirm(`Send "${typeLabels[type]}" notification to customer?`)) return;
                doSendNotification(bookingId, type);
            }
        }
        
        function doSendNotification(bookingId, type) {
            // Show loading animation
            if (typeof showArcLoading === 'function') {
                showArcLoading('Sending notification...');
            }
            
            // Disable button to prevent duplicate submissions
            const btnId = type === 'ready_pickup' ? 'pickupBtn-' + bookingId : 'onwayBtn-' + bookingId;
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Sending...';
            }
            
            fetch('../api/send-notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    booking_id: bookingId, 
                    type: type 
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess('Notification sent successfully!');
                    } else {
                        alert('✅ Notification sent successfully!');
                    }
                } else {
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = type === 'ready_pickup' ? '📦 Ready for Pickup' : '🚚 On The Way';
                    }
                    if (typeof showArcError === 'function') {
                        showArcError(data.message || 'Failed to send notification');
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = type === 'ready_pickup' ? '📦 Ready for Pickup' : '🚚 On The Way';
                }
                if (typeof showArcError === 'function') {
                    showArcError('Network error. Please try again.');
                }
            });
        }
    </script>

    <!-- ETA Modal -->
    <div id="etaModalOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; backdrop-filter: blur(4px);"></div>
    <div id="etaModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 25px; max-width: 450px; width: 90%; z-index: 10001; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
        <div style="background: linear-gradient(135deg, #4a1414 0%, #6c1d12 100%); padding: 1.5rem; border-radius: 25px 25px 0 0;">
            <h3 style="color: white; margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 0.75rem;">
                <span>⏰</span> Set Estimated Time
            </h3>
        </div>
        <div style="padding: 1.5rem;">
            <p style="color: #5c4a42; margin-bottom: 1.5rem; line-height: 1.6;">
                When will this order be ready? Enter an estimated completion time to notify the customer.
            </p>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; color: #4a1414; font-weight: 600; margin-bottom: 0.5rem; font-family: 'League Spartan', sans-serif;">
                    Estimated Completion Time
                </label>
                <input type="text" id="etaInput" placeholder="e.g., 3:00 PM today, or May 15, 2:00 PM" 
                    style="width: 100%; padding: 0.875rem 1rem; border: 2px solid #e8ddd4; border-radius: 10px; font-size: 1rem; color: #4a1414; box-sizing: border-box;">
                <small style="color: #8a6d5b; display: block; margin-top: 0.5rem;">
                    Examples: "Today at 3:00 PM", "Tomorrow at noon", "May 15, 2:00 PM"
                </small>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="button" onclick="closeETAModal()" style="flex: 1; padding: 0.875rem; border: 2px solid #ddd; background: white; border-radius: 10px; cursor: pointer; font-weight: 500; color: #666;">
                    Cancel
                </button>
                <button type="button" id="etaSubmitBtn" onclick="submitETA()" style="flex: 1; padding: 0.875rem; border: none; background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%); color: white; border-radius: 10px; cursor: pointer; font-weight: 600;">
                    Confirm & Notify Customer
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
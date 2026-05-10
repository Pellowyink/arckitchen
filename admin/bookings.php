<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$data_type = 'bookings';

// Get active bookings (pending, confirmed) - exclude archived
$active_bookings = getBookings(['status' => 'confirmed', 'archived' => false]);
$pending_bookings = getBookings(['status' => 'pending', 'archived' => false]);
$active_bookings = array_merge($pending_bookings, $active_bookings);

// Get all cancelled bookings (both active and archived - unified view)
$cancelled_bookings = getBookings(['status' => 'cancelled']);
$archived_cancelled_bookings = getArchivedBookings();
// Merge archived cancelled with active cancelled
$all_cancelled_bookings = array_merge($cancelled_bookings, $archived_cancelled_bookings);
// Remove duplicates by ID
$seen_ids = [];
$all_cancelled_bookings = array_filter($all_cancelled_bookings, function($b) use (&$seen_ids) {
    if (in_array($b['id'], $seen_ids)) return false;
    $seen_ids[] = $b['id'];
    return true;
});
// Sort by event date descending
usort($all_cancelled_bookings, function($a, $b) {
    return strtotime($b['event_date']) - strtotime($a['event_date']);
});

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
    <!-- Suppress favicon 404 error -->
    <link rel="icon" href="data:;base64,iVBORw0KGgo=">
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
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 class="card-title">🟢 Active Bookings (<?php echo count($active_bookings); ?>)</h2>
                        <span class="card-subtitle">Pending and Confirmed events</span>
                    </div>
                    <div class="bulk-actions" style="display: flex; gap: 0.5rem; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                            <input type="checkbox" id="selectAllActive" onchange="toggleSelectAll('active')">
                            Select All
                        </label>
                        <button class="btn-admin btn-danger-admin btn-small" onclick="bulkDeleteBookings('active')" id="bulkDeleteActiveBtn" style="display: none;">
                            🗑️ Delete Selected
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAllActiveHeader" onchange="toggleSelectAll('active')"></th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Guests</th>
                                <th>Total Amount</th>
                                <th>Event Details</th>
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
                                    <td><input type="checkbox" class="booking-checkbox active-checkbox" value="<?php echo (int)$booking['id']; ?>" onchange="updateBulkDeleteButton('active')"></td>
                                    <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                    <td><?php echo escape($booking['customer_email']); ?></td>
                                    <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                    <td><strong>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <div style="font-size: 0.85rem;">
                                            <div style="color: #4a1414; font-weight: 600;">
                                                <?php echo date('M d, Y', strtotime($booking['event_date'])); ?>
                                            </div>
                                            <?php if (!empty($booking['event_time'])): ?>
                                            <div style="color: #666;">
                                                <?php echo date('g:i A', strtotime($booking['event_time'])); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($booking['event_location'])): ?>
                                            <div style="color: #8a2927; font-size: 0.75rem; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo escape($booking['event_location']); ?>">
                                                <?php echo escape($booking['event_location']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $booking['status'])); ?>">
                                            <?php echo escape($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(<?php echo (int)$booking['id']; ?>, 'booking')" style="margin-right: 8px;">View Order</button>
                                            <button class="btn-manage" onclick="openManageModal(<?php echo (int)$booking['id']; ?>, 'booking', '<?php echo escape($booking['customer_name']); ?>', '<?php echo $status; ?>')">
                                                ⚙️ Manage
                                            </button>
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
                 CANCELLED & BLOCKED BOOKINGS (Unified View)
                 ======================================== -->
            <div class="admin-card" style="margin-top: 30px; background: #faf9f7; border: 1px solid #f0e6e0;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 class="card-title" style="color: #4a1414;">❌ Cancelled & Blocked Bookings (<?php echo count($all_cancelled_bookings); ?>)</h2>
                        <span class="card-subtitle">All cancelled or blocked events</span>
                    </div>
                    <div class="no-print" style="display: flex; gap: 0.5rem;">
                        <button class="btn-admin btn-secondary-admin btn-small" onclick="printAllCancelled()">🖨️ Print All</button>
                    </div>
                </div>
                <div id="cancelledBookingsTable">
                    <?php if (!empty($all_cancelled_bookings)): ?>
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
                                        <th class="no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_cancelled_bookings as $booking): ?>
                                    <tr id="cancelled-booking-<?php echo $booking['id']; ?>" data-booking-id="<?php echo (int)$booking['id']; ?>">
                                        <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                        <td><?php echo escape($booking['customer_email']); ?></td>
                                        <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                        <td><strong>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></strong></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                        <td><?php echo escape(substr($booking['special_requests'] ?? '—', 0, 30)); ?></td>
                                        <td>
                                            <span class="badge" style="background: #f8d7da; color: #721c24; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">CANCELLED</span>
                                        </td>
                                        <td class="no-print action-buttons" style="display: flex; gap: 0.5rem;">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="printBookingReceipt(<?php echo (int)$booking['id']; ?>)">🧾 Receipt</button>
                                            <button class="btn-admin btn-danger-admin btn-small" style="background: #8a2927;" onclick="deleteBookingRecord(<?php echo (int)$booking['id']; ?>, '<?php echo escape($booking['customer_name']); ?>')">🗑️ Delete</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 3rem 2rem; text-align: center; background: #fff; border-radius: 20px; margin: 1rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                            <h3 style="color: #4a1414; margin-bottom: 0.5rem;">No cancelled bookings found</h3>
                            <p style="color: #888;">All bookings are active or completed</p>
                        </div>
                    <?php endif; ?>
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
        
        /**
         * Print all cancelled bookings section
         */
        function printAllCancelled() {
            const content = document.getElementById('cancelledBookingsTable').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Cancelled Bookings - ARC Kitchen</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background: #f5f5f5; font-weight: bold; }
                        h1 { color: #4a1414; }
                        .no-print, .action-buttons { display: none !important; }
                    </style>
                </head>
                <body>
                    <h1>📦 Cancelled Bookings</h1>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <hr>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        /**
         * Print receipt for a specific booking
         */
        function printBookingReceipt(bookingId) {
            // Find booking data from the table
            const row = document.getElementById('cancelled-booking-' + bookingId);
            if (!row) {
                alert('Booking not found');
                return;
            }
            
            const cells = row.getElementsByTagName('td');
            const customer = cells[0].textContent.trim();
            const email = cells[1].textContent.trim();
            const guests = cells[2].textContent.trim();
            const total = cells[3].textContent.trim();
            const eventDate = cells[4].textContent.trim();
            const status = cells[5].textContent.trim();
            const archived = cells[6].textContent.trim();
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Booking Receipt - ARC Kitchen</title>
                    <style>
                        body { font-family: 'Courier New', monospace; max-width: 400px; margin: 0 auto; padding: 20px; }
                        .receipt-header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 15px; margin-bottom: 20px; }
                        .receipt-row { display: flex; justify-content: space-between; padding: 8px 0; }
                        .receipt-footer { text-align: center; border-top: 2px dashed #333; padding-top: 15px; margin-top: 20px; font-size: 0.85rem; }
                        h2 { margin: 0; color: #4a1414; }
                    </style>
                </head>
                <body>
                    <div class="receipt-header">
                        <h2>ARC KITCHEN</h2>
                        <p>Booking Receipt</p>
                        <p>${new Date().toLocaleDateString()}</p>
                    </div>
                    <div class="receipt-row"><span>Receipt #:</span><span>ARC-${bookingId.toString().padStart(4, '0')}</span></div>
                    <div class="receipt-row"><span>Customer:</span><span>${customer}</span></div>
                    <div class="receipt-row"><span>Email:</span><span>${email}</span></div>
                    <div class="receipt-row"><span>Event Date:</span><span>${eventDate}</span></div>
                    <div class="receipt-row"><span>Guests:</span><span>${guests}</span></div>
                    <div class="receipt-row"><span>Status:</span><span>${status}</span></div>
                    <div class="receipt-row" style="border-top: 1px dashed #ccc; margin-top: 10px; padding-top: 10px; font-weight: bold;">
                        <span>Total:</span><span>${total}</span>
                    </div>
                    <div class="receipt-footer">
                        <p>Thank you for choosing ARC Kitchen!</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        /**
         * Delete a booking record permanently
         */
        function deleteBookingRecord(bookingId, customerName) {
            const message = customerName 
                ? `Warning: This will permanently remove ${customerName}'s record from the database. Continue?`
                : 'Warning: This will permanently remove this record from the database. Continue?';
                
            if (typeof showArcConfirm === 'function') {
                showArcConfirm(message, function(confirmed) {
                    if (confirmed) {
                        doDeleteBookingRecord(bookingId);
                    }
                });
            } else {
                if (!confirm(message)) {
                    return;
                }
                doDeleteBookingRecord(bookingId);
            }
        }
        
        function doDeleteBookingRecord(bookingId) {
            fetch('../api/delete-archived-booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: bookingId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const row = document.getElementById('cancelled-booking-' + bookingId);
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess('Record successfully removed from system');
                    } else {
                        alert('Record successfully removed from system');
                    }
                } else {
                    if (typeof showArcError === 'function') {
                        showArcError(result.message || 'Failed to delete booking');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to delete booking'));
                    }
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                if (typeof showArcError === 'function') {
                    showArcError('Failed to delete booking. Please try again.');
                } else {
                    alert('Failed to delete booking. Please try again.');
                }
            });
        }
        
        // ============================================
        // BULK DELETE & SOFT DELETE WITH REASON
        // ============================================
        
        let currentDeleteIds = [];
        let currentDeleteType = '';
        
        /**
         * Toggle all checkboxes for bulk delete
         */
        function toggleSelectAll(type) {
            const checkboxes = document.querySelectorAll(`.${type}-checkbox`);
            const selectAllHeader = document.getElementById(`selectAll${type.charAt(0).toUpperCase() + type.slice(1)}Header`);
            const selectAllMain = document.getElementById(`selectAll${type.charAt(0).toUpperCase() + type.slice(1)}`);
            
            const isChecked = selectAllHeader.checked;
            
            checkboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            
            if (selectAllMain) selectAllMain.checked = isChecked;
            
            updateBulkDeleteButton(type);
        }
        
        /**
         * Update bulk delete button visibility
         */
        function updateBulkDeleteButton(type) {
            const checkboxes = document.querySelectorAll(`.${type}-checkbox:checked`);
            const btn = document.getElementById(`bulkDelete${type.charAt(0).toUpperCase() + type.slice(1)}Btn`);
            
            if (btn) {
                btn.style.display = checkboxes.length > 0 ? 'inline-block' : 'none';
                btn.textContent = `🗑️ Delete Selected (${checkboxes.length})`;
            }
        }
        
        /**
         * Show delete reason modal for single booking
         */
        function deleteBookingWithReason(bookingId, customerName) {
            currentDeleteIds = [bookingId];
            currentDeleteType = 'single';
            
            const message = customerName 
                ? `You are about to delete <strong>${escapeHtml(customerName)}'s</strong> booking. This will mark it as deleted but keep the record for audit purposes.`
                : 'You are about to delete this booking. This will mark it as deleted but keep the record for audit purposes.';
            
            showDeleteReasonModal(message, function(reason) {
                if (reason !== null) {
                    doSoftDeleteBookings([bookingId], reason);
                }
            });
        }
        
        /**
         * Bulk delete bookings with reason
         */
        function bulkDeleteBookings(type) {
            const checkboxes = document.querySelectorAll(`.${type}-checkbox:checked`);
            const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
            if (ids.length === 0) {
                if (typeof showArcError === 'function') {
                    showArcError('Please select at least one booking to delete.');
                } else {
                    alert('Please select at least one booking to delete.');
                }
                return;
            }
            
            currentDeleteIds = ids;
            currentDeleteType = 'bulk';
            
            const message = `You are about to delete <strong>${ids.length}</strong> booking(s). This will mark them as deleted but keep the records for audit purposes.`;
            
            showDeleteReasonModal(message, function(reason) {
                if (reason !== null) {
                    doSoftDeleteBookings(ids, reason);
                }
            });
        }
        
        /**
         * Show custom delete reason modal
         */
        function showDeleteReasonModal(message, callback) {
            // Remove existing modal if any
            const existingModal = document.getElementById('deleteReasonModal');
            if (existingModal) existingModal.remove();
            
            const modalHtml = `
                <div id="deleteReasonModalOverlay" style="display: block; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 20000; backdrop-filter: blur(4px);"></div>
                <div id="deleteReasonModal" style="display: block; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 16px; max-width: 450px; width: 90%; z-index: 20001; box-shadow: 0 25px 50px rgba(0,0,0,0.3); padding: 1.5rem;">
                    <h3 style="color: #8a2927; margin: 0 0 1rem 0; font-size: 1.2rem;">⚠️ Confirm Deletion</h3>
                    <p style="margin: 0 0 1rem 0; color: #333; line-height: 1.5;">${message}</p>
                    <div style="margin-bottom: 1rem;">
                        <label for="deleteReason" style="display: block; margin-bottom: 0.5rem; color: #4a1414; font-weight: 600;">Reason for deletion (optional):</label>
                        <textarea id="deleteReason" rows="3" placeholder="e.g., Duplicate booking, Customer request, Invalid data..." style="width: 100%; padding: 0.75rem; border: 2px solid #e5d5c5; border-radius: 8px; font-family: inherit; resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button onclick="closeDeleteReasonModal(null)" style="padding: 0.75rem 1.5rem; border: 2px solid #ddd; background: #f5f5f5; border-radius: 8px; cursor: pointer; font-family: inherit; font-weight: 500;">Cancel</button>
                        <button onclick="closeDeleteReasonModal(document.getElementById('deleteReason').value)" style="padding: 0.75rem 1.5rem; border: none; background: #8a2927; color: white; border-radius: 8px; cursor: pointer; font-family: inherit; font-weight: 600;">Delete</button>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            document.getElementById('deleteReason').focus();
            
            // Store callback for later use
            window.deleteReasonCallback = callback;
        }
        
        /**
         * Close delete reason modal and execute callback
         */
        function closeDeleteReasonModal(reason) {
            const modal = document.getElementById('deleteReasonModal');
            const overlay = document.getElementById('deleteReasonModalOverlay');
            
            if (modal) modal.remove();
            if (overlay) overlay.remove();
            
            if (window.deleteReasonCallback) {
                window.deleteReasonCallback(reason);
                window.deleteReasonCallback = null;
            }
        }
        
        /**
         * Escape HTML for safe display
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Perform soft delete API call
         */
        function doSoftDeleteBookings(ids, reason) {
            if (typeof showArcLoading === 'function') {
                showArcLoading('Deleting...');
            }
            
            const apiUrl = ids.length === 1 
                ? '../api/soft-delete-booking.php' 
                : '../api/bulk-delete-bookings.php';
            
            const bodyData = ids.length === 1 
                ? { id: ids[0], reason: reason || 'No reason provided' }
                : { ids: ids, reason: reason || 'Bulk delete operation' };
            
            fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bodyData)
            })
            .then(response => response.json())
            .then(result => {
                if (typeof hideArcModal === 'function') hideArcModal();
                
                if (result.success) {
                    // Remove rows from table
                    ids.forEach(id => {
                        const row = document.getElementById('booking-' + id) || document.getElementById('cancelled-booking-' + id);
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        }
                    });
                    
                    // Reset checkboxes
                    document.querySelectorAll('.booking-checkbox').forEach(cb => cb.checked = false);
                    updateBulkDeleteButton('active');
                    
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess(result.message || 'Booking(s) deleted successfully');
                    } else {
                        alert(result.message || 'Booking(s) deleted successfully');
                    }
                } else {
                    if (typeof showArcError === 'function') {
                        showArcError(result.message || 'Failed to delete booking(s)');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to delete booking(s)'));
                    }
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                if (typeof hideArcModal === 'function') hideArcModal();
                if (typeof showArcError === 'function') {
                    showArcError('Failed to delete booking(s). Please try again.');
                } else {
                    alert('Failed to delete booking(s). Please try again.');
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

    <!-- Admin Success Modal -->
    <div id="adminSuccessModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 9999;">
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9); background: #fffdf8; border-radius: 25px; padding: 3rem; max-width: 450px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.3); text-align: center; border: 3px solid #4a1414; animation: modalSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">✅</div>
            <h2 style="color: #4a1414; margin: 0 0 1rem 0; font-size: 1.5rem;">Order Completed!</h2>
            <p id="successModalMessage" style="color: #666; margin: 0 0 1.5rem 0; font-size: 1rem; line-height: 1.5;"></p>
            <button onclick="closeAdminSuccessModal()" class="btn-admin btn-primary-admin" style="padding: 0.75rem 2rem; font-size: 1rem;">Close</button>
        </div>
    </div>
    
    <!-- Admin Error Modal -->
    <div id="adminErrorModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 9999;">
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9); background: #fffdf8; border-radius: 25px; padding: 3rem; max-width: 450px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.3); text-align: center; border: 3px solid #dc3545; animation: modalSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">⚠️</div>
            <h2 style="color: #dc3545; margin: 0 0 1rem 0; font-size: 1.5rem;">Email Failed</h2>
            <p id="errorModalMessage" style="color: #666; margin: 0 0 1.5rem 0; font-size: 1rem; line-height: 1.5;">Failed to send final receipt email.</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button id="retryEmailBtn" class="btn-admin btn-primary-admin" style="padding: 0.75rem 1.5rem;">🔄 Retry Email</button>
                <button onclick="closeAdminErrorModal()" class="btn-admin btn-secondary-admin" style="padding: 0.75rem 1.5rem;">Complete Without Email</button>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes modalSlideIn {
            from { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
            to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }
    </style>
    
    <script>
        let currentBookingIdForRetry = null;
        
        function showAdminSuccessModal(message) {
            document.getElementById('successModalMessage').textContent = message;
            document.getElementById('adminSuccessModal').style.display = 'block';
        }
        
        function closeAdminSuccessModal() {
            document.getElementById('adminSuccessModal').style.display = 'none';
            window.location.reload();
        }
        
        function showAdminErrorModal(message, bookingId) {
            currentBookingIdForRetry = bookingId;
            document.getElementById('errorModalMessage').textContent = message || 'Failed to send final receipt email.';
            document.getElementById('adminErrorModal').style.display = 'block';
        }
        
        function closeAdminErrorModal() {
            document.getElementById('adminErrorModal').style.display = 'none';
            window.location.reload();
        }
        
        // Retry email function
        document.getElementById('retryEmailBtn')?.addEventListener('click', async function() {
            if (!currentBookingIdForRetry) return;
            
            this.textContent = '🔄 Sending...';
            this.disabled = true;
            
            try {
                const response = await fetch('../api/resend-receipt.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: currentBookingIdForRetry })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    closeAdminErrorModal();
                    showAdminSuccessModal('Final receipt resent successfully!');
                } else {
                    this.textContent = '🔄 Retry Email';
                    this.disabled = false;
                    alert('Failed to resend email: ' + data.message);
                }
            } catch (error) {
                this.textContent = '🔄 Retry Email';
                this.disabled = false;
                alert('Network error. Please try again.');
            }
        });
        
        // Close modals on overlay click
        document.getElementById('adminSuccessModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeAdminSuccessModal();
        });
        document.getElementById('adminErrorModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeAdminErrorModal();
        });
    </script>

    <!-- Manage Actions Modal JavaScript -->
    <script>
        let currentManageRecord = null;

        // Open Manage Actions Modal
        function openManageModal(recordId, recordType, recordName, status = null) {
            currentManageRecord = { id: recordId, type: recordType, name: recordName, status: status };

            // Update modal title
            const titleEl = document.getElementById('manageModalTitle');
            titleEl.textContent = `Manage ${recordType === 'inquiry' ? 'Inquiry' : 'Booking'}: ${recordName}`;

            // Populate modal body with actions
            const bodyEl = document.getElementById('manageModalBody');
            bodyEl.innerHTML = generateManageActions(recordType, recordId, recordName, status);

            // Show modal
            const modal = document.getElementById('manageActionsModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        // Close Manage Actions Modal
        function closeManageModal() {
            const modal = document.getElementById('manageActionsModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            currentManageRecord = null;
        }

        // Generate action buttons HTML based on record type and status
        function generateManageActions(recordType, recordId, recordName, status) {
            if (recordType === 'inquiry') {
                return `
                    <div class="manage-actions-grid">
                        <button class="manage-action-btn action-approve" onclick="approveInquiry(${recordId}); closeManageModal();">
                            <div class="action-icon">✅</div>
                            <div class="action-label">Approve</div>
                        </button>
                        <button class="manage-action-btn action-reject" onclick="rejectInquiry(${recordId}); closeManageModal();">
                            <div class="action-icon">❌</div>
                            <div class="action-label">Reject</div>
                        </button>
                        <button class="manage-action-btn action-edit" onclick="openEditModal(${recordId}, 'inquiry'); closeManageModal();">
                            <div class="action-icon">📝</div>
                            <div class="action-label">Edit Order</div>
                        </button>
                        <button class="manage-action-btn action-delete" onclick="deleteInquiryWithReason(${recordId}, '${recordName.replace(/'/g, "\\'")}'); closeManageModal();">
                            <div class="action-icon">🗑️</div>
                            <div class="action-label">Delete</div>
                        </button>
                    </div>
                `;
            } else if (recordType === 'booking') {
                let actionsHtml = '<div class="manage-actions-grid">';

                if (status === 'pending') {
                    actionsHtml += `
                        <button class="manage-action-btn action-confirm" onclick="changeBookingStatus(${recordId}, 'confirmed'); closeManageModal();">
                            <div class="action-icon">✅</div>
                            <div class="action-label">Confirm</div>
                        </button>
                        <button class="manage-action-btn action-cancel" onclick="changeBookingStatus(${recordId}, 'cancelled'); closeManageModal();">
                            <div class="action-icon">❌</div>
                            <div class="action-label">Cancel</div>
                        </button>
                    `;
                } else if (status === 'confirmed') {
                    actionsHtml += `
                        <button class="manage-action-btn action-progress" onclick="showETAModal(${recordId}); closeManageModal();">
                            <div class="action-icon">👨‍🍳</div>
                            <div class="action-label">In-Progress</div>
                        </button>
                        <button class="manage-action-btn action-complete" onclick="changeBookingStatus(${recordId}, 'completed'); closeManageModal();">
                            <div class="action-icon">💰</div>
                            <div class="action-label">Complete & Pay</div>
                        </button>
                        <button class="manage-action-btn action-cancel" onclick="changeBookingStatus(${recordId}, 'cancelled'); closeManageModal();">
                            <div class="action-icon">❌</div>
                            <div class="action-label">Cancel</div>
                        </button>
                        <button class="manage-action-btn action-ready" onclick="sendQuickNotification(${recordId}, 'ready_pickup'); closeManageModal();">
                            <div class="action-icon">📦</div>
                            <div class="action-label">Ready</div>
                        </button>
                        <button class="manage-action-btn action-onway" onclick="sendQuickNotification(${recordId}, 'on_the_way'); closeManageModal();">
                            <div class="action-icon">🚚</div>
                            <div class="action-label">On The Way</div>
                        </button>
                    `;
                }

                actionsHtml += `
                    <button class="manage-action-btn action-edit" onclick="openEditModal(${recordId}, 'booking'); closeManageModal();">
                        <div class="action-icon">📝</div>
                        <div class="action-label">Edit Order</div>
                    </button>
                    <button class="manage-action-btn action-delete" onclick="deleteBookingWithReason(${recordId}, '${recordName.replace(/'/g, "\\'")}'); closeManageModal();">
                        <div class="action-icon">🗑️</div>
                        <div class="action-label">Delete</div>
                    </button>
                </div>`;

                return actionsHtml;
            }

            return '<p>No actions available</p>';
        }

        // Close modal when clicking overlay
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('manageActionsModal');
            if (event.target === modal) {
                closeManageModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('manageActionsModal');
                if (modal.style.display !== 'none') {
                    closeManageModal();
                }
            }
        });
    </script>

    <!-- Manage Actions Modal -->
    <div id="manageActionsModal" class="manage-modal-overlay" style="display: none;">
        <div class="manage-modal-content">
            <div class="manage-modal-header">
                <h3 id="manageModalTitle">Manage Order</h3>
                <button class="manage-modal-close" onclick="closeManageModal()">×</button>
            </div>
            <div class="manage-modal-body" id="manageModalBody">
                <!-- Actions will be populated here by JavaScript -->
            </div>
        </div>
    </div>

    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$data_type = 'inquiries';

// Get active (pending) inquiries - exclude archived
$active_inquiries = getInquiriesFiltered(['status' => 'pending', 'archived' => false]);

// Get all rejected inquiries (both active and archived - unified view)
$rejected_inquiries = getInquiriesFiltered(['status' => 'rejected']);
$archived_rejected_inquiries = getArchivedInquiries();
// Merge archived rejected with active rejected
$all_rejected_inquiries = array_merge($rejected_inquiries, $archived_rejected_inquiries);
// Remove duplicates by ID
$seen_ids = [];
$all_rejected_inquiries = array_filter($all_rejected_inquiries, function($i) use (&$seen_ids) {
    if (in_array($i['id'], $seen_ids)) return false;
    $seen_ids[] = $i['id'];
    return true;
});
// Sort by event date descending
usort($all_rejected_inquiries, function($a, $b) {
    return strtotime($b['event_date']) - strtotime($a['event_date']);
});

?><!DOCTYPE html>
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
                <p class="admin-subtitle">Manage incoming inquiry requests and convert to bookings</p>
            </div>

            <!-- ========================================
                 UNIFIED FILTER BAR (Date filters for inquiries)
                 ======================================== -->
            <?php require_once __DIR__ . '/../includes/filter_bar.php'; ?>

            <!-- ========================================
                 ACTIVE INQUIRIES (Pending)
                 ======================================== -->
            <div class="admin-card">
                <div class="card-header">
                    <h2 class="card-title">🟢 Active Inquiries (<?php echo count($active_inquiries); ?>)</h2>
                    <span class="card-subtitle">Pending inquiry requests awaiting approval</span>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Event Details</th>
                                <th>Guests</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="active-inquiries-body">
                            <?php if (!empty($active_inquiries)): ?>
                                <?php foreach ($active_inquiries as $inquiry): ?>
                                <tr id="inquiry-<?php echo (int)$inquiry['id']; ?>" class="inquiry-row" data-inquiry-id="<?php echo (int)$inquiry['id']; ?>">
                                    <td><strong><?php echo escape($inquiry['full_name']); ?></strong></td>
                                    <td><?php echo escape($inquiry['email']); ?></td>
                                    <td><?php echo escape($inquiry['phone']); ?></td>
                                    <td>
                                        <div style="font-size: 0.85rem;">
                                            <div style="color: #4a1414; font-weight: 600;">
                                                <?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?>
                                            </div>
                                            <?php if (!empty($inquiry['event_time'])): ?>
                                            <div style="color: #666;">
                                                <?php echo date('g:i A', strtotime($inquiry['event_time'])); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($inquiry['event_location'])): ?>
                                            <div style="color: #8a2927; font-size: 0.75rem; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo escape($inquiry['event_location']); ?>">
                                                <?php echo escape($inquiry['event_location']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                    <td><?php echo date('M d', strtotime($inquiry['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-pending"><?php echo escape($inquiry['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(<?php echo (int)$inquiry['id']; ?>, 'inquiry')">View Order</button>
                                            <button class="btn-admin btn-primary-admin btn-small" onclick="approveInquiry(<?php echo (int)$inquiry['id']; ?>)">Approve</button>
                                            <button class="btn-admin btn-danger-admin btn-small" onclick="rejectInquiry(<?php echo (int)$inquiry['id']; ?>)">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-cell">No pending inquiries found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================
                 REJECTED INQUIRIES (Unified View)
                 ======================================== -->
            <div class="admin-card" style="margin-top: 30px; background: #faf9f7; border: 1px solid #f0e6e0;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h2 class="card-title" style="color: #4a1414;">❌ Rejected Inquiries (<?php echo count($all_rejected_inquiries); ?>)</h2>
                        <span class="card-subtitle">All declined inquiry requests</span>
                    </div>
                    <div class="no-print" style="display: flex; gap: 0.5rem;">
                        <button class="btn-admin btn-secondary-admin btn-small" onclick="printAllRejected()">🖨️ Print All</button>
                    </div>
                </div>
                <div id="rejectedInquiriesTable">
                    <?php if (!empty($all_rejected_inquiries)): ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Event Date</th>
                                        <th>Guests</th>
                                        <th>Submitted</th>
                                        <th>Status</th>
                                        <th class="no-print">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_rejected_inquiries as $inquiry): ?>
                                    <tr id="rejected-inquiry-<?php echo $inquiry['id']; ?>" data-inquiry-id="<?php echo (int)$inquiry['id']; ?>">
                                        <td><strong><?php echo escape($inquiry['full_name']); ?></strong></td>
                                        <td><?php echo escape($inquiry['email']); ?></td>
                                        <td><?php echo escape($inquiry['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                        <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                        <td><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></td>
                                        <td>
                                            <span class="badge" style="background: #f8d7da; color: #721c24; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">REJECTED</span>
                                        </td>
                                        <td class="no-print action-buttons" style="display: flex; gap: 0.5rem;">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="printInquiryReceipt(<?php echo (int)$inquiry['id']; ?>)">🧾 Receipt</button>
                                            <button class="btn-admin btn-danger-admin btn-small" style="background: #8a2927;" onclick="deleteInquiryRecord(<?php echo (int)$inquiry['id']; ?>, '<?php echo escape($inquiry['full_name']); ?>')">🗑️ Delete</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 3rem 2rem; text-align: center; background: #fff; border-radius: 20px; margin: 1rem;">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                            <h3 style="color: #4a1414; margin-bottom: 0.5rem;">No rejected inquiries found</h3>
                            <p style="color: #888;">All inquiries are pending or approved</p>
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
         * Refresh all inquiry tables by reloading the page
         */
        function refreshAllTables() {
            window.location.reload();
        }

        /**
         * Approve an inquiry - opens payment calculator
         */
        function approveInquiry(inquiryId) {
            openPaymentCalculator(inquiryId, 'inquiry', 'approve');
        }

        /**
         * Reject an inquiry
         */
        function rejectInquiry(inquiryId) {
            if (typeof showArcConfirm === 'function') {
                showArcConfirm('Reject this inquiry? This action cannot be undone.', function(confirmed) {
                    if (confirmed) {
                        doRejectInquiry(inquiryId);
                    }
                });
            } else {
                if (!confirm('Reject this inquiry? This action cannot be undone.')) return;
                doRejectInquiry(inquiryId);
            }
        }
        
        function doRejectInquiry(inquiryId) {
            // Show loading animation
            if (typeof showArcLoading === 'function') {
                showArcLoading('Rejecting inquiry...');
            }
            
            fetch(`../api/update-inquiry-status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: inquiryId, action: 'reject' }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess('Inquiry rejected', function() {
                            refreshAllTables();
                        });
                    } else {
                        alert('❌ Inquiry rejected');
                        refreshAllTables();
                    }
                } else {
                    if (typeof showArcError === 'function') {
                        showArcError(data.message || 'Failed to reject inquiry');
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

        // Set up filter event listeners for date filters
        document.addEventListener('DOMContentLoaded', () => {
            // Apply date filters on change
            document.getElementById('date-from')?.addEventListener('change', () => {
                const dateFrom = document.getElementById('date-from').value;
                const dateTo = document.getElementById('date-to').value;
                filterByDate(dateFrom, dateTo);
            });
            document.getElementById('date-to')?.addEventListener('change', () => {
                const dateFrom = document.getElementById('date-from').value;
                const dateTo = document.getElementById('date-to').value;
                filterByDate(dateFrom, dateTo);
            });

            // Clear filters button - reset date filters
            document.getElementById('clear-filters')?.addEventListener('click', () => {
                document.getElementById('date-from').value = '';
                document.getElementById('date-to').value = '';
                window.location.reload();
            });
        });

        /**
         * Filter tables by date range
         */
        function filterByDate(dateFrom, dateTo) {
            const rows = document.querySelectorAll('.inquiry-row');
            rows.forEach(row => {
                const dateCell = row.querySelector('td:nth-child(4)');
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
         * Archive an inquiry
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
                    const row = document.getElementById(type + '-' + id);
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                    if (typeof showArcSuccess === 'function') {
                        showArcSuccess('Item archived successfully!', function() {
                            // Refresh to update counts and ensure consistency
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
         * Print all rejected inquiries section
         */
        function printAllRejected() {
            const content = document.getElementById('rejectedInquiriesTable').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Rejected Inquiries - ARC Kitchen</title>
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
                    <h1>📦 Rejected Inquiries</h1>
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
         * Print receipt for a specific inquiry
         */
        function printInquiryReceipt(inquiryId) {
            // Find inquiry data from the table
            const row = document.getElementById('rejected-inquiry-' + inquiryId);
            if (!row) {
                alert('Inquiry not found');
                return;
            }
            
            const cells = row.getElementsByTagName('td');
            const customer = cells[0].textContent.trim();
            const email = cells[1].textContent.trim();
            const phone = cells[2].textContent.trim();
            const eventDate = cells[3].textContent.trim();
            const guests = cells[4].textContent.trim();
            const status = cells[5].textContent.trim();
            const archived = cells[6].textContent.trim();
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Inquiry Receipt - ARC Kitchen</title>
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
                        <p>Inquiry Receipt</p>
                        <p>${new Date().toLocaleDateString()}</p>
                    </div>
                    <div class="receipt-row"><span>Receipt #:</span><span>ARC-INQ-${inquiryId.toString().padStart(4, '0')}</span></div>
                    <div class="receipt-row"><span>Customer:</span><span>${customer}</span></div>
                    <div class="receipt-row"><span>Email:</span><span>${email}</span></div>
                    <div class="receipt-row"><span>Phone:</span><span>${phone}</span></div>
                    <div class="receipt-row"><span>Event Date:</span><span>${eventDate}</span></div>
                    <div class="receipt-row"><span>Guests:</span><span>${guests}</span></div>
                    <div class="receipt-row"><span>Status:</span><span>${status}</span></div>
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
         * Delete an inquiry record permanently
         */
        function deleteInquiryRecord(inquiryId, customerName) {
            const message = customerName 
                ? `Warning: This will permanently remove ${customerName}'s record from the database. Continue?`
                : 'Warning: This will permanently remove this record from the database. Continue?';
                
            if (typeof showArcConfirm === 'function') {
                showArcConfirm(message, function(confirmed) {
                    if (confirmed) {
                        doDeleteInquiryRecord(inquiryId);
                    }
                });
            } else {
                if (!confirm(message)) {
                    return;
                }
                doDeleteInquiryRecord(inquiryId);
            }
        }
        
        function doDeleteInquiryRecord(inquiryId) {
            fetch('../api/delete-archived-inquiry.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: inquiryId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const row = document.getElementById('rejected-inquiry-' + inquiryId);
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
                        showArcError(result.message || 'Failed to delete inquiry');
                    } else {
                        alert('Error: ' + (result.message || 'Failed to delete inquiry'));
                    }
                }
            })
            .catch(err => {
                console.error('Delete error:', err);
                if (typeof showArcError === 'function') {
                    showArcError('Failed to delete inquiry. Please try again.');
                } else {
                    alert('Failed to delete inquiry. Please try again.');
                }
            });
        }
    </script>

    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
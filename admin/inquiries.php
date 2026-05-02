<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$data_type = 'inquiries';

// Get active (pending) inquiries - exclude archived
$active_inquiries = getInquiriesFiltered(['status' => 'pending', 'archived' => false]);

// Get rejected inquiries separately - exclude archived
$rejected_inquiries = getInquiriesFiltered(['status' => 'rejected', 'archived' => false]);

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
                                <th>Event Date</th>
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
                                    <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                    <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                    <td><?php echo date('M d', strtotime($inquiry['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-pending"><?php echo escape($inquiry['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(<?php echo (int)$inquiry['id']; ?>, 'inquiry')">Edit</button>
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
                 REJECTED INQUIRIES
                 ======================================== -->
            <div class="admin-card" style="margin-top: 30px; opacity: 0.85;">
                <div class="card-header">
                    <h2 class="card-title">❌ Rejected Inquiries (<?php echo count($rejected_inquiries); ?>)</h2>
                    <span class="card-subtitle">Declined inquiry requests</span>
                </div>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="rejected-inquiries-body">
                            <?php if (!empty($rejected_inquiries)): ?>
                                <?php foreach ($rejected_inquiries as $inquiry): ?>
                                <tr id="inquiry-<?php echo (int)$inquiry['id']; ?>" class="inquiry-row" data-inquiry-id="<?php echo (int)$inquiry['id']; ?>">
                                    <td><strong><?php echo escape($inquiry['full_name']); ?></strong></td>
                                    <td><?php echo escape($inquiry['email']); ?></td>
                                    <td><?php echo escape($inquiry['phone']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                    <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                    <td><?php echo date('M d', strtotime($inquiry['created_at'])); ?></td>
                                    <td>
                                        <span class="badge badge-rejected"><?php echo escape($inquiry['status']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="archiveItem(<?php echo (int)$inquiry['id']; ?>, 'inquiry')">📦 Archive</button>
                                            <span class="badge badge-danger">Rejected</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="empty-cell">No rejected inquiries.</td>
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
    </script>

    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
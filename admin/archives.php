<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

// Get all archived items
$all_archived_bookings = getArchivedBookings();
$archived_inquiries = getArchivedInquiries();

// Get sales report (all completed archived bookings)
$sales_report = getSalesReport();

// Filter out completed bookings from archived bookings (they're in sales report)
$archived_bookings = array_filter($all_archived_bookings, function($booking) {
    return strtolower($booking['status']) !== 'completed';
});

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Print Styles */
        @media print {
            .admin-sidebar, .no-print, .admin-header, .action-buttons {
                display: none !important;
            }
            .admin-main {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .admin-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            body {
                background: white;
            }
        }
        
        .receipt-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .receipt-modal.active {
            display: flex;
        }
        .receipt-content {
            background: white;
            max-width: 400px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 2rem;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        .receipt-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        .receipt-header p {
            margin: 0.25rem 0;
            font-size: 0.8rem;
        }
        .receipt-body {
            margin: 1rem 0;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            font-size: 0.9rem;
        }
        .receipt-row.total {
            border-top: 2px dashed #333;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .receipt-footer {
            text-align: center;
            border-top: 2px dashed #333;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 0.8rem;
        }
        .sales-summary {
            background: #f8f5f0;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        .sales-summary h3 {
            margin-top: 0;
            color: #4a1414;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        .stat-box {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #8a2927;
        }
        .stat-box .label {
            font-size: 0.8rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">📁 Archives</h1>
            </div>

            <!-- Sales Report Section -->
            <div class="admin-card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>💰 Sales Report</h2>
                    <button class="btn-admin btn-primary-admin no-print" onclick="printSalesReport()">
                        🖨️ Print Report
                    </button>
                </div>
                <div class="sales-summary">
                    <h3>Completed Bookings Summary</h3>
                    <div class="summary-stats">
                        <div class="stat-box">
                            <div class="number"><?php echo $sales_report['count'] ?? 0; ?></div>
                            <div class="label">Total Events</div>
                        </div>
                        <div class="stat-box">
                            <div class="number">₱<?php echo number_format($sales_report['total_sales'] ?? 0, 2); ?></div>
                            <div class="label">Total Sales</div>
                        </div>
                        <div class="stat-box">
                            <div class="number"><?php echo count($archived_bookings); ?></div>
                            <div class="label">Archived Bookings</div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($sales_report['bookings'])): ?>
                    <table class="admin-table" id="salesTable">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Event Date</th>
                                <th>Amount</th>
                                <th>Archived Date</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_report['bookings'] as $booking): ?>
                            <tr id="booking-<?php echo $booking['id']; ?>">
                                <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                <td><?php echo escape($booking['customer_email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                <td><strong>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($booking['archived_at'])); ?></td>
                                <td class="no-print action-buttons">
                                    <button class="btn-admin btn-secondary-admin btn-small" onclick="showReceipt('booking', <?php echo (int)$booking['id']; ?>)">🧾 Receipt</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No completed bookings in archives yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Archived Bookings -->
            <div class="admin-card" style="margin-top: 1.5rem;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>📦 Archived Bookings</h2>
                    <div class="no-print">
                        <button class="btn-admin btn-primary-admin btn-small" onclick="printSection('archivedBookings')">🖨️ Print All</button>
                    </div>
                </div>
                <div id="archivedBookings">
                    <?php if (!empty($archived_bookings)): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Guests</th>
                                    <th>Total</th>
                                    <th>Event Date</th>
                                    <th>Status</th>
                                    <th>Archived</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_bookings as $booking): ?>
                                <tr id="archived-booking-<?php echo $booking['id']; ?>">
                                    <td><strong><?php echo escape($booking['customer_name']); ?></strong></td>
                                    <td><?php echo escape($booking['customer_email']); ?></td>
                                    <td><?php echo (int)$booking['guest_count']; ?> pax</td>
                                    <td><strong>₱<?php echo number_format((float)$booking['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $booking['status']; ?>"><?php echo escape($booking['status']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($booking['archived_at'])); ?></td>
                                    <td class="no-print action-buttons">
                                        <button class="btn-admin btn-secondary-admin btn-small" onclick="showReceipt('booking', <?php echo (int)$booking['id']; ?>)">🧾 Receipt</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No archived bookings yet. Archive completed or cancelled bookings from the Bookings page.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Archived Inquiries -->
            <div class="admin-card" style="margin-top: 1.5rem;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>📨 Archived Inquiries</h2>
                    <div class="no-print">
                        <button class="btn-admin btn-primary-admin btn-small" onclick="printSection('archivedInquiries')">🖨️ Print All</button>
                    </div>
                </div>
                <div id="archivedInquiries">
                    <?php if (!empty($archived_inquiries)): ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Event Date</th>
                                    <th>Guests</th>
                                    <th>Status</th>
                                    <th>Archived</th>
                                    <th class="no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_inquiries as $inquiry): ?>
                                <tr id="archived-inquiry-<?php echo $inquiry['id']; ?>">
                                    <td><strong><?php echo escape($inquiry['full_name']); ?></strong></td>
                                    <td><?php echo escape($inquiry['email']); ?></td>
                                    <td><?php echo escape($inquiry['phone']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($inquiry['event_date'])); ?></td>
                                    <td><?php echo (int)$inquiry['guest_count']; ?> pax</td>
                                    <td>
                                        <span class="badge badge-rejected"><?php echo escape($inquiry['status']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($inquiry['archived_at'])); ?></td>
                                    <td class="no-print action-buttons">
                                        <button class="btn-admin btn-secondary-admin btn-small" onclick="showReceipt('inquiry', <?php echo (int)$inquiry['id']; ?>)">🧾 Receipt</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No archived inquiries yet. Archive rejected inquiries from the Inquiries page.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div class="receipt-modal" id="receiptModal">
        <div class="receipt-content">
            <div class="receipt-header">
                <h2>ARC KITCHEN</h2>
                <p>Catering Services</p>
                <p id="receiptDate"></p>
            </div>
            <div class="receipt-body" id="receiptBody">
                <!-- Dynamic content -->
            </div>
            <div class="receipt-footer">
                <p>Thank you for choosing ARC Kitchen!</p>
                <p>For inquiries: info@arckitchen.com</p>
            </div>
            <div class="no-print" style="margin-top: 1.5rem; text-align: center;">
                <button class="btn-admin btn-primary-admin" onclick="window.print()">🖨️ Print Receipt</button>
                <button class="btn-admin btn-secondary-admin" onclick="closeReceipt()" style="margin-left: 0.5rem;">Close</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Store data for receipt generation
        const bookingsData = <?php echo json_encode($archived_bookings); ?>;
        const inquiriesData = <?php echo json_encode($archived_inquiries); ?>;
        const salesData = <?php echo json_encode($sales_report['bookings'] ?? []); ?>;

        function showReceipt(type, id) {
            let data;
            if (type === 'booking') {
                data = bookingsData.find(b => b.id == id) || salesData.find(b => b.id == id);
            } else {
                data = inquiriesData.find(i => i.id == id);
            }
            
            if (!data) {
                alert('Item not found');
                return;
            }

            const modal = document.getElementById('receiptModal');
            const body = document.getElementById('receiptBody');
            const dateEl = document.getElementById('receiptDate');
            
            dateEl.textContent = new Date().toLocaleDateString('en-US', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
            });

            let html = '';
            if (type === 'booking') {
                html = `
                    <div class="receipt-row">
                        <span>Type:</span>
                        <span>BOOKING</span>
                    </div>
                    <div class="receipt-row">
                        <span>Customer:</span>
                        <span>${data.customer_name}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Email:</span>
                        <span>${data.customer_email}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Event Date:</span>
                        <span>${new Date(data.event_date).toLocaleDateString()}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Guests:</span>
                        <span>${data.guest_count} pax</span>
                    </div>
                    <div class="receipt-row total">
                        <span>TOTAL:</span>
                        <span>₱${parseFloat(data.total_amount).toFixed(2)}</span>
                    </div>
                `;
            } else {
                html = `
                    <div class="receipt-row">
                        <span>Type:</span>
                        <span>INQUIRY</span>
                    </div>
                    <div class="receipt-row">
                        <span>Customer:</span>
                        <span>${data.full_name}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Email:</span>
                        <span>${data.email}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Phone:</span>
                        <span>${data.phone}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Event Date:</span>
                        <span>${new Date(data.event_date).toLocaleDateString()}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Guests:</span>
                        <span>${data.guest_count} pax</span>
                    </div>
                    <div class="receipt-row">
                        <span>Status:</span>
                        <span>${data.status.toUpperCase()}</span>
                    </div>
                `;
            }
            
            body.innerHTML = html;
            modal.classList.add('active');
        }

        function closeReceipt() {
            document.getElementById('receiptModal').classList.remove('active');
        }

        function printSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (!section) return;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>ARC Kitchen Archives</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background: #f5f5f5; font-weight: bold; }
                        h2 { color: #4a1414; }
                        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
                        .badge-completed { background: #d4edda; color: #155724; }
                        .badge-cancelled { background: #f8d7da; color: #721c24; }
                        .badge-rejected { background: #f8d7da; color: #721c24; }
                    </style>
                </head>
                <body>
                    <h1>📁 ARC Kitchen Archives</h1>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <hr>
                    ${section.innerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        function printSalesReport() {
            const printWindow = window.open('', '_blank');
            const table = document.getElementById('salesTable');
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Sales Report - ARC Kitchen</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background: #f5f5f5; font-weight: bold; }
                        h1 { color: #4a1414; }
                        .summary { background: #f8f5f0; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
                        .stat { display: inline-block; margin-right: 40px; }
                        .stat-value { font-size: 1.5rem; font-weight: bold; color: #8a2927; }
                        .stat-label { font-size: 0.9rem; color: #666; }
                    </style>
                </head>
                <body>
                    <h1>💰 Sales Report</h1>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <div class="summary">
                        <div class="stat">
                            <div class="stat-value">${salesData.length}</div>
                            <div class="stat-label">Total Events</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">₱${salesData.reduce((sum, b) => sum + parseFloat(b.total_amount || 0), 0).toFixed(2)}</div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                    </div>
                    <hr>
                    ${table ? table.outerHTML : '<p>No data available</p>'}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Close modal on backdrop click
        document.getElementById('receiptModal').addEventListener('click', function(e) {
            if (e.target === this) closeReceipt();
        });
    </script>
</body>
</html>
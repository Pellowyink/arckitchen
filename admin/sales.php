<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

// Get bookings with payment data
$bookings = getBookings();

// Calculate comprehensive sales stats
$totalRevenue = 0;
$totalDownPayments = 0;
$totalFullPayments = 0;
$totalBalance = 0;
$confirmedEvents = 0;
$completedEvents = 0;
$fullyPaidCount = 0;
$partialPaidCount = 0;
$pendingPaymentCount = 0;

foreach ($bookings as $booking) {
    $total = (float)($booking['total_amount'] ?? 0);
    $downPayment = (float)($booking['down_payment'] ?? 0);
    $fullPayment = (float)($booking['full_payment'] ?? 0);
    $paid = $downPayment + $fullPayment;
    $balance = $total - $paid;
    
    $totalRevenue += $total;
    $totalDownPayments += $downPayment;
    $totalFullPayments += $fullPayment;
    $totalBalance += max(0, $balance);
    
    if ($booking['status'] === 'confirmed') {
        $confirmedEvents++;
    }
    if ($booking['status'] === 'completed') {
        $completedEvents++;
    }
    
    $paymentStatus = $booking['payment_status'] ?? 'pending';
    if ($paymentStatus === 'fully_paid') {
        $fullyPaidCount++;
    } elseif ($paymentStatus === 'partial') {
        $partialPaidCount++;
    } else {
        $pendingPaymentCount++;
    }
}

$totalCollected = $totalDownPayments + $totalFullPayments;

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Enhanced Sales Report Styles */
        .filter-bar {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            border-color: #8a2927;
            color: #8a2927;
        }
        .filter-btn.active {
            background: #8a2927;
            color: white;
            border-color: #8a2927;
        }
        .date-input {
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
        }
        .bulk-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .metric-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 25px;
            padding: 1.5rem;
            text-align: center;
            position: relative;
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .metric-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            color: #666;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            color: #4a1414;
            margin: 0.5rem 0;
        }
        .growth-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .growth-up {
            color: #4CAF50;
            background: #e8f5e9;
        }
        .growth-down {
            color: #f44336;
            background: #ffebee;
        }
        .zero-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #faf9f7;
            border-radius: 25px;
            border: 2px dashed #ddd;
        }
        .zero-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .zero-state h3 {
            color: #4a1414;
            margin-bottom: 0.5rem;
        }
        .zero-state p {
            color: #666;
        }
        .checkbox-col {
            width: 40px;
            text-align: center;
        }
        .row-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        #selectAll {
            cursor: pointer;
        }
        .selected-count {
            font-size: 0.9rem;
            color: #666;
            margin-left: 0.5rem;
        }
        .payment-breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .breakdown-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        .breakdown-label {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        .breakdown-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4a1414;
        }
        .action-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .filter-btn-sm {
            transition: all 0.2s ease;
        }
        .filter-btn-sm:hover {
            border-color: #8a2927 !important;
            color: #8a2927;
            background: #fdf6f6 !important;
        }
        .filter-btn-sm.active {
            background: #8a2927 !important;
            color: white !important;
            border-color: #8a2927 !important;
        }
        .date-input-sm:focus {
            outline: none;
            border-color: #8a2927;
        }
        @media print {
            .admin-sidebar, .compact-filter-bar, .action-toolbar, .no-print, .bulk-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">💰 Sales Report</h1>
            </div>

            <!-- Live Metrics Cards -->
            <div class="stats-grid" id="metricsContainer">
                <div class="metric-card">
                    <div class="metric-icon">💰</div>
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-value" id="totalRevenue">₱0.00</div>
                    <div class="growth-indicator" id="revenueGrowth"></div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">💵</div>
                    <div class="metric-label">Total Collected</div>
                    <div class="metric-value" id="totalCollected" style="color: #4CAF50;">₱0.00</div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">⏳</div>
                    <div class="metric-label">Pending Balance</div>
                    <div class="metric-value" id="totalBalance" style="color: #f44336;">₱0.00</div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon">✅</div>
                    <div class="metric-label">Fully Paid</div>
                    <div class="metric-value" id="fullyPaidCount" style="color: #4CAF50;">0</div>
                </div>
            </div>

            <!-- Payment Breakdown -->
            <div class="admin-card" id="breakdownSection">
                <h2 style="color: #4a1414; margin-bottom: 1rem;">💳 Payment Breakdown</h2>
                <div class="payment-breakdown-grid">
                    <div class="breakdown-card">
                        <div class="breakdown-label">Down Payments</div>
                        <div class="breakdown-value" id="downPayments">₱0.00</div>
                    </div>
                    <div class="breakdown-card">
                        <div class="breakdown-label">Full Payments</div>
                        <div class="breakdown-value" id="fullPayments">₱0.00</div>
                    </div>
                    <div class="breakdown-card">
                        <div class="breakdown-label">Partially Paid</div>
                        <div class="breakdown-value" id="partialCount" style="color: #FF9800;">0 bookings</div>
                    </div>
                    <div class="breakdown-card">
                        <div class="breakdown-label">Payment Pending</div>
                        <div class="breakdown-value" id="pendingCount" style="color: #9e9e9e;">0 bookings</div>
                    </div>
                </div>
            </div>

            <!-- Sales Intelligence Table -->
            <div class="admin-card" id="salesTableContainer">
                <!-- Filter Bar -->
                <div class="filter-container no-print" style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 12px; border: 1px solid #eee; display: flex; align-items: flex-end; gap: 0.75rem; flex-wrap: wrap;">
                    <div class="filter-group">
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: #8a2927; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">📊 Filter</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="filter-btn-sm active" data-filter="all" style="padding: 0.5rem 0.75rem; font-size: 0.85rem; border: 1px solid #8a2927; background: #8a2927; color: white; border-radius: 6px; cursor: pointer; font-weight: 500;">All</button>
                            <button class="filter-btn-sm" data-filter="weekly" style="padding: 0.5rem 0.75rem; font-size: 0.85rem; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer; font-weight: 500;">Week</button>
                            <button class="filter-btn-sm" data-filter="monthly" style="padding: 0.5rem 0.75rem; font-size: 0.85rem; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer; font-weight: 500;">Month</button>
                            <button class="filter-btn-sm" data-filter="yearly" style="padding: 0.5rem 0.75rem; font-size: 0.85rem; border: 1px solid #ddd; background: white; border-radius: 6px; cursor: pointer; font-weight: 500;">Year</button>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: #8a2927; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">📅 From</label>
                        <input type="date" id="dateFrom" style="padding: 0.5rem; font-size: 0.85rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; width: 130px;">
                    </div>
                    <div class="filter-group">
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: #8a2927; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem;">📅 To</label>
                        <input type="date" id="dateTo" style="padding: 0.5rem; font-size: 0.85rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; width: 130px;">
                    </div>
                    <div class="filter-group">
                        <label style="display: block; font-size: 0.7rem; font-weight: 700; color: transparent; margin-bottom: 0.25rem;">Action</label>
                        <button class="filter-btn-sm" data-filter="custom" style="padding: 0.5rem 1rem; font-size: 0.85rem; border: 1px solid #8a2927; background: white; color: #8a2927; border-radius: 8px; cursor: pointer; font-weight: 600;">Go</button>
                    </div>
                    <div style="margin-left: auto; display: flex; gap: 0.5rem; align-items: flex-end;">
                        <button id="clearFilters" style="padding: 0.5rem 1rem; font-size: 0.85rem; border: 1px solid #ddd; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                            ✕ Clear
                        </button>
                        <button id="sortNewest" style="padding: 0.5rem 1rem; font-size: 0.85rem; border: 1px solid #8a2927; background: #8a2927; color: white; border-radius: 8px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
                            ↕ Newest
                        </button>
                    </div>
                </div>

                <div class="action-toolbar">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label for="selectAll" style="cursor: pointer;"></label>
                        <span class="selected-count" id="selectedCount"></span>
                    </div>
                    <div class="bulk-actions">
                        <button class="btn-admin btn-secondary-admin btn-small" id="exportCsvBtn">
                            📊 Export CSV
                        </button>
                        <button class="btn-admin btn-primary-admin btn-small" id="bulkPrintBtn" disabled>
                            🖨️ Print Selected
                        </button>
                    </div>
                </div>

                <h3 style="color: #4a1414; margin: 1rem 0;">📋 Completed Bookings (Sales)</h3>
                
                <!-- Zero State -->
                <div class="zero-state" id="zeroState" style="display: none;">
                    <div class="zero-state-icon">🍳</div>
                    <h3>No revenue recorded for this period yet.</h3>
                    <p>Keep cooking! Sales will appear here once bookings are completed.</p>
                </div>

                <div class="table-responsive" id="tableWrapper">
                    <table class="admin-table" id="salesTable">
                        <thead>
                            <tr>
                                <th class="checkbox-col"><input type="checkbox" id="selectAllHeader" title="Select All"></th>
                                <th>Customer</th>
                                <th>Event Date</th>
                                <th>Total Cost</th>
                                <th>Down Payment</th>
                                <th>Full Payment</th>
                                <th>Balance</th>
                                <th>Payment Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="salesTableBody">
                            <!-- Dynamically populated -->
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/print-engine.js"></script>
    <script>
        // Sales Report Dynamic System
        let currentBookingsData = [];
        let selectedIds = new Set();

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data (all time)
            loadSalesData('all');
            
            // Setup filter buttons
            document.querySelectorAll('.filter-btn-sm[data-filter]').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all
                    document.querySelectorAll('.filter-btn-sm').forEach(b => b.classList.remove('active'));
                    // Add active to clicked
                    this.classList.add('active');
                    
                    const filter = this.dataset.filter;
                    if (filter === 'custom') {
                        const dateFrom = document.getElementById('dateFrom').value;
                        const dateTo = document.getElementById('dateTo').value;
                        if (dateFrom && dateTo) {
                            loadSalesData('custom', dateFrom, dateTo);
                        } else {
                            if (typeof showArcError === 'function') {
                                showArcError('Please select both From and To dates');
                            } else {
                                alert('Please select both From and To dates');
                            }
                        }
                    } else {
                        loadSalesData(filter);
                    }
                });
            });

            // Select All functionality
            document.getElementById('selectAll').addEventListener('change', function() {
                toggleSelectAll(this.checked);
            });
            
            document.getElementById('selectAllHeader').addEventListener('change', function() {
                toggleSelectAll(this.checked);
            });

            // Clear Filters button
            document.getElementById('clearFilters').addEventListener('click', function() {
                document.getElementById('dateFrom').value = '';
                document.getElementById('dateTo').value = '';
                // Reset to All Time
                document.querySelectorAll('.filter-btn-sm').forEach(b => b.classList.remove('active'));
                document.querySelector('[data-filter="all"]').classList.add('active');
                loadSalesData('all');
            });

            // Sort Newest button
            let sortDesc = true;
            document.getElementById('sortNewest').addEventListener('click', function() {
                sortDesc = !sortDesc;
                this.innerHTML = sortDesc ? '↓ Newest' : '↑ Oldest';
                currentBookingsData.sort((a, b) => {
                    const dateA = new Date(a.event_date);
                    const dateB = new Date(b.event_date);
                    return sortDesc ? dateB - dateA : dateA - dateB;
                });
                updateTable(currentBookingsData);
            });

            // Export CSV button
            document.getElementById('exportCsvBtn').addEventListener('click', exportToCSV);
            
            // Bulk Print button
            document.getElementById('bulkPrintBtn').addEventListener('click', bulkPrintSelected);
        });

        // Load sales data via AJAX
        function loadSalesData(filter, dateFrom = '', dateTo = '') {
            showLoading(true);
            
            let url = `../api/filter-sales.php?filter=${filter}`;
            if (filter === 'custom' && dateFrom && dateTo) {
                url += `&date_from=${dateFrom}&date_to=${dateTo}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentBookingsData = data.bookings;
                        updateMetrics(data.metrics);
                        updateTable(data.bookings);
                        
                        // Update print engine data
                        if (window.printEngine) {
                            window.printEngine.setBookingsData(data.bookings);
                        }
                        
                        showLoading(false);
                        
                        if (typeof showArcSuccess === 'function') {
                            showArcSuccess(`Showing ${data.metrics.booking_count} sales records`);
                        }
                    } else {
                        showLoading(false);
                        if (typeof showArcError === 'function') {
                            showArcError('Failed to load sales data');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading sales data:', error);
                    showLoading(false);
                    if (typeof showArcError === 'function') {
                        showArcError('Error loading sales data');
                    }
                });
        }

        // Update metric cards with animation
        function updateMetrics(metrics) {
            animateValue('totalRevenue', metrics.total_revenue, '₱');
            animateValue('totalCollected', metrics.total_collected, '₱');
            animateValue('totalBalance', metrics.total_balance, '₱');
            animateValue('fullyPaidCount', metrics.fully_paid_count, '');
            
            // Update breakdown
            document.getElementById('downPayments').textContent = '₱' + metrics.total_down_payments.toFixed(2);
            document.getElementById('fullPayments').textContent = '₱' + metrics.total_full_payments.toFixed(2);
            document.getElementById('partialCount').textContent = metrics.partial_paid_count + ' bookings';
            document.getElementById('pendingCount').textContent = metrics.pending_payment_count + ' bookings';
            
            // Update growth indicator
            const growthEl = document.getElementById('revenueGrowth');
            if (metrics.growth_percent !== 0) {
                const isPositive = metrics.growth_percent > 0;
                growthEl.className = 'growth-indicator ' + (isPositive ? 'growth-up' : 'growth-down');
                growthEl.innerHTML = (isPositive ? '↑' : '↓') + ' ' + Math.abs(metrics.growth_percent) + '% vs last period';
            } else {
                growthEl.textContent = '';
            }
        }

        // Animate number changes
        function animateValue(id, value, prefix = '') {
            const el = document.getElementById(id);
            const start = parseFloat(el.textContent.replace(/[^0-9.-]/g, '')) || 0;
            const end = value;
            const duration = 800;
            const startTime = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeProgress = 1 - Math.pow(1 - progress, 3); // Ease out cubic
                
                const current = start + (end - start) * easeProgress;
                el.textContent = prefix + current.toFixed(2);
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }

        // Update sales table
        function updateTable(bookings) {
            const tbody = document.getElementById('salesTableBody');
            const zeroState = document.getElementById('zeroState');
            const tableWrapper = document.getElementById('tableWrapper');
            
            // Clear selections
            selectedIds.clear();
            updateBulkButtons();
            
            if (bookings.length === 0) {
                zeroState.style.display = 'block';
                tableWrapper.style.display = 'none';
                tbody.innerHTML = '';
                return;
            }
            
            zeroState.style.display = 'none';
            tableWrapper.style.display = 'block';
            
            tbody.innerHTML = bookings.map(booking => {
                const total = parseFloat(booking.total_amount || 0);
                const downPayment = parseFloat(booking.down_payment || 0);
                const fullPayment = parseFloat(booking.full_payment || 0);
                const paid = downPayment + fullPayment;
                const balance = Math.max(0, total - paid);
                
                let statusBadge = '';
                const status = booking.payment_status || 'pending';
                if (status === 'fully_paid') {
                    statusBadge = '<span class="badge" style="background: #d4edda; color: #155724;">✅ Fully Paid</span>';
                } else if (status === 'partial') {
                    statusBadge = '<span class="badge" style="background: #fff3cd; color: #856404;">💳 Partial</span>';
                } else {
                    statusBadge = '<span class="badge" style="background: #f8d7da; color: #721c24;">⏳ Pending</span>';
                }
                
                // Get customer name from either customer_name or full_name field
                const customerName = booking.customer_name || booking.full_name || 'N/A';
                const customerEmail = booking.customer_email || booking.email || 'N/A';
                
                return `
                    <tr>
                        <td class="checkbox-col"><input type="checkbox" class="row-checkbox" data-id="${booking.id}"></td>
                        <td>
                            <strong>${escapeHtml(customerName)}</strong>
                            <div style="font-size: 0.8rem; color: #666;">${escapeHtml(customerEmail)}</div>
                        </td>
                        <td>${new Date(booking.event_date).toLocaleDateString()}</td>
                        <td>₱${total.toFixed(2)}</td>
                        <td style="color: ${downPayment > 0 ? '#4CAF50' : '#999'};">${downPayment > 0 ? '₱' + downPayment.toFixed(2) : '-'}</td>
                        <td style="color: ${fullPayment > 0 ? '#4CAF50' : '#999'};">${fullPayment > 0 ? '₱' + fullPayment.toFixed(2) : '-'}</td>
                        <td style="color: ${balance > 0 ? '#f44336' : '#4CAF50'}; font-weight: ${balance > 0 ? '600' : '400'};">${balance > 0 ? '₱' + balance.toFixed(2) : 'PAID'}</td>
                        <td>${statusBadge}</td>
                        <td class="no-print action-buttons">
                            <button class="btn-admin btn-secondary-admin btn-small" onclick="showReceipt(${booking.id})">🧾 Receipt</button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            // Re-bind checkbox events
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const id = this.dataset.id;
                    if (this.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                    updateBulkButtons();
                    updateSelectAllCheckbox();
                });
            });
        }

        // Toggle all checkboxes
        function toggleSelectAll(checked) {
            document.querySelectorAll('.row-checkbox').forEach(cb => {
                cb.checked = checked;
                const id = cb.dataset.id;
                if (checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
            });
            updateBulkButtons();
        }

        // Update select all checkbox state
        function updateSelectAllCheckbox() {
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const selectAll = document.getElementById('selectAll');
            const selectAllHeader = document.getElementById('selectAllHeader');
            
            if (allCheckboxes.length > 0) {
                const isAllChecked = checkedBoxes.length === allCheckboxes.length;
                const isIndeterminate = checkedBoxes.length > 0 && checkedBoxes.length < allCheckboxes.length;
                
                selectAll.checked = isAllChecked;
                selectAll.indeterminate = isIndeterminate;
                selectAllHeader.checked = isAllChecked;
                selectAllHeader.indeterminate = isIndeterminate;
            }
        }

        // Update bulk action buttons
        function updateBulkButtons() {
            const bulkPrintBtn = document.getElementById('bulkPrintBtn');
            const count = selectedIds.size;
            
            bulkPrintBtn.disabled = count === 0;
            document.getElementById('selectedCount').textContent = count > 0 ? `${count} selected` : '';
        }

        // Bulk print selected
        function bulkPrintSelected() {
            if (selectedIds.size === 0) {
                if (typeof showArcError === 'function') {
                    showArcError('Please select at least one booking');
                }
                return;
            }
            
            const selectedBookings = currentBookingsData.filter(b => 
                selectedIds.has(b.id.toString())
            );
            
            if (window.printEngine) {
                window.printEngine.openPrintWindow(selectedBookings);
            }
        }

        // Show receipt for archived booking
        function showReceipt(id) {
            const booking = currentBookingsData.find(b => b.id == id);
            if (!booking) {
                if (typeof showArcError === 'function') {
                    showArcError('Booking not found');
                }
                return;
            }
            
            if (window.printEngine) {
                window.printEngine.openPrintWindow([booking]);
            }
        }

        // Show archived booking receipt
        function showArchivedReceipt(id) {
            // Get archived booking data from the DOM or create a fetch call
            alert('Archived receipt for booking #' + id);
        }

        // Show inquiry receipt
        function showInquiryReceipt(id) {
            alert('Inquiry receipt #' + id);
        }

        // Print archived bookings section
        function printArchivedSection() {
            const content = document.getElementById('archivedBookingsTable').innerHTML;
            openPrintWindow('Archived Bookings', content);
        }

        // Print archived inquiries section
        function printArchivedInquiriesSection() {
            const content = document.getElementById('archivedInquiriesTable').innerHTML;
            openPrintWindow('Archived Inquiries', content);
        }

        // Open print window
        function openPrintWindow(title, content) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>${title} - ARC Kitchen</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background: #f5f5f5; font-weight: bold; }
                        h1 { color: #4a1414; }
                        .no-print { display: none !important; }
                        .action-buttons { display: none !important; }
                    </style>
                </head>
                <body>
                    <h1>📁 ARC Kitchen - ${title}</h1>
                    <p>Generated: ${new Date().toLocaleString()}</p>
                    <hr>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Export to CSV
        function exportToCSV() {
            if (currentBookingsData.length === 0) {
                if (typeof showArcError === 'function') {
                    showArcError('No data to export');
                } else {
                    alert('No data to export');
                }
                return;
            }
            
            if (window.printEngine) {
                window.printEngine.exportToCSV();
            }
        }

        // Show loading state
        function showLoading(show) {
            if (typeof showArcLoading === 'function' && show) {
                showArcLoading('Loading sales data...');
            } else if (typeof hideArcLoading === 'function' && !show) {
                hideArcLoading();
            }
        }

        // Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
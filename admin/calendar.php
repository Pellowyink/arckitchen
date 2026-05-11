<?php
/**
 * Admin: Calendar with Blocked Dates Management
 * View bookings and manage unavailable dates
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

$errors = [];
$success = '';

// Handle form submissions
if (isPostRequest()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'block_date') {
        $date = $_POST['date'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if ($date && strtotime($date) >= strtotime(date('Y-m-d'))) {
            if (saveCalendarSetting($date, true, $reason)) {
                $success = 'Date blocked successfully.';
            } else {
                $errors[] = 'Failed to block date.';
            }
        } else {
            $errors[] = 'Please select a valid future date.';
        }
    }
    
    if ($action === 'unblock_date') {
        $date = $_POST['date'] ?? '';
        if ($date) {
            if (saveCalendarSetting($date, false, '', null, 'available', false)) {
                $success = 'Date unblocked successfully.';
            } else {
                $errors[] = 'Failed to unblock date.';
            }
        }
    }
}

// Get current month view
$currentMonth = $_GET['month'] ?? date('m');
$currentYear = $_GET['year'] ?? date('Y');

$calendarSettings = getCalendarSettings($currentMonth, $currentYear);
$calendarStatusMap = getCalendarStatusMap($currentMonth, $currentYear);
$blockedDates = array_filter($calendarStatusMap, fn($setting) => !empty($setting['is_blocked']));

// Get upcoming bookings for list
$bookings = getInquiries();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .calendar-wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.35rem;
            margin-top: 0.75rem;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            color: #8a2927;
            padding: 0.4rem;
            font-size: 0.75rem;
        }
        .calendar-day {
            aspect-ratio: 1;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 0.85rem;
            border: 1px solid transparent;
            transition: all 0.2s;
            min-height: 38px;
        }
        .calendar-day.empty {
            background: transparent;
        }
        .calendar-day.available,
        .calendar-day.state-available {
            background-color: #2ecc71 !important;
            border-color: rgba(138, 41, 39, 0.1);
            color: #333;
        }
        .calendar-day.available,
        .calendar-day.state-available,
        .calendar-day.state-limited {
            cursor: pointer;
        }
        .calendar-day.available:hover,
        .calendar-day.state-available:hover,
        .calendar-day.state-limited:hover {
            transform: scale(1.03);
            box-shadow: 0 3px 10px rgba(138, 41, 39, 0.18);
        }
        .calendar-day.date-blocked,
        .calendar-day.state-blocked {
            background-color: #d3d3d3 !important;
            color: #888;
            cursor: pointer;
            pointer-events: auto;
        }
        .calendar-day.state-full {
            background-color: #ff4d4d !important;
            color: #fff;
            cursor: pointer;
            pointer-events: auto;
        }
        .calendar-day.state-limited {
            background-color: #ffa500 !important;
            color: #4a1414;
        }
        .slot-count {
            font-size: 0.62rem;
            margin-top: 0.1rem;
            font-weight: 700;
            opacity: 0.9;
        }
        .calendar-day.past {
            background: #f5f5f5;
            color: #ccc;
        }
        .calendar-day.today {
            border-color: #d5a437;
            border-width: 2px;
        }
        .date-management-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 1100;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .date-management-modal.active {
            display: flex;
        }
        .date-management-card {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }
        .date-management-header {
            background: #8a2927;
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .date-management-header h2 {
            margin: 0;
            font-size: 1.1rem;
        }
        .date-management-close {
            border: 0;
            background: transparent;
            color: #fff;
            font-size: 1.8rem;
            line-height: 1;
            cursor: pointer;
        }
        .date-management-body {
            padding: 1.25rem;
        }
        .date-management-body label {
            display: block;
            margin-bottom: 0.35rem;
            color: #4a1414;
            font-weight: 700;
            font-size: 0.88rem;
        }
        .date-management-body input,
        .date-management-body select,
        .date-management-body textarea {
            width: 100%;
            padding: 0.7rem;
            border: 1px solid #dbc7b9;
            border-radius: 8px;
            font: inherit;
        }
        .date-management-body textarea {
            min-height: 90px;
            resize: vertical;
        }
        .date-management-field {
            margin-bottom: 1rem;
        }
        .date-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem;
            border: 1px solid #eadbd1;
            border-radius: 8px;
            background: #fffaf7;
        }
        .date-toggle-row input {
            width: auto;
        }
        .slot-summary {
            background: #f8f1ec;
            border-left: 4px solid #8a2927;
            padding: 0.75rem 1rem;
            border-radius: 0 8px 8px 0;
            margin-bottom: 1rem;
            color: #4a1414;
            font-weight: 600;
        }
        .date-management-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid #eee;
            background: #faf7f4;
        }
        /* Receipt Modal Styles */
        .order-receipt-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .order-receipt-modal.active {
            display: flex;
        }
        .receipt-container {
            background: white;
            border-radius: 25px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .receipt-header {
            background: #4a1414;
            color: white;
            padding: 1.5rem;
            border-radius: 25px 25px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .receipt-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }
        .receipt-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .receipt-body {
            padding: 1.5rem;
        }
        .receipt-section {
            margin-bottom: 1.5rem;
        }
        .receipt-section h3 {
            color: #4a1414;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        .receipt-info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .receipt-info-row:last-child {
            border-bottom: none;
        }
        .receipt-label {
            color: #666;
            font-size: 0.9rem;
        }
        .receipt-value {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        .receipt-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }
        .receipt-items-table th,
        .receipt-items-table td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 0.85rem;
        }
        .receipt-items-table th {
            font-weight: 600;
            color: #4a1414;
            background: #faf9f7;
        }
        .receipt-footer-actions {
            padding: 1rem 1.5rem;
            background: #faf9f7;
            border-radius: 0 0 25px 25px;
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .date-status-badge {
            font-size: 0.5rem;
            margin-top: 1px;
            line-height: 1;
        }
        .legend {
            display: flex;
            gap: 1rem;
            margin: 0.75rem 0;
            flex-wrap: wrap;
            justify-content: center;
            font-size: 0.8rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 3px;
        }
        .blocked-list {
            margin-top: 1rem;
        }
        .blocked-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            background: rgba(138, 41, 39, 0.05);
            border-radius: 8px;
            margin-bottom: 0.35rem;
            font-size: 0.9rem;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media (max-width: 900px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">📆 Calendar & Blocked Dates</h1>
                <p class="admin-subtitle">View bookings and manage unavailable dates</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo escape($success); ?></div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo escape($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="two-column">
                <!-- Left: Calendar -->
                <div class="admin-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h2 style="margin: 0;"><?php echo date('F Y', strtotime("$currentYear-$currentMonth-01")); ?></h2>
                        <div>
                            <?php
                            // Calculate prev/next from current displayed month
                            $currentMonthTime = strtotime("$currentYear-$currentMonth-01");
                            $prevMonthTime = strtotime('-1 month', $currentMonthTime);
                            $nextMonthTime = strtotime('+1 month', $currentMonthTime);
                            ?>
                            <a href="?month=<?php echo date('n', $prevMonthTime); ?>&year=<?php echo date('Y', $prevMonthTime); ?>" class="btn-admin btn-secondary-admin btn-small">← Prev</a>
                            <a href="?month=<?php echo date('n', $nextMonthTime); ?>&year=<?php echo date('Y', $nextMonthTime); ?>" class="btn-admin btn-secondary-admin btn-small">Next →</a>
                        </div>
                    </div>
                    
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #fff; border: 2px solid rgba(138, 41, 39, 0.1);"></div>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ffa500;"></div>
                            <span>Limited</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ff4d4d;"></div>
                            <span>Full</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #d3d3d3;"></div>
                            <span>Blocked</span>
                        </div>
                    </div>
                    
                    <div class="calendar-wrapper">
                        <div class="calendar-grid">
                            <?php
                            $days = ['S','M','T','W','T','F','S'];
                            foreach ($days as $day) {
                                echo "<div class='calendar-day-header'>$day</div>";
                            }
                            
                            $firstDay = strtotime("$currentYear-$currentMonth-01");
                            $daysInMonth = date('t', $firstDay);
                            $startWeekday = date('w', $firstDay);
                            $today = date('Y-m-d');
                            
                            // Empty cells
                            for ($i = 0; $i < $startWeekday; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }
                            
                            // Days
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                                $isToday = ($dateStr === $today);
                                $isPast = ($dateStr < $today);
                                
                                $availability = $calendarStatusMap[$dateStr] ?? checkDateAvailability($dateStr);
                                
                                $class = $availability['availability_class'];
                                $clickHandler = '';
                                
                                if ($isPast) {
                                    $class = 'past';
                                } else {
                                    $clickHandler = " onclick='openDateManagementModal(\"$dateStr\")'";
                                }
                                
                                if ($isToday) $class .= ' today';
                                
                                echo "<div class='calendar-day $class'$clickHandler data-date='" . escape($dateStr) . "'>";
                                echo "<span>$day</span>";
                                if (!empty($availability['is_blocked'])) {
                                    echo "<span class='slot-count'>Blocked</span>";
                                } elseif ($availability['status'] === 'full') {
                                    $bookingLabel = trim((string)($availability['booking_names'] ?? ''));
                                    $bookingIds = trim((string)($availability['booking_ids'] ?? ''));
                                    if ($bookingIds !== '') {
                                        $firstBookingId = trim(explode(',', $bookingIds)[0]);
                                        $bookingLabel = '#' . $firstBookingId . ($bookingLabel !== '' ? ' ' . $bookingLabel : '');
                                    }
                                    if ($bookingLabel !== '') {
                                        $bookingLabel = strlen($bookingLabel) > 18 ? substr($bookingLabel, 0, 18) . '...' : $bookingLabel;
                                        echo "<span class='slot-count'>" . escape($bookingLabel) . "</span>";
                                    } else {
                                        echo "<span class='slot-count'>" . (int)$availability['current_bookings'] . " booking</span>";
                                    }
                                } elseif ($availability['status'] === 'limited') {
                                    echo "<span class='slot-count'>Limited</span>";
                                } elseif ((int)$availability['current_bookings'] > 0) {
                                    echo "<span class='slot-count'>" . (int)$availability['current_bookings'] . "/" . (int)$availability['max_capacity'] . "</span>";
                                }
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Block Date Form & List -->
                <div>
                    <div class="admin-card" style="margin-bottom: 1rem;">
                        <h3 style="margin-bottom: 0.75rem;">🚫 Block a Date</h3>
                        <form method="post" class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <input type="hidden" name="action" value="block_date">
                            
                            <div class="field" style="margin-bottom: 0;">
                                <label style="font-size: 0.85rem;">Date</label>
                                <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>" style="padding: 0.5rem;">
                            </div>
                            
                            <div class="field" style="grid-column: 1 / -1; margin-bottom: 0;">
                                <label style="font-size: 0.85rem;">Admin Note (optional)</label>
                                <input type="text" name="reason" placeholder="e.g., Holiday, Maintenance" style="padding: 0.5rem;">
                            </div>
                            
                            <div class="field" style="grid-column: 1 / -1; margin-bottom: 0;">
                                <button type="submit" class="btn-admin btn-primary-admin btn-small" style="width: 100%;">Block Date</button>
                            </div>
                        </form>
                    </div>
                    
                    <?php if (!empty($blockedDates)): ?>
                    <div class="admin-card blocked-list">
                        <h3 style="margin-bottom: 0.5rem; font-size: 1rem;">📋 Blocked This Month</h3>
                        <?php foreach ($blockedDates as $blocked): ?>
                        <div class="blocked-item">
                            <div>
                                <strong><?php echo date('M d', strtotime($blocked['slot_date'])); ?></strong>
                                <?php if (!empty($blocked['admin_note'])): ?>
                                <small style="color: #666;">(<?php echo escape($blocked['admin_note']); ?>)</small>
                                <?php endif; ?>
                            </div>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="action" value="unblock_date">
                                <input type="hidden" name="date" value="<?php echo $blocked['slot_date']; ?>">
                                <button type="submit" class="btn-admin btn-danger-admin btn-small" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Unblock</button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Upcoming Bookings -->
            <div class="admin-card" style="margin-top: 1.5rem;">
                <h2>📅 Upcoming Events</h2>
                <?php if (!empty($bookings)): ?>
                    <table class="admin-table" style="margin-top: 1rem;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Event</th>
                                <th>Pax</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $upcoming = array_filter($bookings, fn($b) => $b['event_date'] >= date('Y-m-d'));
                            $upcoming = array_slice($upcoming, 0, 10);
                            foreach ($upcoming as $booking): 
                            ?>
                            <tr>
                                <td><strong><?php echo date('M d', strtotime($booking['event_date'])); ?></strong></td>
                                <td><?php echo escape($booking['full_name']); ?></td>
                                <td><?php echo escape($booking['event_type']); ?></td>
                                <td><?php echo (int)$booking['guest_count']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo escape($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: var(--text-soft); text-align: center; padding: 2rem;">No upcoming events.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Date Management Modal -->
    <div id="dateManagementModal" class="date-management-modal">
        <div class="date-management-card">
            <div class="date-management-header">
                <h2 id="dateManagementTitle">Manage Date</h2>
                <button type="button" class="date-management-close" onclick="closeDateManagementModal()">&times;</button>
            </div>
            <div class="date-management-body">
                <input type="hidden" id="managedDate">
                <div class="slot-summary" id="slotSummary">Loading date details...</div>

                <div class="date-management-field">
                    <label for="adminNote">Admin Comment</label>
                    <textarea id="adminNote" placeholder="Add internal notes or availability comments for this date"></textarea>
                </div>

                <div class="date-management-field">
                    <label for="dateStatus">Manual Status</label>
                    <select id="dateStatus">
                        <option value="available">Available</option>
                        <option value="limited">Limited</option>
                        <option value="full">Full</option>
                    </select>
                </div>

                <div class="date-management-field">
                    <label for="maxCapacity">Max Capacity</label>
                    <input type="number" id="maxCapacity" min="1" step="1" value="<?php echo getDefaultCalendarCapacity(); ?>">
                </div>

                <div class="date-toggle-row">
                    <div>
                        <strong style="color: #4a1414;">Block this Date (Mark as Unavailable)</strong>
                        <div style="color: #777; font-size: 0.85rem;">Blocked dates appear gray and cannot be selected by customers.</div>
                    </div>
                    <input type="checkbox" id="dateBlockedToggle">
                </div>

                <div id="autoFullNotice" class="slot-summary" style="display: none; margin-top: 1rem; background: #fff1f1; border-left-color: #ff4d4d;">
                    This date is automatically marked FULL due to an approved or confirmed booking.
                </div>
            </div>
            <div class="date-management-actions">
                <button type="button" class="btn-admin btn-secondary-admin" onclick="viewManagedDateOrders()">View Orders</button>
                <button type="button" id="overrideLimitedButton" class="btn-admin btn-secondary-admin" style="display: none;" onclick="overrideManagedDate('limited')">Override & Re-Open Limited</button>
                <button type="button" id="overrideOpenButton" class="btn-admin btn-secondary-admin" style="display: none;" onclick="overrideManagedDate('open')">Override & Re-Open</button>
                <button type="button" class="btn-admin btn-danger-admin" onclick="resetManagedDate()">Unblock / Reset Date</button>
                <button type="button" class="btn-admin btn-secondary-admin" onclick="closeDateManagementModal()">Cancel</button>
                <button type="button" class="btn-admin btn-primary-admin" onclick="saveDateSettings()">Save Settings</button>
            </div>
        </div>
    </div>

    <!-- Order Receipt Modal -->
    <div id="orderReceiptModal" class="order-receipt-modal">
        <div class="receipt-container">
            <div class="receipt-header">
                <h2>📋 Order Receipt</h2>
                <button class="receipt-close" onclick="closeOrderReceipt()">&times;</button>
            </div>
            <div class="receipt-body" id="receiptBody">
                <!-- Content loaded via AJAX -->
                <p style="text-align: center; padding: 2rem;">Loading order details...</p>
            </div>
            <div class="receipt-footer-actions">
                <button class="btn-admin btn-primary-admin" onclick="printCurrentReceipt()">🖨️ Print Receipt</button>
                <button class="btn-admin btn-secondary-admin" onclick="closeOrderReceipt()">Close</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentReceiptData = null;
        let currentManagedDate = null;

        function formatDisplayDate(dateStr) {
            return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function openDateManagementModal(dateStr) {
            currentManagedDate = dateStr;
            const modal = document.getElementById('dateManagementModal');
            const title = document.getElementById('dateManagementTitle');
            const summary = document.getElementById('slotSummary');
            const autoFullNotice = document.getElementById('autoFullNotice');
            const overrideLimitedButton = document.getElementById('overrideLimitedButton');
            const overrideOpenButton = document.getElementById('overrideOpenButton');

            document.getElementById('managedDate').value = dateStr;
            title.textContent = `Manage ${formatDisplayDate(dateStr)}`;
            summary.textContent = 'Loading slot details...';
            autoFullNotice.style.display = 'none';
            overrideLimitedButton.style.display = 'none';
            overrideOpenButton.style.display = 'none';
            modal.classList.add('active');

            fetch(`../api/get-date-settings.php?date=${encodeURIComponent(dateStr)}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to load date settings');
                    }

                    const setting = data.setting;
                    document.getElementById('adminNote').value = setting.admin_note || '';
                    document.getElementById('dateBlockedToggle').checked = parseInt(setting.is_blocked || 0, 10) === 1;
                    document.getElementById('dateStatus').value = ['available', 'limited', 'full'].includes(setting.status)
                        ? setting.status
                        : 'available';
                    document.getElementById('maxCapacity').value = parseInt(setting.max_capacity || data.default_capacity || 3, 10);

                    const bookings = parseInt(setting.current_bookings || 0, 10);
                    const capacity = parseInt(setting.max_capacity || data.default_capacity || 3, 10);
                    const stateLabels = { gray: 'Blocked', red: 'Full', orange: 'Limited', green: 'Available' };
                    const bookingNames = setting.booking_names ? ` (${setting.booking_names})` : '';
                    summary.textContent = `${stateLabels[setting.color_state] || 'Available'}: ${bookings} approved/confirmed booking(s)${bookingNames}.`;

                    if (setting.is_auto_full) {
                        autoFullNotice.style.display = 'block';
                        overrideLimitedButton.style.display = 'inline-flex';
                        overrideOpenButton.style.display = 'inline-flex';
                    }
                })
                .catch(error => {
                    console.error('Date settings error:', error);
                    summary.textContent = 'Unable to load date settings.';
                });
        }

        function closeDateManagementModal() {
            document.getElementById('dateManagementModal').classList.remove('active');
            currentManagedDate = null;
        }

        function viewManagedDateOrders() {
            const dateStr = document.getElementById('managedDate').value;
            closeDateManagementModal();
            showOrderReceipt(dateStr);
        }

        function saveDateSettings() {
            const dateStr = document.getElementById('managedDate').value;
            const adminNote = document.getElementById('adminNote').value;
            const isBlocked = document.getElementById('dateBlockedToggle').checked;
            const status = document.getElementById('dateStatus').value;
            const maxCapacity = parseInt(document.getElementById('maxCapacity').value || '3', 10);

            if (!dateStr) {
                alert('Please select a valid date.');
                return;
            }

            fetch('../api/manage-date.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    slot_date: dateStr,
                    admin_note: adminNote,
                    is_blocked: isBlocked ? 1 : 0,
                    status: status,
                    max_capacity: maxCapacity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save date settings');
                }
                window.location.reload();
            })
            .catch(error => {
                console.error('Save date settings error:', error);
                alert(error.message || 'Failed to save date settings.');
            });
        }

        function resetManagedDate() {
            const dateStr = document.getElementById('managedDate').value;

            if (!dateStr) {
                alert('Please select a valid date.');
                return;
            }

            fetch('../api/manage-date.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'reset',
                    slot_date: dateStr,
                    is_blocked: 0,
                    status: 'available',
                    max_capacity: <?php echo getDefaultCalendarCapacity(); ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to reset date settings');
                }
                window.location.reload();
            })
            .catch(error => {
                console.error('Reset date settings error:', error);
                alert(error.message || 'Failed to reset date settings.');
            });
        }

        function overrideManagedDate(mode) {
            const dateStr = document.getElementById('managedDate').value;
            const adminNote = document.getElementById('adminNote').value;
            const maxCapacity = parseInt(document.getElementById('maxCapacity').value || '3', 10);
            const action = mode === 'open' ? 'override_open' : 'override_limited';

            if (!dateStr) {
                alert('Please select a valid date.');
                return;
            }

            fetch('../api/manage-date.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: action,
                    slot_date: dateStr,
                    admin_note: adminNote,
                    is_blocked: 0,
                    status: mode === 'open' ? 'open' : 'limited',
                    max_capacity: maxCapacity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to override date settings');
                }
                window.location.reload();
            })
            .catch(error => {
                console.error('Override date settings error:', error);
                alert(error.message || 'Failed to override date settings.');
            });
        }
        
        /**
         * Show order receipt modal for a specific date
         */
        function showOrderReceipt(dateStr) {
            const modal = document.getElementById('orderReceiptModal');
            const body = document.getElementById('receiptBody');
            
            modal.classList.add('active');
            body.innerHTML = '<p style="text-align: center; padding: 2rem;">Loading order details...</p>';
            
            fetch(`../api/get-order-details.php?date=${dateStr}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.bookings.length > 0) {
                        currentReceiptData = data;
                        renderReceipt(data, dateStr);
                    } else if (data.capacity_note) {
                        // Show admin note for blocked dates without bookings.
                        body.innerHTML = `
                            <div class="receipt-section">
                                <h3>📅 ${new Date(dateStr).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h3>
                                <div style="padding: 1.5rem; background: #fff3cd; border-radius: 12px; margin: 1rem 0;">
                                    <p style="margin: 0; color: #856404; font-weight: 600;">
                                        ⚠️ ${data.capacity_note}
                                    </p>
                                </div>
                            </div>
                        `;
                    } else {
                        body.innerHTML = '<p style="text-align: center; padding: 2rem;">No orders found for this date.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    body.innerHTML = '<p style="text-align: center; padding: 2rem; color: #f44336;">Failed to load order details.</p>';
                });
        }
        
        /**
         * Render receipt content
         */
        function renderReceipt(data, dateStr) {
            const body = document.getElementById('receiptBody');
            const booking = data.bookings[0]; // Show first booking, or loop for multiple
            
            let itemsHtml = '';
            if (booking.order_items && booking.order_items.length > 0 && !booking.order_items[0].placeholder) {
                itemsHtml = `
                    <table class="receipt-items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${booking.order_items.map(item => `
                                <tr>
                                    <td>${item.item_name}</td>
                                    <td>${item.quantity}</td>
                                    <td>₱${parseFloat(item.total_price).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                itemsHtml = '<p style="color: #888; font-style: italic;">Full catering package details available upon request.</p>';
            }
            
            const balance = parseFloat(booking.calculated_balance || 0);
            const paid = parseFloat(booking.calculated_paid || 0);
            const total = parseFloat(booking.total_amount || 0);
            
            body.innerHTML = `
                <div class="receipt-section">
                    <h3>📅 ${new Date(dateStr).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</h3>
                    ${data.capacity_note ? `
                    <div style="padding: 0.75rem 1rem; background: #fff3cd; border-radius: 8px; margin-bottom: 1rem;">
                        <p style="margin: 0; color: #856404; font-size: 0.9rem;">⚠️ ${data.capacity_note}</p>
                    </div>
                    ` : ''}
                </div>
                
                <div class="receipt-section">
                    <h3>👤 Customer Information</h3>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Name:</span>
                        <span class="receipt-value">${booking.customer_name || 'N/A'}</span>
                    </div>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Email:</span>
                        <span class="receipt-value">${booking.customer_email || 'N/A'}</span>
                    </div>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Phone:</span>
                        <span class="receipt-value">${booking.customer_phone || 'N/A'}</span>
                    </div>
                </div>
                
                <div class="receipt-section">
                    <h3>🎉 Event Details</h3>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Event Type:</span>
                        <span class="receipt-value">${booking.event_type || 'Standard Event'}</span>
                    </div>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Guests:</span>
                        <span class="receipt-value">${booking.guest_count || 0} pax</span>
                    </div>
                    ${booking.special_requests ? `
                    <div class="receipt-info-row">
                        <span class="receipt-label">Special Requests:</span>
                        <span class="receipt-value">${booking.special_requests}</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="receipt-section">
                    <h3>🍽️ Order Summary</h3>
                    ${itemsHtml}
                </div>
                
                <div class="receipt-section">
                    <h3>💰 Payment Details</h3>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Total Amount:</span>
                        <span class="receipt-value">₱${total.toFixed(2)}</span>
                    </div>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Amount Paid:</span>
                        <span class="receipt-value" style="color: #4CAF50;">₱${paid.toFixed(2)}</span>
                    </div>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Balance:</span>
                        <span class="receipt-value" style="color: ${balance > 0 ? '#f44336' : '#4CAF50'};">
                            ${balance > 0 ? '₱' + balance.toFixed(2) : 'PAID ✓'}
                        </span>
                    </div>
                    <div class="receipt-info-row">
                        <span class="receipt-label">Payment Status:</span>
                        <span class="receipt-value" style="color: ${booking.payment_status_display?.color || '#888'};">
                            ${booking.payment_status_display?.label || 'Pending'}
                        </span>
                    </div>
                </div>
                
                ${data.bookings.length > 1 ? `
                <div class="receipt-section">
                    <p style="color: #888; font-size: 0.85rem;">📊 ${data.bookings.length} total bookings on this date</p>
                </div>
                ` : ''}
            `;
        }
        
        /**
         * Close order receipt modal
         */
        function closeOrderReceipt() {
            document.getElementById('orderReceiptModal').classList.remove('active');
            currentReceiptData = null;
        }
        
        /**
         * Print current receipt
         */
        function printCurrentReceipt() {
            if (!currentReceiptData) return;
            
            const printWindow = window.open('', '_blank');
            const receiptContent = document.getElementById('receiptBody').innerHTML;
            
            printWindow.document.write(`
                <html>
                <head>
                    <title>Order Receipt - ARC Kitchen</title>
                    <style>
                        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
                        h2 { color: #4a1414; border-bottom: 2px solid #4a1414; padding-bottom: 10px; }
                        h3 { color: #4a1414; font-size: 0.9rem; text-transform: uppercase; margin-top: 20px; }
                        .receipt-info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                        .receipt-label { color: #666; }
                        .receipt-value { font-weight: 600; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background: #f5f5f5; }
                    </style>
                </head>
                <body>
                    <h2>🍽️ ARC Kitchen - Order Receipt</h2>
                    ${receiptContent}
                    <p style="text-align: center; margin-top: 30px; color: #888; font-size: 0.85rem;">
                        Thank you for choosing ARC Kitchen!
                    </p>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.print();
        }
        
        // Close modal when clicking outside
        document.getElementById('orderReceiptModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderReceipt();
            }
        });

        document.getElementById('dateManagementModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDateManagementModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOrderReceipt();
                closeDateManagementModal();
            }
        });
        
    </script>
</body>
</html>

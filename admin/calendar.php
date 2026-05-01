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
        $status = $_POST['status'] ?? 'blocked';
        
        if ($date && strtotime($date) >= strtotime(date('Y-m-d'))) {
            if (blockDate($date, $reason, $status)) {
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
            if (unblockDate($date)) {
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

// Get blocked dates for display
$blockedDates = getUnavailableDates($currentMonth, $currentYear);

// Get booked dates
$bookedDates = getBookedDates($currentMonth, $currentYear);

// Merge for display
$dateStatuses = [];
foreach ($blockedDates as $b) {
    $dateStatuses[$b['date']] = [
        'type' => $b['status'],
        'reason' => $b['reason']
    ];
}
foreach ($bookedDates as $b) {
    if (!isset($dateStatuses[$b['date']])) {
        $dateStatuses[$b['date']] = [
            'type' => 'booked',
            'count' => $b['booking_count']
        ];
    }
}

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
        .calendar-day.available {
            background: #fff;
            border-color: rgba(138, 41, 39, 0.1);
            color: #333;
        }
        .calendar-day.blocked {
            background: #9e9e9e;
            color: white;
        }
        .calendar-day.fully_booked {
            background: #f44336;
            color: white;
        }
        .calendar-day.booked {
            background: #ff9800;
            color: white;
        }
        .calendar-day.past {
            background: #f5f5f5;
            color: #ccc;
        }
        .calendar-day.today {
            border-color: #d5a437;
            border-width: 2px;
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
                            <a href="?month=<?php echo date('m', strtotime('-1 month')); ?>&year=<?php echo date('Y', strtotime('-1 month')); ?>" class="btn-admin btn-secondary-admin btn-small">←</a>
                            <a href="?month=<?php echo date('m', strtotime('+1 month')); ?>&year=<?php echo date('Y', strtotime('+1 month')); ?>" class="btn-admin btn-secondary-admin btn-small">→</a>
                        </div>
                    </div>
                    
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background: #fff; border: 1px solid rgba(138, 41, 39, 0.1);"></div>
                            <span>Free</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #ff9800;"></div>
                            <span>Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #f44336;"></div>
                            <span>Full</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background: #9e9e9e;"></div>
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
                                
                                $class = 'available';
                                $statusText = '';
                                
                                if ($isPast) {
                                    $class = 'past';
                                } elseif (isset($dateStatuses[$dateStr])) {
                                    $status = $dateStatuses[$dateStr]['type'];
                                    $class = $status;
                                    if ($status === 'booked') {
                                        $statusText = $dateStatuses[$dateStr]['count'];
                                    } elseif ($status === 'blocked') {
                                        $statusText = 'X';
                                    } elseif ($status === 'fully_booked') {
                                        $statusText = 'F';
                                    }
                                }
                                
                                if ($isToday) $class .= ' today';
                                
                                echo "<div class='calendar-day $class'>";
                                echo "<span>$day</span>";
                                if ($statusText) {
                                    echo "<span class='date-status-badge'>$statusText</span>";
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
                            
                            <div class="field" style="margin-bottom: 0;">
                                <label style="font-size: 0.85rem;">Status</label>
                                <select name="status" style="padding: 0.5rem;">
                                    <option value="blocked">Blocked</option>
                                    <option value="fully_booked">Fully Booked</option>
                                </select>
                            </div>
                            
                            <div class="field" style="grid-column: 1 / -1; margin-bottom: 0;">
                                <label style="font-size: 0.85rem;">Reason (optional)</label>
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
                                <strong><?php echo date('M d', strtotime($blocked['date'])); ?></strong>
                                <?php if ($blocked['reason']): ?>
                                <small style="color: #666;">(<?php echo escape($blocked['reason']); ?>)</small>
                                <?php endif; ?>
                            </div>
                            <form method="post" style="margin: 0;">
                                <input type="hidden" name="action" value="unblock_date">
                                <input type="hidden" name="date" value="<?php echo $blocked['date']; ?>">
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
</body>
</html>
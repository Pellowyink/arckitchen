<?php
/**
 * Admin: Blocked Dates Management
 * Manage unavailable dates for bookings
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocked Dates - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: 700;
            color: #8a2927;
            padding: 0.75rem;
            font-size: 0.9rem;
        }
        .calendar-day {
            aspect-ratio: 1;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .calendar-day.empty {
            background: transparent;
        }
        .calendar-day.available {
            background: #fff;
            border-color: rgba(138, 41, 39, 0.15);
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
            border-width: 3px;
        }
        .date-status-badge {
            font-size: 0.6rem;
            margin-top: 2px;
        }
        .legend {
            display: flex;
            gap: 1.5rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        .blocked-list {
            margin-top: 1.5rem;
        }
        .blocked-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: rgba(138, 41, 39, 0.05);
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">📅 Blocked Dates</h1>
                <p class="admin-subtitle">Manage unavailable dates and view booking calendar</p>
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
            
            <div class="admin-card">
                <h2>Block a Date</h2>
                <form method="post" class="form-grid">
                    <input type="hidden" name="action" value="block_date">
                    
                    <div class="field">
                        <label>Date *</label>
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="field">
                        <label>Reason</label>
                        <input type="text" name="reason" placeholder="e.g., Holiday, Maintenance">
                    </div>
                    
                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <option value="blocked">Blocked (Admin)</option>
                            <option value="fully_booked">Fully Booked</option>
                        </select>
                    </div>
                    
                    <div class="field" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-admin btn-primary-admin">Block Date</button>
                    </div>
                </form>
            </div>
            
            <div class="admin-card" style="margin-top: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2>Calendar View</h2>
                    <div>
                        <a href="?month=<?php echo date('m', strtotime('-1 month')); ?>&year=<?php echo date('Y', strtotime('-1 month')); ?>" class="btn-admin btn-secondary-admin btn-small">← Prev</a>
                        <span style="margin: 0 1rem; font-weight: 600;"><?php echo date('F Y', strtotime("$currentYear-$currentMonth-01")); ?></span>
                        <a href="?month=<?php echo date('m', strtotime('+1 month')); ?>&year=<?php echo date('Y', strtotime('+1 month')); ?>" class="btn-admin btn-secondary-admin btn-small">Next →</a>
                    </div>
                </div>
                
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background: #fff; border: 2px solid rgba(138, 41, 39, 0.15);"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #ff9800;"></div>
                        <span>Has Bookings</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #f44336;"></div>
                        <span>Fully Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #9e9e9e;"></div>
                        <span>Blocked</span>
                    </div>
                </div>
                
                <div class="calendar-grid">
                    <?php
                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
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
                                $statusText = $dateStatuses[$dateStr]['count'] . ' booking' . ($dateStatuses[$dateStr]['count'] > 1 ? 's' : '');
                            } elseif ($status === 'blocked') {
                                $statusText = 'Blocked';
                            } elseif ($status === 'fully_booked') {
                                $statusText = 'Full';
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
            
            <?php if (!empty($blockedDates)): ?>
            <div class="admin-card blocked-list">
                <h2>Currently Blocked Dates</h2>
                <?php foreach ($blockedDates as $blocked): ?>
                <div class="blocked-item">
                    <div>
                        <strong><?php echo date('F d, Y', strtotime($blocked['date'])); ?></strong>
                        <?php if ($blocked['reason']): ?>
                        <span style="color: #666; margin-left: 0.5rem;">(<?php echo escape($blocked['reason']); ?>)</span>
                        <?php endif; ?>
                        <span class="badge badge-<?php echo $blocked['status']; ?>" style="margin-left: 0.5rem;">
                            <?php echo ucfirst(str_replace('_', ' ', $blocked['status'])); ?>
                        </span>
                    </div>
                    <form method="post" style="margin: 0;">
                        <input type="hidden" name="action" value="unblock_date">
                        <input type="hidden" name="date" value="<?php echo $blocked['date']; ?>">
                        <button type="submit" class="btn-admin btn-danger-admin btn-small">Unblock</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

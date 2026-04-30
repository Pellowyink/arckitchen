<?php
/**
 * AJAX Calendar Endpoint
 * Returns calendar HTML for month navigation without page reload
 */

require_once __DIR__ . '/functions.php';

// Get month/year from request
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate
if ($currentMonth < 1 || $currentMonth > 12) $currentMonth = date('n');
if ($currentYear < 2020 || $currentYear > 2030) $currentYear = date('Y');

// Get unavailable dates for this month
$unavailableDates = getUnavailableDates($currentMonth, $currentYear);
$bookedDates = getBookedDates($currentMonth, $currentYear);

// Create lookup array
$dateStatus = [];
foreach ($unavailableDates as $u) {
    $dateStatus[$u['date']] = $u['status']; // 'blocked' or 'fully_booked'
}
foreach ($bookedDates as $b) {
    if (!isset($dateStatus[$b['date']])) {
        $dateStatus[$b['date']] = 'booked';
    }
}

// Calendar generation
$firstDay = strtotime("$currentYear-$currentMonth-01");
$daysInMonth = date('t', $firstDay);
$startWeekday = date('w', $firstDay); // 0 = Sunday
$today = date('Y-m-d');

// Month navigation
$prevMonth = date('m', strtotime('-1 month', $firstDay));
$prevYear = date('Y', strtotime('-1 month', $firstDay));
$nextMonth = date('m', strtotime('+1 month', $firstDay));
$nextYear = date('Y', strtotime('+1 month', $firstDay));

$monthName = date('F Y', $firstDay);
?>

<!-- Calendar Component -->
<div class="calendar-component" id="calendarComponent">
    <div class="calendar-header">
        <button type="button" class="calendar-nav" onclick="changeMonth(<?php echo (int)$prevMonth; ?>, <?php echo (int)$prevYear; ?>")">
            ← Prev
        </button>
        <h3 class="calendar-title"><?php echo $monthName; ?></h3>
        <button type="button" class="calendar-nav" onclick="changeMonth(<?php echo (int)$nextMonth; ?>, <?php echo (int)$nextYear; ?>")">
            Next →
        </button>
    </div>
    
    <div class="calendar-legend">
        <span class="legend-item"><span class="legend-dot available"></span> Available</span>
        <span class="legend-item"><span class="legend-dot booked"></span> Booked</span>
        <span class="legend-item"><span class="legend-dot blocked"></span> Unavailable</span>
    </div>
    
    <table class="calendar-table">
        <thead>
            <tr>
                <th>Sun</th>
                <th>Mon</th>
                <th>Tue</th>
                <th>Wed</th>
                <th>Thu</th>
                <th>Fri</th>
                <th>Sat</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $day = 1;
            $rows = ceil(($daysInMonth + $startWeekday) / 7);
            
            for ($row = 0; $row < $rows; $row++):
            ?>
            <tr>
                <?php for ($col = 0; $col < 7; $col++): 
                    $cellDay = $row * 7 + $col - $startWeekday + 1;
                    
                    if ($cellDay < 1 || $cellDay > $daysInMonth): 
                        echo '<td class="empty"></td>';
                    else:
                        $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $cellDay);
                        $isToday = ($dateStr === $today);
                        $isPast = ($dateStr < $today);
                        
                        $class = 'available';
                        $canSelect = true;
                        
                        if ($isPast) {
                            $class = 'past';
                            $canSelect = false;
                        } elseif (isset($dateStatus[$dateStr])) {
                            $status = $dateStatus[$dateStr];
                            if ($status === 'blocked') {
                                $class = 'blocked';
                                $canSelect = false;
                            } elseif ($status === 'fully_booked' || $status === 'booked') {
                                $class = 'booked';
                                $canSelect = false;
                            }
                        }
                        
                        if ($isToday) $class .= ' today';
                ?>
                <td class="<?php echo $class; ?>" 
                    data-date="<?php echo $dateStr; ?>"
                    <?php if ($canSelect): ?>onclick="selectDate('<?php echo $dateStr; ?>')"<?php endif; ?>>
                    <?php echo $cellDay; ?>
                    <?php if (isset($dateStatus[$dateStr]) && $dateStatus[$dateStr] === 'booked'): ?>
                    <span class="booking-indicator">●</span>
                    <?php endif; ?>
                </td>
                <?php endif; endfor; ?>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    
    <div class="calendar-selected" id="selectedDateDisplay" style="display: none;">
        <p>Selected Date: <strong id="selectedDateText"></strong></p>
        <input type="hidden" name="event_date" id="eventDateInput" required>
    </div>
</div>

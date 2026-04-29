<?php
/**
 * Calendar Component for Inquiry Page
 * Shows available/unavailable dates for booking
 * Include this in inquiry.php only
 */

// Get current month data
$currentMonth = $_GET['month'] ?? date('m');
$currentYear = $_GET['year'] ?? date('Y');

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
        <button type="button" class="calendar-nav" onclick="changeMonth(<?php echo $prevMonth; ?>, <?php echo $prevYear; ?>)">
            ← Prev
        </button>
        <h3 class="calendar-title"><?php echo $monthName; ?></h3>
        <button type="button" class="calendar-nav" onclick="changeMonth(<?php echo $nextMonth; ?>, <?php echo $nextYear; ?>)">
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

<style>
.calendar-component {
    background: #fffdf8;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.calendar-title {
    margin: 0;
    font-size: 1.2rem;
    color: #4a1414;
    font-family: 'League Spartan', sans-serif;
}

.calendar-nav {
    padding: 0.5rem 1rem;
    border: 2px solid rgba(138, 41, 39, 0.2);
    background: transparent;
    border-radius: 10px;
    cursor: pointer;
    color: #6c1d12;
    font-weight: 600;
    transition: all 0.2s;
}

.calendar-nav:hover {
    background: rgba(138, 41, 39, 0.1);
}

.calendar-legend {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: #666;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-dot.available { background: #4CAF50; }
.legend-dot.booked { background: #f44336; }
.legend-dot.blocked { background: #9e9e9e; }

.calendar-table {
    width: 100%;
    border-collapse: collapse;
}

.calendar-table th {
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-size: 0.85rem;
    color: #8a2927;
    font-weight: 600;
}

.calendar-table td {
    padding: 0.75rem 0.5rem;
    text-align: center;
    border-radius: 10px;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
    height: 40px;
}

.calendar-table td.empty {
    background: transparent;
    cursor: default;
}

.calendar-table td.available {
    background: rgba(76, 175, 80, 0.1);
    color: #2e7d32;
}

.calendar-table td.available:hover {
    background: #4CAF50;
    color: white;
}

.calendar-table td.booked {
    background: rgba(244, 67, 54, 0.1);
    color: #c62828;
    cursor: not-allowed;
}

.calendar-table td.blocked,
.calendar-table td.past {
    background: rgba(158, 158, 158, 0.15);
    color: #999;
    cursor: not-allowed;
}

.calendar-table td.today {
    border: 2px solid #8a2927;
}

.booking-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    font-size: 0.6rem;
    color: #f44336;
}

.calendar-selected {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(138, 41, 39, 0.1);
    border-radius: 12px;
    text-align: center;
}

.calendar-selected p {
    margin: 0;
    color: #4a1414;
}
</style>

<script>
function changeMonth(month, year) {
    // Reload the page with new month/year
    const url = new URL(window.location.href);
    url.searchParams.set('month', month);
    url.searchParams.set('year', year);
    window.location.href = url.toString();
}

function selectDate(date) {
    // Update display
    document.getElementById('selectedDateText').textContent = new Date(date).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    document.getElementById('selectedDateDisplay').style.display = 'block';
    
    // Update hidden input
    document.getElementById('eventDateInput').value = date;
    
    // Highlight selected
    document.querySelectorAll('.calendar-table td').forEach(td => {
        td.classList.remove('selected');
    });
    document.querySelector(`td[data-date="${date}"]`).classList.add('selected');
}
</script>

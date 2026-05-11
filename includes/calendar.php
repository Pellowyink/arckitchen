<?php
/**
 * Calendar Component for Inquiry Page
 * Shows available/unavailable dates for booking
 * Include this in inquiry.php only
 */

// Get current month data
$currentMonth = $_GET['month'] ?? date('m');
$currentYear = $_GET['year'] ?? date('Y');
$calendarStatusMap = getCalendarStatusMap((string)$currentMonth, (string)$currentYear);

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
        <span class="legend-item"><span class="legend-dot limited"></span> Limited</span>
        <span class="legend-item"><span class="legend-dot full"></span> Full</span>
        <span class="legend-item"><span class="legend-dot blocked"></span> Blocked</span>
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
                        $availability = $calendarStatusMap[$dateStr] ?? checkDateAvailability($dateStr);
                        $class = $availability['customer_class'];
                        $canSelect = (bool)$availability['can_select'];
                        $capacityNote = $availability['note'];
                        $status = $availability['status'];
                        
                        if ($isToday) $class .= ' today';
                ?>
                <td class="<?php echo $class; ?>" 
                    data-date="<?php echo $dateStr; ?>"
                    <?php if ($canSelect): ?>onclick="selectDate('<?php echo $dateStr; ?>')"<?php endif; ?>
                    <?php if ($capacityNote): ?>title="<?php echo escape($capacityNote); ?>" data-tooltip="<?php echo escape($capacityNote); ?>"<?php endif; ?>>
                    <?php echo $cellDay; ?>
                    <?php if (in_array($status, ['blocked', 'full', 'limited'], true)): ?>
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
.legend-dot.limited { background: #ffa500; }
.legend-dot.full { background: #ff4d4d; }
.legend-dot.booked,
.legend-dot.fully-booked { background: #f44336; }
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

.calendar-table td.state-available {
    color: #fff;
}

.calendar-table td.state-limited {
    color: #4a1414;
}

.calendar-table td.available:hover,
.calendar-table td.state-limited:hover {
    background: #4CAF50;
    color: white;
}

.calendar-table td.booked,
.calendar-table td.fully_booked {
    background: rgba(244, 67, 54, 0.1);
    color: #c62828;
    cursor: not-allowed;
}

.calendar-table td.blocked,
.calendar-table td.date-blocked,
.calendar-table td.past {
    background: rgba(158, 158, 158, 0.15);
    color: #999;
    cursor: not-allowed;
}

.calendar-table td.date-blocked {
    background-color: #d3d3d3 !important;
    color: #888;
    cursor: not-allowed;
    pointer-events: none;
}

.calendar-table td.date-blocked .booking-indicator {
    display: none;
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
// Store current calendar state
let currentCalendarMonth = <?php echo (int)$currentMonth; ?>;
let currentCalendarYear = <?php echo (int)$currentYear; ?>;

function changeMonth(month, year) {
    // Save form data before loading new month
    saveFormData();
    
    // Fetch canonical calendar state via AJAX
    const url = `api/get-calendar.php?month=${month}&year=${year}`;
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to load calendar');
            }

            const container = document.getElementById('calendarComponent');
            if (container) {
                container.innerHTML = renderCalendarMarkup(month, year, data.dates || []);

                attachCalendarListeners();

                const savedDate = document.getElementById('eventDateInput')?.value || document.getElementById('selectedDate')?.value;
                if (savedDate) {
                    const cell = document.querySelector(`td[data-date="${savedDate}"]`);
                    if (cell && (cell.classList.contains('available') || cell.classList.contains('state-limited'))) {
                        cell.classList.add('selected');
                    }
                }
            }
            
            currentCalendarMonth = month;
            currentCalendarYear = year;
            
            // Restore form data
            restoreFormData();
        })
        .catch(err => {
            console.error('Failed to load calendar:', err);
            // Fallback: reload page with saved data in sessionStorage
            sessionStorage.setItem('calendar_month', month);
            sessionStorage.setItem('calendar_year', year);
            window.location.href = window.location.pathname + `?month=${month}&year=${year}`;
        });
}

function renderCalendarMarkup(month, year, dates) {
    const dateMap = {};
    dates.forEach(item => {
        dateMap[item.date] = item;
    });

    const firstDay = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();
    const startWeekday = firstDay.getDay();
    const prev = new Date(year, month - 2, 1);
    const next = new Date(year, month, 1);
    const monthName = firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    const today = new Date().toISOString().slice(0, 10);
    const rows = Math.ceil((daysInMonth + startWeekday) / 7);
    let body = '';

    for (let row = 0; row < rows; row++) {
        body += '<tr>';
        for (let col = 0; col < 7; col++) {
            const cellDay = row * 7 + col - startWeekday + 1;
            if (cellDay < 1 || cellDay > daysInMonth) {
                body += '<td class="empty"></td>';
                continue;
            }

            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(cellDay).padStart(2, '0')}`;
            const state = dateMap[dateStr] || {
                class: 'state-available available',
                status: 'available',
                can_select: true,
                note: ''
            };
            const classes = `${state.class || 'state-available available'}${dateStr === today ? ' today' : ''}`;
            const title = state.note ? ` title="${escapeHtml(state.note)}" data-tooltip="${escapeHtml(state.note)}"` : '';
            const click = state.can_select ? ` onclick="selectDate('${dateStr}')"` : '';
            const marker = ['blocked', 'full', 'limited'].includes(state.status) ? '<span class="booking-indicator">&bull;</span>' : '';
            body += `<td class="${classes}" data-date="${dateStr}"${click}${title}>${cellDay}${marker}</td>`;
        }
        body += '</tr>';
    }

    return `
        <div class="calendar-header">
            <button type="button" class="calendar-nav" onclick="changeMonth(${prev.getMonth() + 1}, ${prev.getFullYear()})">Prev</button>
            <h3 class="calendar-title">${monthName}</h3>
            <button type="button" class="calendar-nav" onclick="changeMonth(${next.getMonth() + 1}, ${next.getFullYear()})">Next</button>
        </div>
        <div class="calendar-legend">
            <span class="legend-item"><span class="legend-dot available"></span> Available</span>
            <span class="legend-item"><span class="legend-dot limited"></span> Limited</span>
            <span class="legend-item"><span class="legend-dot full"></span> Full</span>
            <span class="legend-item"><span class="legend-dot blocked"></span> Blocked</span>
        </div>
        <table class="calendar-table">
            <thead>
                <tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr>
            </thead>
            <tbody>${body}</tbody>
        </table>
        <div class="calendar-selected" id="selectedDateDisplay" style="display: none;">
            <p>Selected Date: <strong id="selectedDateText"></strong></p>
            <input type="hidden" name="event_date" id="eventDateInput" required>
        </div>
    `;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function attachCalendarListeners() {
    // Re-attach click handlers to available dates
    document.querySelectorAll('.calendar-table td.available, .calendar-table td.state-available, .calendar-table td.state-limited, .calendar-table td.today').forEach(td => {
        const date = td.dataset.date;
        if (date && !td.classList.contains('past') && !td.classList.contains('date-blocked') && !td.classList.contains('state-full') && !td.classList.contains('state-blocked')) {
            td.onclick = () => selectDate(date);
        }
    });
}

function selectDate(date) {
    const selectedCell = document.querySelector(`.calendar-table td[data-date="${date}"]`);
    if (selectedCell && (
        selectedCell.classList.contains('state-full') ||
        selectedCell.classList.contains('state-blocked') ||
        selectedCell.classList.contains('fully_booked') ||
        selectedCell.classList.contains('booked') ||
        selectedCell.classList.contains('blocked') ||
        selectedCell.classList.contains('date-blocked') ||
        selectedCell.classList.contains('past')
    )) {
        return;
    }

    // Update display
    const display = document.getElementById('selectedDateDisplay');
    const text = document.getElementById('selectedDateText');
    const input = document.getElementById('eventDateInput');
    
    if (text) {
        text.textContent = new Date(date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    if (display) display.style.display = 'block';
    if (input) input.value = date;
    
    // Highlight selected
    document.querySelectorAll('.calendar-table td').forEach(td => {
        td.classList.remove('selected');
    });
    const selectedCell = document.querySelector(`td[data-date="${date}"]`);
    if (selectedCell) selectedCell.classList.add('selected');
    
    // Also update the sidebar display if it exists
    const sidebarDateValue = document.getElementById('selectedDateValue');
    const sidebarDateDisplay = document.getElementById('selectedDateDisplay');
    const sidebarDateInput = document.getElementById('selectedDate');
    
    if (sidebarDateValue) {
        sidebarDateValue.textContent = new Date(date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    if (sidebarDateDisplay) {
        sidebarDateDisplay.style.display = 'block';
        sidebarDateDisplay.classList.add('has-date');
    }
    if (sidebarDateInput) sidebarDateInput.value = date;
}

// Save form data to sessionStorage
function saveFormData() {
    const fields = ['full_name', 'email', 'phone', 'event_type', 'guest_count', 'package_interest', 'message'];
    fields.forEach(field => {
        const el = document.getElementById(field);
        if (el) {
            sessionStorage.setItem('form_' + field, el.value);
        }
    });
    // Also save selected date
    const dateInput = document.getElementById('eventDateInput') || document.getElementById('selectedDate');
    if (dateInput && dateInput.value) {
        sessionStorage.setItem('form_event_date', dateInput.value);
    }
}

// Restore form data from sessionStorage
function restoreFormData() {
    const fields = ['full_name', 'email', 'phone', 'event_type', 'guest_count', 'package_interest', 'message'];
    fields.forEach(field => {
        const saved = sessionStorage.getItem('form_' + field);
        if (saved) {
            const el = document.getElementById(field);
            if (el && !el.value) { // Only restore if field is empty
                el.value = saved;
            }
        }
    });
    // Restore date
    const savedDate = sessionStorage.getItem('form_event_date');
    if (savedDate) {
        const dateInput = document.getElementById('eventDateInput') || document.getElementById('selectedDate');
        if (dateInput && !dateInput.value) {
            selectDate(savedDate);
        }
    }
}

// Auto-save form data as user types
document.addEventListener('DOMContentLoaded', function() {
    const fields = ['full_name', 'email', 'phone', 'event_type', 'guest_count', 'package_interest', 'message'];
    fields.forEach(field => {
        const el = document.getElementById(field);
        if (el) {
            el.addEventListener('change', saveFormData);
            el.addEventListener('input', saveFormData);
        }
    });
    
    // Attach initial calendar listeners
    attachCalendarListeners();
    
    // Check for stored calendar month/year from fallback
    const storedMonth = sessionStorage.getItem('calendar_month');
    const storedYear = sessionStorage.getItem('calendar_year');
    if (storedMonth && storedYear) {
        sessionStorage.removeItem('calendar_month');
        sessionStorage.removeItem('calendar_year');
    }
});
</script>

<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'ARC Kitchen | Booking Inquiry';
$menuItems = getMenuItems();
$packages = getPackages();
$errors = [];

if (isPostRequest()) {
    $errors = validateRequiredFields([
        'full_name' => 'Full name',
        'email' => 'Email address',
        'phone' => 'Phone number',
        'event_date' => 'Event date',
    ]);

    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

        if (!$errors) {
            $saved = saveBooking([
                'full_name' => trim($_POST['full_name']),
                'email' => trim($_POST['email']),
                'phone' => trim($_POST['phone']),
                'event_date' => trim($_POST['event_date']),
                'event_type' => trim($_POST['event_type'] ?? 'Catering'),
                'guest_count' => (int) ($_POST['guest_count'] ?? 1),
                'package_interest' => trim($_POST['package_interest'] ?? ''),
                'message' => trim($_POST['message'] ?? ''),
            ]);

            if ($saved) {
                setFlashMessage('success', 'Your booking inquiry has been submitted. ARC Kitchen will contact you shortly to confirm.');
                redirect('inquiry.php');
            }

            $errors[] = 'Database connection is unavailable. Please try again later.';
        }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Booking Calendar Section -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-card reveal">
            <span class="eyebrow">Reserve Your Date</span>
            <h1>Select your event date from our booking calendar</h1>
            <p>Click on an available date to select it. Red dates are confirmed bookings, gray dates are past or blocked.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <!-- Calendar Card -->
        <div class="section-card reveal">
            <h2 style="text-align: center; margin-bottom: 1.5rem;">Calendar | Event Calendar</h2>
            
            <?php
            // Get all booking dates
            $allBookings = getBookings();
            $bookedDates = [];
            foreach ($allBookings as $b) {
                if ($b['status'] === 'confirmed') {
                    $bookedDates[] = date('Y-m-d', strtotime($b['event_date']));
                }
            }
            $bookedDates = array_unique($bookedDates);
            
            // Generate calendar (current month)
            $currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
            $currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            
            $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
            $daysInMonth = date('t', $firstDay);
            $monthName = date('F', $firstDay);
            $firstDayOfWeek = date('w', $firstDay);
            $today = date('Y-m-d');
            
            $prevMonth = $currentMonth - 1;
            $prevYear = $currentYear;
            if ($prevMonth < 1) {
                $prevMonth = 12;
                $prevYear--;
            }
            
            $nextMonth = $currentMonth + 1;
            $nextYear = $currentYear;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear++;
            }
            ?>
            
            <div class="calendar-navigation" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn-secondary-admin" style="padding: 0.5rem 1rem;">&larr; <?php echo date('M', mktime(0, 0, 0, $prevMonth, 1, $prevYear)); ?></a>
                <h3 style="margin: 0; color: var(--surface-dark); font-family: 'League Spartan', sans-serif;"><?php echo $monthName; ?> <?php echo $currentYear; ?></h3>
                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn-secondary-admin" style="padding: 0.5rem 1rem;"><?php echo date('M', mktime(0, 0, 0, $nextMonth, 1, $nextYear)); ?> &rarr;</a>
            </div>
            
            <div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem;">
                <?php
                $weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($weekDays as $day) {
                    echo '<div style="text-align: center; font-weight: 700; color: var(--surface-dark); padding: 0.75rem 0; font-size: 0.9rem;">' . $day . '</div>';
                }
                
                // Empty cells for days before the first day
                for ($i = 0; $i < $firstDayOfWeek; $i++) {
                    echo '<div style="background: rgba(108, 29, 18, 0.03); aspect-ratio: 1; border-radius: 12px;"></div>';
                }
                
                // Days of the month
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                    $isPast = $dateStr < $today;
                    $isBooked = in_array($dateStr, $bookedDates);
                    $isToday = $dateStr === $today;
                    
                    $bgColor = '#fff';
                    $textColor = 'var(--text)';
                    $borderColor = 'rgba(108, 29, 18, 0.15)';
                    $cursor = 'pointer';
                    
                    if ($isPast || $isBooked) {
                        $bgColor = $isPast ? '#f5f5f5' : '#8a2927';
                        $textColor = $isPast ? '#ccc' : '#fff';
                        $borderColor = $isPast ? '#eee' : '#8a2927';
                        $cursor = 'not-allowed';
                    } elseif ($isToday) {
                        $bgColor = 'rgba(213, 164, 55, 0.2)';
                        $borderColor = '#d5a437';
                        $textColor = 'var(--surface-dark)';
                        $cursor = 'pointer';
                    } else {
                        $bgColor = '#fff';
                        $textColor = 'var(--text)';
                        $borderColor = 'rgba(108, 29, 18, 0.15)';
                        $cursor = 'pointer';
                    }
                    
                    $disabled = $isPast ? 'disabled' : '';
                    $onClick = $isPast ? '' : "selectDate('$dateStr')";
                    
                    echo "<button type='button' class='calendar-day' onclick='$onClick' $disabled style='background: $bgColor; color: $textColor; border: 2px solid $borderColor; border-radius: 14px; aspect-ratio: 1; cursor: $cursor; font-weight: 600; font-size: 0.95rem; transition: all 0.2s;' onmouseover='this.style.transform=\"scale(1.05)\"' onmouseout='this.style.transform=\"scale(1)\"'>$day</button>";
                }
                ?>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem; flex-wrap: wrap; justify-content: center;">
                <span style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #8a2927; border-radius: 4px;"></span>
                    Confirmed Booking
                </span>
                <span style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #fff; border: 2px solid rgba(108, 29, 18, 0.15); border-radius: 4px;"></span>
                    Available
                </span>
                <span style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;">
                    <span style="display: inline-block; width: 16px; height: 16px; background: #f5f5f5; border-radius: 4px;"></span>
                    Past Date
                </span>
            </div>
        </div>
        
        <div id="selectedDateDisplay" style="display: none; text-align: center; margin: 1rem 0; padding: 1rem; background: rgba(213, 164, 55, 0.1); border-radius: 16px; border: 2px solid #d5a437;">
            <p style="margin: 0; color: var(--surface-dark); font-weight: 600;">
                Selected Date: <span id="selectedDateValue"></span>
            </p>
        </div>
        
        <!-- Inquiry Form -->
        <div class="admin-card" style="margin-top: 1.5rem;">
            <h2>Inquiry Form - Complete Your Booking Inquiry</h2>
            
            <?php if ($errors): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" data-validate>
                <input type="hidden" id="selectedDate" name="event_date" value="">
                
                <div class="form-grid">
                    <div class="field">
                        <label for="full_name">Full Name *</label>
                        <input id="full_name" name="full_name" type="text" required value="<?php echo escape($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email Address *</label>
                        <input id="email" name="email" type="email" required value="<?php echo escape($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="phone">Phone Number *</label>
                        <input id="phone" name="phone" type="text" required value="<?php echo escape($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="event_type">Event Type</label>
                        <select id="event_type" name="event_type">
                            <option value="">Select event type</option>
                            <?php
                            $eventTypes = ['Birthday', 'Wedding', 'Corporate', 'Baptism', 'Family Gathering', 'Anniversary', 'Other'];
                            foreach ($eventTypes as $eventType):
                                $selected = ($_POST['event_type'] ?? '') === $eventType ? 'selected' : '';
                            ?>
                                <option value="<?php echo escape($eventType); ?>" <?php echo $selected; ?>><?php echo escape($eventType); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="guest_count">Guest Count</label>
                        <input id="guest_count" name="guest_count" type="number" min="1" value="<?php echo escape($_POST['guest_count'] ?? '50'); ?>">
                    </div>
                    <div class="field">
                        <label for="package_interest">Preferred Package</label>
                        <select id="package_interest" name="package_interest">
                            <option value="">No preference yet</option>
                            <?php foreach ($packages as $package): ?>
                                <?php $selected = ($_POST['package_interest'] ?? '') === $package['name'] ? 'selected' : ''; ?>
                                <option value="<?php echo escape($package['name']); ?>" <?php echo $selected; ?>>
                                    <?php echo escape($package['name']); ?> (PHP <?php echo number_format((float)$package['price'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="field-full">
                    <label for="message">Special Requirements & Menu Preferences</label>
                    <textarea id="message" name="message" rows="4" placeholder="Tell us about your dietary requirements, preferred menu items, venue details, or any special requests..."><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <button type="submit" class="button" style="padding: 1rem 2.5rem; font-size: 1.1rem;">Submit Booking Inquiry</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
function selectDate(dateStr) {
    document.getElementById('selectedDate').value = dateStr;
    document.getElementById('selectedDateValue').textContent = dateStr;
    document.getElementById('selectedDateDisplay').style.display = 'block';
    
    // Scroll to form
    document.querySelector('.admin-card').scrollIntoView({ behavior: 'smooth' });
}

// Initialize selected date if present
window.addEventListener('DOMContentLoaded', function() {
    const selectedDateInput = document.getElementById('selectedDate');
    if (selectedDateInput.value) {
        document.getElementById('selectedDateValue').textContent = selectedDateInput.value;
        document.getElementById('selectedDateDisplay').style.display = 'block';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

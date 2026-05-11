<?php
/**
 * API: Get Calendar Data
 * Returns unavailable and booked dates for a month
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Validate
$month = str_pad($month, 2, '0', STR_PAD_LEFT);
$year = (int)$year;

if ($year < 2020 || $year > 2030 || $month < 1 || $month > 12) {
    echo json_encode(['success' => false, 'message' => 'Invalid month/year']);
    exit;
}

$dates = [];
$calendarStatusMap = getCalendarStatusMap($month, (string)$year);
$startDate = sprintf('%04d-%02d-01', $year, (int)$month);
$daysInMonth = (int)date('t', strtotime($startDate));

for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, (int)$month, $day);
    $availability = $calendarStatusMap[$date] ?? checkDateAvailability($date);

    $dates[$date] = [
        'date' => $date,
        'status' => $availability['status'],
        'manual_status' => $availability['manual_status'],
        'admin_override_exists' => $availability['admin_override_exists'],
        'admin_override' => $availability['admin_override_exists'] ? 1 : 0,
        'is_auto_full' => $availability['is_auto_full'],
        'color_state' => $availability['color_state'],
        'is_blocked' => $availability['is_blocked'],
        'max_capacity' => $availability['max_capacity'],
        'current_bookings' => $availability['current_bookings'],
        'booking_ids' => $availability['booking_ids'],
        'booking_names' => $availability['booking_names'],
        'note' => $availability['note'],
        'class' => $availability['customer_class'],
        'can_select' => $availability['can_select'],
    ];
}

echo json_encode([
    'success' => true,
    'month' => $month,
    'year' => $year,
    'date_map' => $dates,
    'dates' => array_values($dates)
]);

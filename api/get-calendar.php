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
$calendarSettings = getCalendarSettings($month, (string)$year);
$startDate = sprintf('%04d-%02d-01', $year, (int)$month);
$daysInMonth = (int)date('t', strtotime($startDate));

for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = sprintf('%04d-%02d-%02d', $year, (int)$month, $day);
    $availability = checkDateAvailability($date, $calendarSettings[$date] ?? [
        'slot_date' => $date,
        'max_slots' => 3,
        'current_slots' => 0,
        'admin_note' => '',
        'status' => 'open',
    ]);

    if ($availability['status'] !== 'available') {
        $dates[$date] = [
            'date' => $date,
            'status' => $availability['status'],
            'count' => $availability['current_slots'],
            'max_slots' => $availability['max_slots'],
            'note' => $availability['note'],
            'class' => $availability['customer_class'],
            'can_select' => $availability['can_select'],
        ];
    }
}

echo json_encode([
    'success' => true,
    'month' => $month,
    'year' => $year,
    'dates' => array_values($dates)
]);

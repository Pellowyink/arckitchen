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

$unavailable = getUnavailableDates($month, $year);
$booked = getBookedDates($month, $year);

// Merge data
$dates = [];

// Add unavailable dates
foreach ($unavailable as $u) {
    $dates[$u['date']] = [
        'date' => $u['date'],
        'status' => $u['status'], // 'blocked' or 'fully_booked'
        'reason' => $u['reason'],
        'class' => $u['status'] === 'fully_booked' ? 'fully-booked' : 'blocked'
    ];
}

// Add booked dates
foreach ($booked as $b) {
    $date = $b['date'];
    if (!isset($dates[$date])) {
        $dates[$date] = [
            'date' => $date,
            'status' => 'booked',
            'count' => $b['booking_count'],
            'class' => 'booked'
        ];
    }
}

echo json_encode([
    'success' => true,
    'month' => $month,
    'year' => $year,
    'dates' => array_values($dates)
]);

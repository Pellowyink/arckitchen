<?php
ob_start();

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

function respondJson(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    if (ob_get_length() !== false) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respondJson(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $date = $_GET['date'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        respondJson(['success' => false, 'message' => 'Invalid date format'], 400);
    }

    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    $calendarStatusMap = getCalendarStatusMap($month, $year);
    $setting = getCalendarSettingByDate($date);
    $availability = $calendarStatusMap[$date] ?? checkDateAvailability($date, $setting);
    $setting = array_merge($setting, [
        'availability_class' => $availability['availability_class'],
        'customer_class' => $availability['customer_class'],
        'color_state' => $availability['color_state'],
        'admin_override_exists' => $availability['admin_override_exists'],
        'is_auto_full' => $availability['is_auto_full'],
        'current_bookings' => $availability['current_bookings'],
        'booking_ids' => $availability['booking_ids'],
        'booking_names' => $availability['booking_names'],
        'can_select' => $availability['can_select'],
    ]);

    respondJson([
        'success' => true,
        'setting' => $setting,
        'default_capacity' => getDefaultCalendarCapacity(),
    ]);
} catch (Throwable $e) {
    error_log("get-date-settings error: " . $e->getMessage());
    respondJson(['success' => false, 'message' => 'Failed to load date settings'], 500);
}

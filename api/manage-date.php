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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respondJson(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respondJson(['success' => false, 'message' => 'Invalid JSON request'], 400);
    }

    $date = $data['slot_date'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        respondJson(['success' => false, 'message' => 'Invalid date format'], 400);
    }

    $adminNote = trim((string)($data['admin_note'] ?? ''));
    $isBlocked = !empty($data['is_blocked']);
    $action = strtolower(trim((string)($data['action'] ?? 'save')));
    $status = strtolower(trim((string)($data['status'] ?? 'available')));
    $maxCapacity = isset($data['max_capacity']) ? (int)$data['max_capacity'] : getDefaultCalendarCapacity();
    $adminOverride = true;

    if ($action === 'reset' || $action === 'unblock') {
        $adminNote = '';
        $isBlocked = false;
        $status = 'available';
        $maxCapacity = getDefaultCalendarCapacity();
        $adminOverride = false;
    } elseif ($action === 'override_limited') {
        $isBlocked = false;
        $status = 'limited';
        $adminOverride = true;
    } elseif ($action === 'override_open') {
        $isBlocked = false;
        $status = 'open';
        $adminOverride = true;
    }

    if (!saveCalendarSetting($date, $isBlocked, $adminNote, $maxCapacity, $status, $adminOverride)) {
        respondJson(['success' => false, 'message' => 'Failed to save date settings'], 500);
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
        'message' => 'Date settings saved',
        'setting' => $setting,
    ]);
} catch (Throwable $e) {
    error_log("manage-date error: " . $e->getMessage());
    respondJson(['success' => false, 'message' => 'Failed to save date settings'], 500);
}

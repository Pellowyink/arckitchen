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

    $maxSlots = (int)($data['max_slots'] ?? 3);
    $adminNote = trim((string)($data['admin_note'] ?? ''));
    $status = (string)($data['status'] ?? 'open');

    if (!saveCalendarSetting($date, $maxSlots, $adminNote, $status)) {
        respondJson(['success' => false, 'message' => 'Failed to save date settings'], 500);
    }

    $setting = getCalendarSettingByDate($date);
    $setting['availability_class'] = getCalendarAvailabilityClass($setting);

    respondJson([
        'success' => true,
        'message' => 'Date settings saved',
        'setting' => $setting,
    ]);
} catch (Throwable $e) {
    error_log("manage-date error: " . $e->getMessage());
    respondJson(['success' => false, 'message' => 'Failed to save date settings'], 500);
}

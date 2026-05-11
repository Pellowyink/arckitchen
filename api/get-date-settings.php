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

    $setting = getCalendarSettingByDate($date);
    $setting['availability_class'] = getCalendarAvailabilityClass($setting);

    respondJson([
        'success' => true,
        'setting' => $setting,
    ]);
} catch (Throwable $e) {
    error_log("get-date-settings error: " . $e->getMessage());
    respondJson(['success' => false, 'message' => 'Failed to load date settings'], 500);
}

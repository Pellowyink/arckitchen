<?php
/**
 * AJAX Endpoint to Get Inquiry Details
 * Returns: JSON with inquiry data + items + total
 * Security: Requires admin session
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

$inquiry_id = (int)($_GET['id'] ?? 0);

if ($inquiry_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
    exit;
}

// Get inquiry from database
$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt = $connection->prepare("SELECT * FROM inquiries WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $inquiry_id);
$stmt->execute();
$result = $stmt->get_result();
$inquiry = $result->fetch_assoc();
$stmt->close();

if (!$inquiry) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
    exit;
}

// Get inquiry items
$items = getInquiryItems($inquiry_id);

// Add preferred package as an item if exists and not already in items
$preferredPackage = $inquiry['package_interest'] ?? '';
if ($preferredPackage && !empty($preferredPackage)) {
    // Check if package is already in items (bidirectional check)
    $packageExists = false;
    $preferredLower = strtolower(trim($preferredPackage));
    foreach ($items as $item) {
        if ($item['is_package'] == 1) {
            $itemNameLower = strtolower(trim($item['name']));
            // Check if either name contains the other
            if (stripos($itemNameLower, $preferredLower) !== false || 
                stripos($preferredLower, $itemNameLower) !== false) {
                $packageExists = true;
                break;
            }
        }
    }
    
    // If not exists, fetch package details and add as item
    if (!$packageExists) {
        $pkgResult = $connection->query("SELECT id, name, total_price, serves FROM packages WHERE name = '" . $connection->real_escape_string($preferredPackage) . "' LIMIT 1");
        if ($pkgResult && $pkgRow = $pkgResult->fetch_assoc()) {
            $items[] = [
                'id' => 'pkg_' . $pkgRow['id'],
                'menu_item_id' => null,
                'package_id' => $pkgRow['id'],
                'inquiry_id' => $inquiry_id,
                'quantity' => 1,
                'unit_price' => $pkgRow['total_price'],
                'name' => $pkgRow['name'],
                'category' => 'Package',
                'type' => 'package',
                'is_package' => 1,
                'package_serves' => $pkgRow['serves']
            ];
        } else {
            // Package not found in DB, add as custom item
            $items[] = [
                'id' => 'preferred_pkg',
                'menu_item_id' => null,
                'package_id' => null,
                'inquiry_id' => $inquiry_id,
                'quantity' => 1,
                'unit_price' => 0,
                'name' => $preferredPackage,
                'category' => 'Preferred Package',
                'type' => 'package',
                'is_package' => 1,
                'package_serves' => ''
            ];
        }
    }
}

// Calculate total
$total = calculateOrderTotal($items);

// Add payment fields if not present
if (!isset($inquiry['down_payment'])) $inquiry['down_payment'] = 0;
if (!isset($inquiry['full_payment'])) $inquiry['full_payment'] = 0;

// Calculate payment status
$totalPaid = (float)$inquiry['down_payment'] + (float)$inquiry['full_payment'];
$balance = $total - $totalPaid;

echo json_encode([
    'success' => true,
    'record' => $inquiry,
    'items' => $items,
    'total' => $total,
    'total_paid' => $totalPaid,
    'balance' => $balance,
    'payment_status' => $balance <= 0 ? 'fully_paid' : ($totalPaid > 0 ? 'partial' : 'pending')
]);

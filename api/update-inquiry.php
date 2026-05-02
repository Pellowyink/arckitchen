<?php
/**
 * AJAX Endpoint to Update Inquiry
 * Handles: details updates including items, total, event info
 * Security: Requires admin session
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$inquiry_id = (int)($data['id'] ?? 0);

if ($inquiry_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry ID']);
    exit;
}

$connection = getDbConnection();
if (!$connection) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Start transaction
$connection->begin_transaction();

try {
    // Update inquiry basic info
    $stmt = $connection->prepare(
        "UPDATE inquiries SET event_date = ?, event_type = ?, guest_count = ?, total_amount = ?, message = ?, updated_at = NOW() WHERE id = ?"
    );
    
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $connection->error);
    }
    
    $event_date = $data['event_date'] ?? '';
    $event_type = $data['event_type'] ?? '';
    $guest_count = (int)($data['guest_count'] ?? 0);
    $total_amount = (float)($data['total_amount'] ?? 0);
    $special_requests = $data['special_requests'] ?? '';
    
    $stmt->bind_param('ssidsi', $event_date, $event_type, $guest_count, $total_amount, $special_requests, $inquiry_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update inquiry: ' . $stmt->error);
    }
    $stmt->close();
    
    // Handle items - delete old items and insert new ones
    $items = $data['items'] ?? [];
    if (!empty($items)) {
        // Deduplicate items by name to prevent duplicates
        $uniqueItems = [];
        $seenNames = [];
        foreach ($items as $item) {
            $name = strtolower(trim($item['name'] ?? ''));
            if (!empty($name) && !in_array($name, $seenNames)) {
                $seenNames[] = $name;
                $uniqueItems[] = $item;
            }
        }
        $items = $uniqueItems;
        
        // Delete existing inquiry items
        $deleteStmt = $connection->prepare("DELETE FROM inquiry_items WHERE inquiry_id = ?");
        $deleteStmt->bind_param('i', $inquiry_id);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        // Insert new items
        $insertStmt = $connection->prepare(
            "INSERT INTO inquiry_items (inquiry_id, menu_item_id, package_id, quantity, unit_price, is_package, notes) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($items as $item) {
            $menuItemId = $item['menu_item_id'] ?? null;
            $packageId = $item['package_id'] ?? null;
            $quantity = (int)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? $item['price'] ?? 0);
            $isPackage = (int)($item['is_package'] ?? 0);
            $notes = $item['notes'] ?? '';
            $itemName = $item['name'] ?? '';
            
            // Skip items without a name
            if (empty($itemName)) {
                continue;
            }
            
            // If is_package but no package_id, try to get it from name
            if ($isPackage && !$packageId) {
                $pkgStmt = $connection->prepare("SELECT id, total_price FROM packages WHERE name = ? LIMIT 1");
                $pkgStmt->bind_param('s', $itemName);
                $pkgStmt->execute();
                $pkgResult = $pkgStmt->get_result();
                if ($pkgRow = $pkgResult->fetch_assoc()) {
                    $packageId = $pkgRow['id'];
                    // Always use the correct price from package database
                    $unitPrice = (float)$pkgRow['total_price'];
                }
                $pkgStmt->close();
            }
            
            // If NOT package but no menu_item_id, try to get it from name
            if (!$isPackage && !$menuItemId) {
                $menuStmt = $connection->prepare("SELECT id, price FROM menu_items WHERE name = ? LIMIT 1");
                $menuStmt->bind_param('s', $itemName);
                $menuStmt->execute();
                $menuResult = $menuStmt->get_result();
                if ($menuRow = $menuResult->fetch_assoc()) {
                    $menuItemId = $menuRow['id'];
                    // Always use the correct price from menu database
                    $unitPrice = (float)$menuRow['price'];
                }
                $menuStmt->close();
            }
            
            // If price is still 0, try to look up by name as fallback
            if ($unitPrice == 0 && !empty($itemName)) {
                if ($isPackage) {
                    $fallbackStmt = $connection->prepare("SELECT total_price FROM packages WHERE name = ? LIMIT 1");
                } else {
                    $fallbackStmt = $connection->prepare("SELECT price FROM menu_items WHERE name = ? LIMIT 1");
                }
                $fallbackStmt->bind_param('s', $itemName);
                $fallbackStmt->execute();
                $fallbackResult = $fallbackStmt->get_result();
                if ($fallbackRow = $fallbackResult->fetch_assoc()) {
                    $unitPrice = (float)($fallbackRow['total_price'] ?? $fallbackRow['price'] ?? 0);
                }
                $fallbackStmt->close();
            }
            
            $insertStmt->bind_param('iiidiss', $inquiry_id, $menuItemId, $packageId, $quantity, $unitPrice, $isPackage, $notes);
            $insertStmt->execute();
        }
        $insertStmt->close();
    }
    
    // Commit transaction
    $connection->commit();
    
    echo json_encode(['success' => true, 'message' => 'Inquiry updated successfully']);
    
} catch (Exception $e) {
    // Rollback on error
    $connection->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

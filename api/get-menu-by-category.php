<?php
/**
 * API: Get Menu Items by Category
 * Returns menu items filtered by category as JSON
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

$category = $_GET['category'] ?? 'ALL';
$connection = getDbConnection();

if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get all unique categories with counts
$catSql = "SELECT category, COUNT(*) as count FROM menu_items WHERE is_active = 1 GROUP BY category ORDER BY category";
$catResult = $connection->query($catSql);
$categories = [];
$totalCount = 0;

while ($row = $catResult->fetch_assoc()) {
    $categories[] = [
        'name' => $row['category'],
        'count' => (int)$row['count']
    ];
    $totalCount += (int)$row['count'];
}

// Get items based on category filter
if ($category === 'ALL') {
    $sql = "SELECT * FROM menu_items WHERE is_active = 1 ORDER BY category, name";
    $stmt = $connection->prepare($sql);
} else {
    $sql = "SELECT * FROM menu_items WHERE category = ? AND is_active = 1 ORDER BY name";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param('s', $category);
}

$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'items' => $items,
    'categories' => $categories,
    'total_count' => $totalCount,
    'current_category' => $category,
    'item_count' => count($items)
]);

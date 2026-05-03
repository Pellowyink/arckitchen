<?php
require_once 'includes/functions.php';

$connection = getDbConnection();
if (!$connection) {
    die("Could not connect to database");
}

$result = $connection->query("SELECT id, name, category FROM menu_items WHERE is_active = 1 ORDER BY category, name");

echo "<h1>Menu Items - Image Naming Reference</h1>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Item Name</th><th>Category</th><th>Filename by ID</th><th>Filename by Slug</th></tr>";

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['name'];
    $category = $row['category'];
    
    // Create slug from name
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    echo "<tr>";
    echo "<td>$id</td>";
    echo "<td>$name</td>";
    echo "<td>$category</td>";
    echo "<td><code>$id.jpg</code></td>";
    echo "<td><code>$slug.jpg</code></td>";
    echo "</tr>";
}

echo "</table>";

// Also show plain text list
echo "<h2>Quick Copy List (ID-based)</h2>";
echo "<pre>";
$result2 = $connection->query("SELECT id, name FROM menu_items WHERE is_active = 1 ORDER BY category, name");
while ($row = $result2->fetch_assoc()) {
    echo $row['id'] . ".jpg = " . $row['name'] . "\n";
}
echo "</pre>";

echo "<h2>Quick Copy List (Slug-based)</h2>";
echo "<pre>";
$result3 = $connection->query("SELECT name FROM menu_items WHERE is_active = 1 ORDER BY category, name");
while ($row = $result3->fetch_assoc()) {
    $slug = strtolower($row['name']);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    echo $slug . ".jpg = " . $row['name'] . "\n";
}
echo "</pre>";

$connection->close();
?>

<?php
require_once 'includes/functions.php';

$connection = getDbConnection();
if (!$connection) {
    die("Database connection failed");
}

// Get all menu items
$result = $connection->query("SELECT id, name, category FROM menu_items WHERE is_active = 1 ORDER BY category, name");

$menuDir = __DIR__ . '/assets/images/menu/';

echo "<h1>Menu Image Checker</h1>";
echo "<p><strong>Image folder:</strong> " . htmlspecialchars($menuDir) . "</p>";
echo "<p><strong>Folder exists:</strong> " . (is_dir($menuDir) ? '✅ YES' : '❌ NO - Create this folder!') . "</p>";

if (is_dir($menuDir)) {
    $files = glob($menuDir . '*');
    echo "<p><strong>Files in folder:</strong> " . count($files) . "</p>";
    if (count($files) > 0) {
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>" . basename($file) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>⚠️ No files found in the images folder!</p>";
    }
}

echo "<hr><h2>Expected Image Names</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f5ebe3;'><th>Item ID</th><th>Item Name</th><th>Category</th><th>Expected Filename (ID)</th><th>Expected Filename (Slug)</th><th>ID File Exists?</th><th>Slug File Exists?</th></tr>";

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['name'];
    $category = $row['category'];
    
    // Create slug from name
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    $idFilename = $id . '.jpg';
    $slugFilename = $slug . '.jpg';
    
    $idExists = file_exists($menuDir . $idFilename);
    $slugExists = file_exists($menuDir . $slugFilename);
    
    $idStatus = $idExists ? '✅ YES' : '❌ NO';
    $slugStatus = $slugExists ? '✅ YES' : '❌ NO';
    
    $rowStyle = ($idExists || $slugExists) ? 'background: #d4edda;' : 'background: #f8d7da;';
    
    echo "<tr style='$rowStyle'>";
    echo "<td>$id</td>";
    echo "<td><strong>$name</strong></td>";
    echo "<td>$category</td>";
    echo "<td><code>$idFilename</code></td>";
    echo "<td><code>$slugFilename</code></td>";
    echo "<td>$idStatus</td>";
    echo "<td>$slugStatus</td>";
    echo "</tr>";
}

echo "</table>";

// Check actual image path being used in code
echo "<hr><h2>Debug Info</h2>";
echo "<p>The code looks for images at: <code>assets/images/menu/{id or slug}.jpg</code></p>";
echo "<p>Full server path: <code>" . __DIR__ . "/assets/images/menu/</code></p>";

$connection->close();
?>

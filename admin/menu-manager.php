<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if (isPostRequest()) {
    $type = $_POST['type'] ?? '';

    if ($type === 'menu_item') {
        saveMenuItem([
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description']),
            'price' => (float) $_POST['price'],
            'image' => trim($_POST['image']) ?: 'assets/images/food-placeholder.svg',
            'category' => trim($_POST['category']),
        ]);
    }

    if ($type === 'package') {
        savePackage([
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description']),
            'price' => (float) $_POST['price'],
            'serves' => trim($_POST['serves']),
        ]);
    }

    redirect('menu-manager.php');
}

$page = basename($_SERVER['PHP_SELF']);
$menuItems = getMenuItems();
$packages = getPackages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARC Kitchen Menu Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="brand brand-light">
                <span class="brand-mark" aria-hidden="true">
                    <img src="../assets/images/arc-logo.png" alt="ARC Kitchen logo" class="brand-logo-image">
                </span>
                <span>ARC Kitchen</span>
            </div>
            <div class="sidebar-note">Content controls</div>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="bookings.php">Bookings</a>
                <a href="menu-manager.php" class="<?php echo $page === 'menu-manager.php' ? 'active' : ''; ?>">Menu Manager</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <h1 class="admin-title">Menu Manager</h1>
            <div class="admin-grid">
                <div class="table-card">
                    <h2>Add Menu Item</h2>
                    <form method="post" data-validate>
                        <input type="hidden" name="type" value="menu_item">
                        <div class="field">
                            <label for="menu_name">Name</label>
                            <input id="menu_name" name="name" type="text" required>
                        </div>
                        <div class="field spacer-top-md">
                            <label for="menu_description">Description</label>
                            <textarea id="menu_description" name="description" required></textarea>
                        </div>
                        <div class="field spacer-top-md">
                            <label for="menu_price">Price</label>
                            <input id="menu_price" name="price" type="number" step="0.01" required>
                        </div>
                        <div class="field spacer-top-md">
                            <label for="menu_category">Category</label>
                            <input id="menu_category" name="category" type="text" required>
                        </div>
                        <div class="field spacer-top-md">
                            <label for="menu_image">Image Path</label>
                            <input id="menu_image" name="image" type="text" placeholder="assets/images/food-placeholder.svg">
                        </div>
                        <div class="stack-inline">
                            <button type="submit" class="button">Save Menu Item</button>
                        </div>
                    </form>
                </div>

                <div class="table-card">
                    <h2>Add Package</h2>
                    <form method="post" data-validate>
                        <input type="hidden" name="type" value="package">
                        <div class="field">
                            <label for="package_name">Package Name</label>
                            <input id="package_name" name="name" type="text" required>
                        </div>
                        <div class="field spacer-top-md">
                            <label for="package_description">Description</label>
                            <textarea id="package_description" name="description" required></textarea>
                        </div>
                        <div class="field spacer-top-md">
                            <label for="package_price">Price</label>
                            <input id="package_price" name="price" type="number" step="0.01" required>
                        </div>
                        <div class="field spacer-top-md">
                            <label for="serves">Serves</label>
                            <input id="serves" name="serves" type="text" placeholder="20 - 30 pax" required>
                        </div>
                        <div class="stack-inline">
                            <button type="submit" class="button">Save Package</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="admin-grid spacer-top-lg">
                <div class="table-card">
                    <h2>Current Menu Items</h2>
                    <?php if ($menuItems): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($menuItems as $item): ?>
                                    <tr>
                                        <td><?php echo escape($item['name']); ?></td>
                                        <td><?php echo escape($item['category']); ?></td>
                                        <td>PHP <?php echo number_format((float) $item['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">No menu items found.</div>
                    <?php endif; ?>
                </div>

                <div class="table-card">
                    <h2>Current Packages</h2>
                    <?php if ($packages): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Serves</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($packages as $package): ?>
                                    <tr>
                                        <td><?php echo escape($package['name']); ?></td>
                                        <td><?php echo escape($package['serves']); ?></td>
                                        <td>PHP <?php echo number_format((float) $package['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">No packages found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>


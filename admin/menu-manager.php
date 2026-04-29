<?php

require_once __DIR__ . '/../includes/functions.php';

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

$menuItems = getMenuItems();
$packages = getPackages();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Manager - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">🍽️ Menu Manager</h1>
            </div>

            <!-- Add Menu Item & Package Forms -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <!-- Add Menu Item Form -->
                <div class="admin-card">
                    <h2>➕ Add Menu Item</h2>
                    <form method="post" data-validate>
                        <input type="hidden" name="type" value="menu_item">
                        <div class="form-group">
                            <label for="menu_name">Item Name</label>
                            <input id="menu_name" name="name" type="text" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="menu_description">Description</label>
                            <textarea id="menu_description" name="description" class="form-input" style="min-height: 80px;" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="menu_price">Price</label>
                            <input id="menu_price" name="price" type="number" step="0.01" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="menu_category">Category</label>
                            <input id="menu_category" name="category" type="text" class="form-input" placeholder="e.g., Beef, Pork, Chicken" required>
                        </div>
                        <div class="form-group">
                            <label for="menu_image">Image Path</label>
                            <input id="menu_image" name="image" type="text" class="form-input" placeholder="assets/images/food-placeholder.svg">
                        </div>
                        <button type="submit" class="btn-admin btn-primary-admin" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem 1rem;">Save Menu Item</button>
                    </form>
                </div>

                <!-- Add Package Form -->
                <div class="admin-card">
                    <h2>➕ Add Package</h2>
                    <form method="post" data-validate>
                        <input type="hidden" name="type" value="package">
                        <div class="form-group">
                            <label for="package_name">Package Name</label>
                            <input id="package_name" name="name" type="text" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="package_description">Description</label>
                            <textarea id="package_description" name="description" class="form-input" style="min-height: 80px;" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="package_price">Price</label>
                            <input id="package_price" name="price" type="number" step="0.01" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="serves">Serves</label>
                            <input id="serves" name="serves" type="text" class="form-input" placeholder="e.g., 20 - 30 pax" required>
                        </div>
                        <button type="submit" class="btn-admin btn-primary-admin" style="width: 100%; margin-top: 0.5rem; padding: 0.75rem 1rem;">Save Package</button>
                    </form>
                </div>
            </div>

            <!-- Current Menu Items -->
            <div class="admin-card">
                <h2>📋 Current Menu Items (<?php echo count($menuItems); ?>)</h2>
                <?php if ($menuItems): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td><strong><?php echo escape($item['name']); ?></strong></td>
                                    <td><?php echo escape($item['category']); ?></td>
                                    <td><?php echo escape(substr($item['description'], 0, 30)) . '...'; ?></td>
                                    <td><strong>₱<?php echo number_format((float) $item['price'], 2); ?></strong></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn-admin btn-secondary-admin btn-small">Edit</button>
                                            <button class="btn-admin btn-secondary-admin btn-small">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No menu items found. Create one above!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Current Packages -->
            <div class="admin-card" style="margin-top: 1.5rem;">
                <h2>📦 Current Packages (<?php echo count($packages); ?>)</h2>
                <?php if ($packages): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Serves</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                                <tr>
                                    <td><strong><?php echo escape($package['name']); ?></strong></td>
                                    <td><?php echo escape($package['serves']); ?></td>
                                    <td><?php echo escape(substr($package['description'], 0, 30)) . '...'; ?></td>
                                    <td><strong>₱<?php echo number_format((float) $package['price'], 2); ?></strong></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn-admin btn-secondary-admin btn-small">Edit</button>
                                            <button class="btn-admin btn-secondary-admin btn-small">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No packages found. Create one above!</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
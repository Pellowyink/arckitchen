<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

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
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditSidebar(<?php echo (int)$item['id']; ?>, 'menu')">Edit</button>
                                            <button class="btn-admin btn-danger-admin btn-small" onclick="deleteItem(<?php echo (int)$item['id']; ?>, 'menu')">Delete</button>
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
                                    <td><strong>₱<?php echo number_format((float) ($package['total_price'] ?? 0), 2); ?></strong></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn-admin btn-secondary-admin btn-small" onclick="openEditSidebar(<?php echo (int)$package['id']; ?>, 'package')">Edit</button>
                                            <button class="btn-admin btn-danger-admin btn-small" onclick="deleteItem(<?php echo (int)$package['id']; ?>, 'package')">Delete</button>
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

    <!-- Edit Sidebar for Menu Items and Packages -->
    <div id="edit-sidebar" class="edit-sidebar">
        <div class="edit-sidebar-header">
            <h2 id="edit-sidebar-title">Edit Item</h2>
            <button class="btn-close-sidebar" onclick="closeEditSidebar()">&times;</button>
        </div>
        <div class="edit-sidebar-content" id="edit-sidebar-content">
            <!-- Form content will be loaded here -->
        </div>
    </div>

    <script>
    let currentEditId = null;
    let currentEditType = null;

    /**
     * Open edit sidebar for menu item or package
     */
    function openEditSidebar(id, type) {
        currentEditId = id;
        currentEditType = type;
        
        const sidebar = document.getElementById('edit-sidebar');
        const content = document.getElementById('edit-sidebar-content');
        const title = document.getElementById('edit-sidebar-title');
        
        title.textContent = type === 'menu' ? 'Edit Menu Item' : 'Edit Package';
        
        // Fetch item data
        fetch(`../api/get-${type}.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = generateEditForm(data.data, type);
                    sidebar.classList.add('active');
                } else {
                    alert('Failed to load item details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load item details');
            });
    }

    /**
     * Generate edit form HTML
     */
    function generateEditForm(item, type) {
        if (type === 'menu') {
            return `
                <form onsubmit="saveEdit(event)">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-input" value="${escapeHtml(item.name)}" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-input">
                            <option value="Beef" ${item.category === 'Beef' ? 'selected' : ''}>Beef</option>
                            <option value="Chicken" ${item.category === 'Chicken' ? 'selected' : ''}>Chicken</option>
                            <option value="Pork" ${item.category === 'Pork' ? 'selected' : ''}>Pork</option>
                            <option value="Seafood" ${item.category === 'Seafood' ? 'selected' : ''}>Seafood</option>
                            <option value="Pasta" ${item.category === 'Pasta' ? 'selected' : ''}>Pasta</option>
                            <option value="Appetizer" ${item.category === 'Appetizer' ? 'selected' : ''}>Appetizer</option>
                            <option value="Salad" ${item.category === 'Salad' ? 'selected' : ''}>Salad</option>
                            <option value="Dessert" ${item.category === 'Dessert' ? 'selected' : ''}>Dessert</option>
                            <option value="Drinks" ${item.category === 'Drinks' ? 'selected' : ''}>Drinks</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-input" rows="3">${escapeHtml(item.description)}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Price (₱)</label>
                        <input type="number" name="price" class="form-input" value="${item.price}" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" ${item.is_active ? 'checked' : ''}>
                            Active
                        </label>
                    </div>
                    <button type="submit" class="btn-admin btn-primary-admin">Save Changes</button>
                </form>
            `;
        } else {
            return `
                <form onsubmit="saveEdit(event)">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-input" value="${escapeHtml(item.name)}" required>
                    </div>
                    <div class="form-group">
                        <label>Serves</label>
                        <input type="text" name="serves" class="form-input" value="${escapeHtml(item.serves)}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-input" rows="3">${escapeHtml(item.description)}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Total Price (₱)</label>
                        <input type="number" name="total_price" class="form-input" value="${item.total_price}" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" ${item.is_active ? 'checked' : ''}>
                            Active
                        </label>
                    </div>
                    <button type="submit" class="btn-admin btn-primary-admin">Save Changes</button>
                </form>
            `;
        }
    }

    /**
     * Escape HTML for display
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Save edited item
     */
    function saveEdit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.id = currentEditId;
        data.is_active = form.querySelector('[name="is_active"]')?.checked ? 1 : 0;
        
        fetch(`../api/update-${currentEditType}.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('✅ Saved successfully!');
                closeEditSidebar();
                location.reload();
            } else {
                alert('❌ Error: ' + (result.message || 'Failed to save'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Failed to save changes');
        });
    }

    /**
     * Close edit sidebar
     */
    function closeEditSidebar() {
        document.getElementById('edit-sidebar').classList.remove('active');
        currentEditId = null;
        currentEditType = null;
    }

    /**
     * Delete item with confirmation
     */
    function deleteItem(id, type) {
        if (!confirm(`Are you sure you want to delete this ${type}?`)) return;
        
        fetch(`../api/delete-${type}.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('✅ Deleted successfully!');
                location.reload();
            } else {
                alert('❌ Error: ' + (result.message || 'Failed to delete'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Failed to delete');
        });
    }
    </script>

    <style>
    .edit-sidebar {
        position: fixed;
        top: 0;
        right: -500px;
        width: 450px;
        height: 100vh;
        background: #fff;
        box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        z-index: 1100;
        transition: right 0.3s ease;
        overflow-y: auto;
    }
    .edit-sidebar.active {
        right: 0;
    }
    .edit-sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        background: linear-gradient(135deg, #5e1a1a 0%, #8b2e2e 100%);
        color: white;
    }
    .edit-sidebar-header h2 {
        margin: 0;
        font-size: 1.25rem;
    }
    .btn-close-sidebar {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }
    .edit-sidebar-content {
        padding: 1.5rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
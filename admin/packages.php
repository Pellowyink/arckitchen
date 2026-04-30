<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$packages = getPackages();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages - ARC Kitchen Admin</title>
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
                <h1 class="admin-title">📦 Catering Packages</h1>
                <button class="btn-admin btn-primary-admin">+ New Package</button>
            </div>

            <!-- Packages Grid -->
            <div class="admin-card">
                <?php if (!empty($packages)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Serves</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><strong><?php echo escape($package['name']); ?></strong></td>
                                <td><?php echo escape($package['serves'] ?? 'N/A'); ?></td>
                                <td>₱<?php echo number_format($package['total_price'] ?? 0, 2); ?></td>
                                <td>
                                    <?php $isActive = !empty($package['is_active']); ?>
                                    <span class="badge badge-<?php echo ($isActive ? 'confirmed' : 'cancelled'); ?>">
                                        <?php echo ($isActive ? 'Active' : 'Inactive'); ?>
                                    </span>
                                </td>
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
                        <p>No packages found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Edit Sidebar for Packages -->
    <div id="edit-sidebar" class="edit-sidebar">
        <div class="edit-sidebar-header">
            <h2 id="edit-sidebar-title">Edit Package</h2>
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
     * Open edit sidebar for package
     */
    function openEditSidebar(id, type) {
        currentEditId = id;
        currentEditType = type;
        
        const sidebar = document.getElementById('edit-sidebar');
        const content = document.getElementById('edit-sidebar-content');
        const title = document.getElementById('edit-sidebar-title');
        
        title.textContent = 'Edit Package';
        
        // Fetch package data
        fetch(`../api/get-package.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = generateEditForm(data.data);
                    sidebar.classList.add('active');
                } else {
                    alert('Failed to load package details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load package details');
            });
    }

    /**
     * Generate edit form HTML
     */
    function generateEditForm(item) {
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
     * Save edited package
     */
    function saveEdit(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.id = currentEditId;
        data.is_active = form.querySelector('[name="is_active"]')?.checked ? 1 : 0;
        
        fetch(`../api/update-package.php`, {
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
     * Delete package with confirmation
     */
    function deleteItem(id, type) {
        if (!confirm(`Are you sure you want to delete this ${type}?`)) return;
        
        fetch(`../api/delete-package.php`, {
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
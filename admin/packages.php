<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$packages = getPackages();
$menuItems = getMenuItems();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages & Menu - ARC Kitchen Admin</title>
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
            <!-- Header -->
            <div class="admin-header">
                <h1 class="admin-title">📦 Packages & Menu</h1>
            </div>

            <!-- PACKAGES SECTION -->
            <section class="admin-section packages-section">
                <div class="section-header artisanal-header">
                    <h2 class="section-title">
                        <span class="header-icon">📦</span>
                        <span class="header-text">Catering Packages</span>
                    </h2>
                    <button class="btn-admin btn-primary-admin btn-small" onclick="openAddModal('package')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Package
                    </button>
                </div>
                
                <div class="admin-card">
                    <?php if (!empty($packages)): ?>
                        <div class="table-responsive">
                            <table class="admin-table unified-table">
                                <thead>
                                    <tr>
                                        <th width="30%">Package Name</th>
                                        <th width="15%">Serves</th>
                                        <th width="15%">Price</th>
                                        <th width="15%">Status</th>
                                        <th width="25%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($packages as $package): ?>
                                    <tr>
                                        <td><strong class="item-name"><?php echo escape($package['name']); ?></strong></td>
                                        <td><?php echo escape($package['serves'] ?? 'N/A'); ?></td>
                                        <td><strong class="price">₱<?php echo number_format($package['total_price'] ?? 0, 2); ?></strong></td>
                                        <td>
                                            <?php $isActive = !empty($package['is_active']); ?>
                                            <span class="status-badge <?php echo ($isActive ? 'active' : 'inactive'); ?>">
                                                <?php echo ($isActive ? 'Active' : 'Inactive'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon btn-icon-edit" onclick="openEditModal(<?php echo (int)$package['id']; ?>, 'package')" title="Edit">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <button class="btn-icon btn-icon-delete" onclick="deleteItem(<?php echo (int)$package['id']; ?>, 'package')" title="Delete">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d5a437" stroke-width="1.5">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                            </div>
                            <p>No packages found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- MENU ITEMS SECTION -->
            <section class="admin-section menu-section">
                <div class="section-header artisanal-header">
                    <h2 class="section-title">
                        <span class="header-icon">🍽️</span>
                        <span class="header-text">Current Menu Items</span>
                        <span class="item-count">(<?php echo count($menuItems); ?>)</span>
                    </h2>
                    <button class="btn-admin btn-primary-admin btn-small" onclick="openAddModal('menu')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Menu Item
                    </button>
                </div>
                
                <div class="admin-card">
                    <?php if (!empty($menuItems)): ?>
                        <div class="table-responsive">
                            <table class="admin-table unified-table">
                                <thead>
                                    <tr>
                                        <th width="25%">Item Name</th>
                                        <th width="12%">Category</th>
                                        <th width="33%">Description</th>
                                        <th width="15%">Price</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($menuItems as $item): ?>
                                    <tr>
                                        <td><strong class="item-name"><?php echo escape($item['name']); ?></strong></td>
                                        <td><span class="category-tag"><?php echo escape($item['category']); ?></span></td>
                                        <td class="description-cell"><?php echo escape(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?></td>
                                        <td><strong class="price">₱<?php echo number_format((float)$item['price'], 2); ?></strong></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon btn-icon-edit" onclick="openEditModal(<?php echo (int)$item['id']; ?>, 'menu')" title="Edit">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <button class="btn-icon btn-icon-delete" onclick="deleteItem(<?php echo (int)$item['id']; ?>, 'menu')" title="Delete">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d5a437" stroke-width="1.5">
                                    <path d="M3 3h18v18H3zM9 3v18M15 3v18M3 9h18M3 15h18"></path>
                                </svg>
                            </div>
                            <p>No menu items found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Edit Modal Overlay -->
    <div id="edit-modal-overlay" class="modal-overlay" onclick="closeEditModal(event)" style="display: none;"></div>
    
    <!-- Edit Modal -->
    <div id="edit-modal" class="edit-modal" style="display: none;">
        <div class="edit-modal-header">
            <h2 id="edit-modal-title">Edit</h2>
            <button class="btn-close-modal" onclick="closeEditModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="edit-modal-content" id="edit-modal-content">
            <!-- Form content will be loaded here -->
        </div>
    </div>

    <!-- Add Modal Overlay -->
    <div id="add-modal-overlay" class="modal-overlay" onclick="closeAddModal(event)" style="display: none;"></div>
    
    <!-- Add Menu Item Modal -->
    <div id="add-menu-modal" class="edit-modal" style="display: none;">
        <div class="edit-modal-header">
            <h2>Add New Menu Item</h2>
            <button class="btn-close-modal" onclick="closeAddModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="edit-modal-content">
            <form onsubmit="saveNewMenuItem(event)" class="modal-form">
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g., Beef Broccoli" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <div class="select-wrapper">
                        <select name="category" class="form-input form-select" required>
                            <option value="">Select a category...</option>
                            <option value="Beef">Beef</option>
                            <option value="Chicken">Chicken</option>
                            <option value="Pork">Pork</option>
                            <option value="Seafood">Seafood</option>
                            <option value="Pasta">Pasta</option>
                            <option value="Appetizer">Appetizer</option>
                            <option value="Salad">Salad</option>
                            <option value="Dessert">Dessert</option>
                            <option value="Drinks">Drinks</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input form-textarea" rows="3" placeholder="Describe the dish..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Price</label>
                    <div class="price-input-wrapper">
                        <span class="currency-symbol">₱</span>
                        <input type="number" name="price" class="form-input price-input" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                </div>
                <div class="form-group availability-group">
                    <label class="form-label">Item Status</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Active</span>
                    </label>
                </div>
                <button type="submit" class="btn-save-changes">Add Menu Item</button>
            </form>
        </div>
    </div>

    <!-- Add Package Modal -->
    <div id="add-package-modal" class="edit-modal package-builder-modal" style="display: none;">
        <div class="edit-modal-header">
            <h2>Add New Package</h2>
            <button class="btn-close-modal" onclick="closeAddModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="edit-modal-content">
            <form onsubmit="saveNewPackage(event)" class="modal-form package-builder-form">
                <div class="form-group">
                    <label class="form-label">Package Name</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g., Budget Fiesta" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Serves (Pax)</label>
                    <input type="text" name="serves" class="form-input" placeholder="e.g., 10 pax per tray" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input form-textarea" rows="2" placeholder="Describe the package..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Price</label>
                    <div class="price-input-wrapper">
                        <span class="currency-symbol">₱</span>
                        <input type="number" name="total_price" class="form-input price-input" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                </div>
                
                <!-- Menu Items Selection -->
                <div class="form-group menu-selection-group">
                    <label class="form-label">Select Menu Items <span class="selection-count">(0 selected)</span></label>
                    <div class="menu-items-search">
                        <input type="text" class="form-input" id="menu-search" placeholder="Search dishes..." onkeyup="filterMenuItems()">
                    </div>
                    <div class="menu-items-gallery" id="menu-items-gallery">
                        <!-- Menu items will be loaded here -->
                        <div class="loading-menu">Loading menu items...</div>
                    </div>
                    <input type="hidden" name="selected_items" id="selected-items-input" required>
                </div>

                <div class="form-group availability-group">
                    <label class="form-label">Package Status</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-label">Active</span>
                    </label>
                </div>
                <button type="submit" class="btn-save-changes">Create Package</button>
            </form>
        </div>
    </div>

    <script>
    let currentEditId = null;
    let currentEditType = null;

    /**
     * Open edit modal for package or menu item
     */
    function openEditModal(id, type) {
        currentEditId = id;
        currentEditType = type;
        
        const modal = document.getElementById('edit-modal');
        const overlay = document.getElementById('edit-modal-overlay');
        const content = document.getElementById('edit-modal-content');
        const title = document.getElementById('edit-modal-title');
        
        title.textContent = type === 'menu' ? 'Edit Menu Item' : 'Edit Package';
        
        // Fetch item data (include inactive for editing)
        fetch(`../api/get-${type}.php?id=${id}&include_inactive=1`)
            .then(response => response.json())
            .then(data => {
                console.log('API response:', data);
                if (data.success) {
                    content.innerHTML = generateEditForm(data.data, type);
                    modal.style.display = 'flex';
                    overlay.style.display = 'block';
                    // Small delay to allow display to apply before adding animation class
                    setTimeout(() => {
                        modal.classList.add('active');
                        overlay.classList.add('active');
                    }, 10);
                    document.body.style.overflow = 'hidden';
                } else {
                    alert('Failed to load item details');
                }
            })
            .catch(error => {
                console.error('Error loading item:', error);
                alert('Failed to load item details. Check console (F12) for details.');
            });
    }

    /**
     * Open add modal
     */
    function openAddModal(type) {
        const overlay = document.getElementById('add-modal-overlay');
        
        if (type === 'menu') {
            const modal = document.getElementById('add-menu-modal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        } else if (type === 'package') {
            const modal = document.getElementById('add-package-modal');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            loadMenuItemsForPackage();
        }
        
        overlay.style.display = 'block';
        setTimeout(() => {
            overlay.classList.add('active');
        }, 10);
        document.body.style.overflow = 'hidden';
    }

    /**
     * Generate edit form HTML with professional styling
     */
    function generateEditForm(item, type) {
        const isActive = item.is_active ? 'checked' : '';
        
        if (type === 'menu') {
            return `
                <form onsubmit="saveEdit(event)" class="modal-form">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-input" value="${escapeHtml(item.name)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <div class="select-wrapper">
                            <select name="category" class="form-input form-select">
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
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input form-textarea" rows="4">${escapeHtml(item.description)}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price</label>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₱</span>
                            <input type="number" name="price" class="form-input price-input" value="${parseFloat(item.price).toFixed(2)}" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-group availability-group">
                        <label class="form-label">Item Availability</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" ${isActive}>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">${item.is_active ? 'Active' : 'Inactive'}</span>
                        </label>
                    </div>
                    <button type="submit" class="btn-save-changes">Save Changes</button>
                </form>
            `;
        } else {
            return `
                <form onsubmit="saveEdit(event)" class="modal-form">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-input" value="${escapeHtml(item.name)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Serves</label>
                        <input type="text" name="serves" class="form-input" value="${escapeHtml(item.serves)}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input form-textarea" rows="4">${escapeHtml(item.description)}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Price</label>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">₱</span>
                            <input type="number" name="total_price" class="form-input price-input" value="${parseFloat(item.total_price).toFixed(2)}" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-group availability-group">
                        <label class="form-label">Package Availability</label>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" ${isActive}>
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">${item.is_active ? 'Active' : 'Inactive'}</span>
                        </label>
                    </div>
                    <button type="submit" class="btn-save-changes">Save Changes</button>
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
                closeEditModal();
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
     * Close edit modal
     */
    function closeEditModal(event) {
        if (event && event.target !== event.currentTarget) return;
        
        const modal = document.getElementById('edit-modal');
        const overlay = document.getElementById('edit-modal-overlay');
        
        modal.classList.remove('active');
        overlay.classList.remove('active');
        
        // Wait for animation to finish before hiding
        setTimeout(() => {
            modal.style.display = 'none';
            overlay.style.display = 'none';
        }, 300);
        
        document.body.style.overflow = '';
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

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
            closeAddModal();
        }
    });

    // ============================================
    // ADD MODAL FUNCTIONS
    // ============================================
    
    let selectedMenuItems = new Set();
    let allMenuItems = [];

    /**
     * Close add modal
     */
    function closeAddModal(event) {
        if (event && event.target !== event.currentTarget) return;
        
        const menuModal = document.getElementById('add-menu-modal');
        const packageModal = document.getElementById('add-package-modal');
        const overlay = document.getElementById('add-modal-overlay');
        
        menuModal.classList.remove('active');
        packageModal.classList.remove('active');
        overlay.classList.remove('active');
        
        // Wait for animation to finish before hiding
        setTimeout(() => {
            menuModal.style.display = 'none';
            packageModal.style.display = 'none';
            overlay.style.display = 'none';
        }, 300);
        
        document.body.style.overflow = '';
        
        // Reset forms
        document.querySelector('#add-menu-modal form')?.reset();
        document.querySelector('#add-package-modal form')?.reset();
        
        // Reset package builder
        selectedMenuItems.clear();
        updateSelectionCount();
        document.getElementById('menu-search').value = '';
    }

    /**
     * Save new menu item
     */
    function saveNewMenuItem(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_active = form.querySelector('[name="is_active"]')?.checked ? 1 : 0;
        data.price = parseFloat(data.price);
        
        fetch('../api/add-menu.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('✅ Menu item added successfully!');
                closeAddModal();
                
                // Add new row to table
                addMenuItemToTable(result.item);
                
                // Update counter
                updateMenuCounter(result.active_count);
            } else {
                alert('❌ Error: ' + (result.message || 'Failed to add menu item'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Failed to add menu item');
        });
    }

    /**
     * Load menu items for package builder
     */
    function loadMenuItemsForPackage() {
        const gallery = document.getElementById('menu-items-gallery');
        gallery.innerHTML = '<div class="loading-menu">Loading menu items...</div>';
        
        fetch('../api/get-all-menu.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allMenuItems = data.items;
                    renderMenuItemsGallery(data.grouped);
                } else {
                    gallery.innerHTML = '<div class="loading-menu">Failed to load menu items</div>';
                }
            })
            .catch(error => {
                console.error('Error loading menu items:', error);
                gallery.innerHTML = '<div class="loading-menu">Failed to load menu items</div>';
            });
    }

    /**
     * Render menu items gallery
     */
    function renderMenuItemsGallery(grouped) {
        const gallery = document.getElementById('menu-items-gallery');
        
        if (allMenuItems.length === 0) {
            gallery.innerHTML = '<div class="loading-menu">No menu items available. Please add dishes first.</div>';
            return;
        }
        
        let html = '';
        for (const [category, items] of Object.entries(grouped)) {
            html += `
                <div class="menu-category-group" data-category="${category.toLowerCase()}">
                    <div class="menu-category-header">${category}</div>
                    <div class="menu-items-grid">
                        ${items.map(item => `
                            <div class="menu-item-card ${selectedMenuItems.has(item.id) ? 'selected' : ''}" 
                                 data-id="${item.id}"
                                 data-name="${escapeHtml(item.name).toLowerCase()}"
                                 data-category="${item.category.toLowerCase()}"
                                 onclick="toggleMenuItem(${item.id})">
                                <div class="menu-item-select-indicator">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                </div>
                                <div class="menu-item-name">${escapeHtml(item.name)}</div>
                                <div class="menu-item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }
        
        gallery.innerHTML = html;
    }

    /**
     * Toggle menu item selection
     */
    function toggleMenuItem(id) {
        const card = document.querySelector(`.menu-item-card[data-id="${id}"]`);
        
        if (selectedMenuItems.has(id)) {
            selectedMenuItems.delete(id);
            card.classList.remove('selected');
        } else {
            selectedMenuItems.add(id);
            card.classList.add('selected');
        }
        
        updateSelectionCount();
    }

    /**
     * Update selection count display
     */
    function updateSelectionCount() {
        const count = selectedMenuItems.size;
        const label = document.querySelector('.selection-count');
        const input = document.getElementById('selected-items-input');
        
        label.textContent = `(${count} selected)`;
        input.value = Array.from(selectedMenuItems).join(',');
        
        // Update visual state
        document.querySelectorAll('.menu-item-card').forEach(card => {
            const id = parseInt(card.dataset.id);
            if (selectedMenuItems.has(id)) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    }

    /**
     * Filter menu items by search
     */
    function filterMenuItems() {
        const search = document.getElementById('menu-search').value.toLowerCase();
        const cards = document.querySelectorAll('.menu-item-card');
        const groups = document.querySelectorAll('.menu-category-group');
        
        cards.forEach(card => {
            const name = card.dataset.name;
            const category = card.dataset.category;
            
            if (name.includes(search) || category.includes(search)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Hide empty category groups
        groups.forEach(group => {
            const visibleCards = group.querySelectorAll('.menu-item-card:not([style*="display: none"])');
            group.style.display = visibleCards.length > 0 ? 'block' : 'none';
        });
    }

    /**
     * Save new package
     */
    function saveNewPackage(event) {
        event.preventDefault();
        
        if (selectedMenuItems.size === 0) {
            alert('❌ Please select at least one menu item for the package');
            return;
        }
        
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.is_active = form.querySelector('[name="is_active"]')?.checked ? 1 : 0;
        data.total_price = parseFloat(data.total_price);
        data.items = Array.from(selectedMenuItems);
        
        fetch('../api/add-package.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('✅ Package created successfully!');
                closeAddModal();
                
                // Add new row to table
                addPackageToTable(result.package);
                
                // Update counter
                updatePackageCounter(result.active_count);
            } else {
                alert('❌ Error: ' + (result.message || 'Failed to create package'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Failed to create package');
        });
    }

    /**
     * Add new menu item row to table
     */
    function addMenuItemToTable(item) {
        const tbody = document.querySelector('.menu-section tbody');
        const emptyState = document.querySelector('.menu-section .empty-state');
        
        // Remove empty state if exists
        if (emptyState) {
            emptyState.closest('.admin-card').querySelector('.table-responsive').style.display = 'block';
            emptyState.remove();
        }
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong class="item-name">${escapeHtml(item.name)}</strong></td>
            <td><span class="category-tag">${escapeHtml(item.category)}</span></td>
            <td class="description-cell">${escapeHtml(item.description || '').substring(0, 50)}${(item.description || '').length > 50 ? '...' : ''}</td>
            <td><strong class="price">₱${parseFloat(item.price).toFixed(2)}</strong></td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon btn-icon-edit" onclick="openEditModal(${item.id}, 'menu')" title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn-icon btn-icon-delete" onclick="deleteItem(${item.id}, 'menu')" title="Delete">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </div>
            </td>
        `;
        
        // Add animation
        row.style.animation = 'slideIn 0.3s ease';
        tbody.insertBefore(row, tbody.firstChild);
        
        // Update item count
        const countEl = document.querySelector('.menu-section .item-count');
        if (countEl) {
            const currentCount = parseInt(countEl.textContent.replace(/\D/g, '')) || 0;
            countEl.textContent = `(${currentCount + 1})`;
        }
    }

    /**
     * Add new package row to table
     */
    function addPackageToTable(pkg) {
        const tbody = document.querySelector('.packages-section tbody');
        const emptyState = document.querySelector('.packages-section .empty-state');
        
        // Remove empty state if exists
        if (emptyState) {
            emptyState.closest('.admin-card').querySelector('.table-responsive').style.display = 'block';
            emptyState.remove();
        }
        
        const isActive = pkg.is_active;
        const statusClass = isActive ? 'active' : 'inactive';
        const statusText = isActive ? 'Active' : 'Inactive';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong class="item-name">${escapeHtml(pkg.name)}</strong></td>
            <td>${escapeHtml(pkg.serves || 'N/A')}</td>
            <td><strong class="price">₱${parseFloat(pkg.total_price || 0).toFixed(2)}</strong></td>
            <td>
                <span class="status-badge ${statusClass}">${statusText}</span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-icon btn-icon-edit" onclick="openEditModal(${pkg.id}, 'package')" title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button class="btn-icon btn-icon-delete" onclick="deleteItem(${pkg.id}, 'package')" title="Delete">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </button>
                </div>
            </td>
        `;
        
        // Add animation
        row.style.animation = 'slideIn 0.3s ease';
        tbody.insertBefore(row, tbody.firstChild);
    }

    /**
     * Update menu counter
     */
    function updateMenuCounter(count) {
        // Update dashboard counter if on dashboard
        const menuCounter = document.querySelector('.stat-card-menu .stat-value');
        if (menuCounter) {
            menuCounter.textContent = count;
        }
        
        // Update count in header
        const countEl = document.querySelector('.menu-section .item-count');
        if (countEl) {
            countEl.textContent = `(${count})`;
        }
    }

    /**
     * Update package counter
     */
    function updatePackageCounter(count) {
        // Update dashboard counter if on dashboard
        const pkgCounter = document.querySelector('.stat-card-packages .stat-value');
        if (pkgCounter) {
            pkgCounter.textContent = count;
        }
    }
    </script>

    <style>
    /* ============================================
       UNIFIED PACKAGES & MENU STYLES
       ============================================ */
    
    /* Section Spacing */
    .packages-section {
        margin-bottom: 80px;
    }
    
    .menu-section {
        margin-bottom: 2rem;
    }
    
    /* Artisanal Headers */
    .artisanal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 0 0.25rem;
    }
    
    .section-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.35rem;
        font-weight: 700;
        color: #4a1414;
        margin: 0;
        font-family: 'League Spartan', sans-serif;
    }
    
    .header-icon {
        font-size: 1.5rem;
    }
    
    .header-text {
        letter-spacing: -0.02em;
    }
    
    .item-count {
        font-size: 1rem;
        font-weight: 500;
        color: #8a2927;
        opacity: 0.7;
    }
    
    /* Unified Table Styling */
    .unified-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .unified-table thead th {
        background: linear-gradient(135deg, #f8f4f0 0%, #f0e6dc 100%);
        color: #4a1414;
        font-weight: 700;
        text-align: left;
        padding: 1rem 1.25rem;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        border-bottom: 2px solid rgba(138, 41, 39, 0.1);
    }
    
    .unified-table tbody tr {
        background: #fff;
        transition: background 0.2s ease;
    }
    
    .unified-table tbody tr:hover {
        background: #fdfbf9;
    }
    
    .unified-table td {
        padding: 1.125rem 1.25rem;
        border-bottom: 1px solid rgba(53, 21, 15, 0.06);
        vertical-align: middle;
    }
    
    .item-name {
        color: #2d1b1b;
        font-weight: 600;
        font-size: 0.95rem;
    }
    
    .price {
        color: #8a2927;
        font-weight: 700;
        font-size: 0.95rem;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.85rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .status-badge.active {
        background: rgba(40, 167, 69, 0.12);
        color: #1e7e34;
    }
    
    .status-badge.active::before {
        content: '';
        width: 6px;
        height: 6px;
        background: #28a745;
        border-radius: 50%;
    }
    
    .status-badge.inactive {
        background: rgba(108, 117, 125, 0.12);
        color: #495057;
    }
    
    .status-badge.inactive::before {
        content: '';
        width: 6px;
        height: 6px;
        background: #6c757d;
        border-radius: 50%;
    }
    
    .category-tag {
        display: inline-flex;
        padding: 0.35rem 0.75rem;
        background: rgba(213, 164, 55, 0.15);
        color: #8a2927;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    
    .description-cell {
        color: #6b5a55;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    
    /* Action Icon Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-icon {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        background: transparent;
    }
    
    .btn-icon svg {
        transition: all 0.2s ease;
    }
    
    .btn-icon-edit {
        background: #fff8e1;
        color: #f57c00;
    }
    
    .btn-icon-edit:hover {
        background: #f57c00;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(245, 124, 0, 0.25);
    }
    
    .btn-icon-delete {
        background: #ffebee;
        color: #c62828;
    }
    
    .btn-icon-delete:hover {
        background: #c62828;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(198, 40, 40, 0.25);
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #999;
    }
    
    .empty-icon {
        margin-bottom: 1rem;
        opacity: 0.6;
    }
    
    /* ============================================
       PROFESSIONAL EDIT MODAL
       ============================================ */
    
    /* Modal Overlay */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(74, 20, 20, 0.6);
        backdrop-filter: blur(4px);
        z-index: 1099;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    /* Edit Modal Container */
    .edit-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.95);
        width: 480px;
        max-width: calc(100vw - 2rem);
        max-height: calc(100vh - 2rem);
        background: #fff;
        border-radius: 25px;
        box-shadow: 0 32px 64px rgba(0, 0, 0, 0.25), 0 16px 32px rgba(74, 20, 20, 0.15);
        z-index: 1100;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .edit-modal.active {
        opacity: 1;
        visibility: visible;
        transform: translate(-50%, -50%) scale(1);
    }
    
    /* Modal Header - Deep Maroon */
    .edit-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        background: #4a1414;
        color: white;
        flex-shrink: 0;
    }
    
    .edit-modal-header h2 {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        font-family: 'League Spartan', sans-serif;
        letter-spacing: -0.02em;
    }
    
    /* Close Button - Gold/White */
    .btn-close-modal {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: #d5a437;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .btn-close-modal:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        transform: rotate(90deg);
    }
    
    /* Modal Content */
    .edit-modal-content {
        padding: 2rem;
        overflow-y: auto;
        flex: 1;
    }
    
    /* Modal Form */
    .modal-form {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    /* Labels - Smaller, bolded dark-grey */
    .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #4a4a4a;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    /* Input Fields - 10px rounded corners */
    .form-input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1rem;
        font-family: 'Inter', sans-serif;
        background: #fff;
        color: #2d1b1b;
        transition: all 0.2s ease;
    }
    
    .form-input:hover {
        border-color: #d5a437;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #8a2927;
        box-shadow: 0 0 0 3px rgba(138, 41, 39, 0.1);
    }
    
    .form-textarea {
        resize: vertical;
        min-height: 100px;
        line-height: 1.5;
    }
    
    .select-wrapper {
        position: relative;
    }
    
    .select-wrapper::after {
        content: '';
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 5px solid #8a2927;
        pointer-events: none;
    }
    
    .form-select {
        appearance: none;
        padding-right: 2.5rem;
        cursor: pointer;
    }
    
    /* Price Input with Currency Symbol */
    .price-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .currency-symbol {
        position: absolute;
        left: 1rem;
        font-weight: 600;
        color: #8a2927;
        font-size: 1rem;
        pointer-events: none;
    }
    
    .price-input {
        padding-left: 2rem;
        font-weight: 600;
        color: #8a2927;
    }
    
    /* Toggle Switch for Availability */
    .availability-group {
        margin-top: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid #f0e6dc;
    }
    
    .toggle-switch {
        display: flex;
        align-items: center;
        gap: 1rem;
        cursor: pointer;
    }
    
    .toggle-switch input {
        display: none;
    }
    
    .toggle-slider {
        position: relative;
        width: 52px;
        height: 28px;
        background: #e0e0e0;
        border-radius: 28px;
        transition: background 0.3s ease;
        flex-shrink: 0;
    }
    
    .toggle-slider::before {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 22px;
        height: 22px;
        background: white;
        border-radius: 50%;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .toggle-switch input:checked + .toggle-slider {
        background: #28a745;
    }
    
    .toggle-switch input:checked + .toggle-slider::before {
        transform: translateX(24px);
    }
    
    .toggle-label {
        font-size: 0.95rem;
        font-weight: 600;
        color: #4a4a4a;
    }
    
    /* Full-width Save Button - Secondary Maroon */
    .btn-save-changes {
        width: 100%;
        padding: 1rem 1.5rem;
        margin-top: 0.5rem;
        background: #8a2927;
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(138, 41, 39, 0.25);
    }
    
    .btn-save-changes:hover {
        background: #7a2420;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(138, 41, 39, 0.35);
    }
    
    .btn-save-changes:active {
        transform: translateY(0);
    }
    
    /* ============================================
       PACKAGE BUILDER - MENU SELECTION UI
       ============================================ */
    
    .package-builder-modal {
        width: 560px;
    }
    
    .menu-selection-group {
        margin-top: 0.5rem;
    }
    
    .selection-count {
        font-weight: 500;
        color: #8a2927;
        font-size: 0.9rem;
    }
    
    .menu-items-search {
        margin-bottom: 0.75rem;
    }
    
    .menu-items-search input {
        background: #fffaf2;
        border-color: #e8dfd5;
    }
    
    .menu-items-search input:focus {
        background: #fff;
        border-color: #d5a437;
    }
    
    .menu-items-gallery {
        max-height: 280px;
        overflow-y: auto;
        padding: 0.5rem;
        background: linear-gradient(180deg, #fffaf2 0%, #f8f4f0 100%);
        border-radius: 12px;
        border: 1px solid #e8dfd5;
    }
    
    .menu-items-gallery::-webkit-scrollbar {
        width: 6px;
    }
    
    .menu-items-gallery::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .menu-items-gallery::-webkit-scrollbar-thumb {
        background: #d5a437;
        border-radius: 3px;
    }
    
    .menu-category-group {
        margin-bottom: 1rem;
    }
    
    .menu-category-group:last-child {
        margin-bottom: 0;
    }
    
    .menu-category-header {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #8a2927;
        padding: 0.5rem 0.75rem;
        background: rgba(213, 164, 55, 0.12);
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }
    
    .menu-items-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .menu-item-card {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0.75rem;
        background: #fff;
        border: 2px solid transparent;
        border-radius: 14px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }
    
    .menu-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-color: #d5a437;
    }
    
    .menu-item-card.selected {
        background: linear-gradient(135deg, #e8f5e9 0%, #d4edda 100%);
        border-color: #28a745;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
    }
    
    .menu-item-select-indicator {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #e0e0e0;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        opacity: 0.5;
    }
    
    .menu-item-card.selected .menu-item-select-indicator {
        background: #28a745;
        opacity: 1;
    }
    
    .menu-item-card.selected .menu-item-select-indicator svg {
        stroke-dasharray: 20;
        stroke-dashoffset: 0;
        animation: checkmark 0.3s ease;
    }
    
    @keyframes checkmark {
        from { stroke-dashoffset: 20; }
        to { stroke-dashoffset: 0; }
    }
    
    .menu-item-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #2d1b1b;
        line-height: 1.3;
        padding-right: 1.5rem;
    }
    
    .menu-item-card.selected .menu-item-name {
        color: #155724;
    }
    
    .menu-item-price {
        font-size: 0.8rem;
        font-weight: 700;
        color: #8a2927;
    }
    
    .menu-item-card.selected .menu-item-price {
        color: #28a745;
    }
    
    .loading-menu {
        text-align: center;
        padding: 2rem;
        color: #999;
        font-size: 0.9rem;
    }
    
    /* Slide-in animation for new rows */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* Responsive */
    @media (max-width: 640px) {
        .edit-modal {
            width: 100%;
            max-width: none;
            border-radius: 20px 20px 0 0;
            top: auto;
            bottom: 0;
            left: 0;
            right: 0;
            transform: translateY(100%);
            max-height: 90vh;
        }
        
        .edit-modal.active {
            transform: translateY(0);
        }
        
        .edit-modal-header h2 {
            font-size: 1.15rem;
        }
        
        .edit-modal-content {
            padding: 1.5rem;
        }
        
        .menu-items-grid {
            grid-template-columns: 1fr;
        }
        
        .menu-items-gallery {
            max-height: 220px;
        }
    }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
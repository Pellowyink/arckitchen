<!-- Modular Editable Sidebar Component
     This sidebar pops up from the right when editing inquiries or bookings
     It allows inline editing of quantities, items, and dates
-->

<!-- Menu Item Picker Modal -->
<div class="menu-picker-modal" id="menuPickerModal">
    <div class="menu-picker-overlay" onclick="closeMenuPicker()"></div>
    <div class="menu-picker-content">
        <div class="menu-picker-header">
            <h3>Select Menu Item</h3>
            <button class="btn-close" onclick="closeMenuPicker()">✕</button>
        </div>
        <div class="menu-picker-body">
            <!-- Category Tabs -->
            <div class="category-tabs" id="menuCategoryTabs">
                <button class="category-tab active" data-category="MENU" onclick="filterMenuBySource('MENU')">
                    Menu Items
                </button>
                <button class="category-tab" data-category="PACKAGES" onclick="filterMenuBySource('PACKAGES')">
                    Packages
                </button>
            </div>
            <!-- Menu Items Grid -->
            <div class="menu-items-grid" id="menuItemsGrid">
                <div class="loading-text">Loading items...</div>
            </div>
        </div>
    </div>
</div>

<div class="edit-sidebar" id="edit-sidebar">
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <h2 id="sidebar-title">Edit Order Details</h2>
            <button class="btn-close" id="btn-close-sidebar" title="Close">✕</button>
        </div>

        <!-- Loading State -->
        <div class="sidebar-loading" id="sidebar-loading" style="display: none;">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>

        <!-- Main Content (Hidden during loading) -->
        <div class="sidebar-body" id="sidebar-body">
            
            <!-- Customer Information Section -->
            <section class="sidebar-section">
                <h3>👤 Customer Information</h3>
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="edit-customer-name" class="form-input" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="edit-customer-email" class="form-input" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" id="edit-customer-phone" class="form-input" readonly>
                </div>
            </section>

            <!-- Event Details Section -->
            <section class="sidebar-section">
                <h3>📅 Event Details</h3>
                <div class="form-group">
                    <label class="form-label">Event Date</label>
                    <input type="date" id="edit-event-date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Event Type</label>
                    <input type="text" id="edit-event-type" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Guest Count</label>
                    <input type="number" id="edit-guest-count" class="form-input" min="1">
                </div>
            </section>

            <!-- Order Items Section -->
            <section class="sidebar-section">
                <h3>🍽️ Order Items</h3>
                <div class="items-list" id="items-list">
                    <!-- Items will be populated here -->
                </div>
                <button class="btn-admin btn-secondary-admin" id="btn-add-item" style="width: 100%; margin-top: 0.5rem;" onclick="openMenuPicker()">+ Add Menu Item</button>
            </section>

            <!-- Special Requests Section -->
            <section class="sidebar-section">
                <h3>💬 Special Requests</h3>
                <textarea 
                    id="edit-special-requests" 
                    class="form-input form-textarea"
                    placeholder="Any special instructions or dietary requirements..."
                    rows="3"
                ></textarea>
            </section>

            <!-- Total Amount Section -->
            <section class="sidebar-section sidebar-total">
                <div class="total-breakdown">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">₱0.00</span>
                    </div>
                    <div class="total-row">
                        <span>Tax:</span>
                        <span id="tax">₱0.00</span>
                    </div>
                    <div class="total-row total-final">
                        <span>Total Amount:</span>
                        <span id="total-amount">₱0.00</span>
                    </div>
                </div>
            </section>

            <!-- Form Actions -->
            <div class="sidebar-actions">
                <button class="btn-admin btn-secondary-admin" id="btn-cancel-edit">Cancel</button>
                <button class="btn-admin btn-primary-admin" id="btn-save-changes">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS for Edit Sidebar -->
<style>
.edit-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    height: 100vh;
    width: 100%;
    display: none;
    z-index: 1000;
}

.edit-sidebar.active {
    display: flex;
}

.sidebar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
}

.sidebar-content {
    position: relative;
    width: 100%;
    max-width: 500px;
    height: 100%;
    background: #fffdf8;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    animation: slideInRight 0.3s ease-out;
    margin-left: auto;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 2px solid #4a1414;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-header h2 {
    margin: 0;
    color: #4a1414;
    font-size: 1.3rem;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #4a1414;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-close:hover {
    background: rgba(74, 20, 20, 0.1);
    border-radius: 4px;
}

.sidebar-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
}

.sidebar-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e0d5c7;
}

.sidebar-section:last-of-type {
    border-bottom: none;
}

.sidebar-section h3 {
    margin: 0 0 1rem 0;
    color: #4a1414;
    font-size: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.4rem;
    font-weight: 500;
    color: #4a1414;
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 0.6rem;
    border: 1px solid #d5a437;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
}

.form-input:focus {
    outline: none;
    border-color: #8a2927;
    box-shadow: 0 0 0 3px rgba(138, 41, 39, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.items-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.item-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    padding: 0.75rem;
    background: rgba(247, 241, 231, 0.5);
    border-radius: 10px;
    border: 1px solid transparent;
}

.item-row.package-row {
    background: linear-gradient(135deg, #fffdf8 0%, #f7efe2 100%);
    border-color: #d5a437;
}

.item-row input {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
}

.item-row .item-name {
    flex: 1;
}

.package-badge {
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.8rem;
}

.item-row .btn-remove:hover {
    background: #a01818;
}

.sidebar-total {
    background: rgba(74, 20, 20, 0.08);
    padding: 1rem;
    border-radius: 8px;
    border: none;
}

.total-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    color: #333;
}

.total-row.total-final {
    font-weight: 700;
    font-size: 1.1rem;
    color: #4a1414;
    border-top: 1px solid #d5a437;
    padding-top: 0.6rem;
}

.sidebar-actions {
    padding: 1.5rem;
    border-top: 1px solid #e0d5c7;
    display: flex;
    gap: 1rem;
    background: #f9f7f4;
}

.sidebar-actions .btn-admin {
    flex: 1;
}

.sidebar-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #4a1414;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e0d5c7;
    border-top-color: #4a1414;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 1rem;
}

/* Menu Picker Modal Styles */
.menu-picker-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2000;
    justify-content: center;
    align-items: center;
}

.menu-picker-modal.active {
    display: flex;
}

.menu-picker-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(3px);
}

.menu-picker-content {
    position: relative;
    background: #fffdf8;
    border-radius: 16px;
    width: 90%;
    max-width: 700px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease-out;
    z-index: 2001;
}

@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.menu-picker-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 2px solid #4a1414;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.menu-picker-header h3 {
    margin: 0;
    color: #4a1414;
    font-size: 1.2rem;
}

.menu-picker-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

/* Category Tabs */
.category-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e0d5c7;
}

.category-tab {
    padding: 0.5rem 1rem;
    border: 1px solid #d5a437;
    background: transparent;
    border-radius: 20px;
    cursor: pointer;
    color: #4a1414;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
}

.category-tab:hover {
    background: rgba(213, 164, 55, 0.1);
}

.category-tab.active {
    background: #8a2927;
    color: white;
    border-color: #8a2927;
}

/* Menu Items Grid */
.menu-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.menu-item-card {
    background: white;
    border: 2px solid #e0d5c7;
    border-radius: 12px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.menu-item-card:hover {
    border-color: #8a2927;
    box-shadow: 0 4px 12px rgba(138, 41, 39, 0.15);
    transform: translateY(-2px);
}

.menu-item-card .item-category {
    font-size: 0.7rem;
    color: #8a2927;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.menu-item-card .item-name {
    font-weight: 600;
    color: #4a1414;
    font-size: 0.95rem;
    line-height: 1.3;
}

.menu-item-card .item-price {
    color: #8a2927;
    font-weight: 700;
    font-size: 1.1rem;
    margin-top: auto;
}

.loading-text {
    text-align: center;
    color: #888;
    padding: 2rem;
    grid-column: 1 / -1;
}

.empty-state {
    text-align: center;
    color: #888;
    padding: 2rem;
    grid-column: 1 / -1;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .sidebar-content {
        max-width: 100%;
    }
}
</style>

<!-- JavaScript for Edit Sidebar -->
<script>
let currentEditId = null;
let currentEditType = null;

/**
 * Open the edit sidebar with data for a specific inquiry or booking
 */
function openEditModal(recordId, type) {
    currentEditId = recordId;
    currentEditType = type;
    
    const sidebar = document.getElementById('edit-sidebar');
    const loading = document.getElementById('sidebar-loading');
    const body = document.getElementById('sidebar-body');
    
    // Show loading state
    loading.style.display = 'flex';
    body.style.display = 'none';
    sidebar.classList.add('active');
    
    // Fetch data from server
    fetch(`../api/get-${type}.php?id=${recordId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateSidebarForm(data.record, type, data);
                loading.style.display = 'none';
                body.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load record');
            closeSidebar();
        });
}

/**
 * Populate sidebar form with record data
 */
function populateSidebarForm(record, type, data) {
    document.getElementById('edit-customer-name').value = record.customer_name || record.full_name || '';
    document.getElementById('edit-customer-email').value = record.customer_email || record.email || '';
    document.getElementById('edit-customer-phone').value = record.customer_phone || record.phone || '';
    document.getElementById('edit-event-date').value = record.event_date || '';
    document.getElementById('edit-event-type').value = record.event_type || '';
    document.getElementById('edit-guest-count').value = record.guest_count || '';
    document.getElementById('edit-special-requests').value = record.special_requests || record.message || '';
    
    // Populate items if available from data.items
    populateItemsList(data.items || [], data.total || 0);
}

/**
 * Populate the items list from API data
 */
function populateItemsList(items, total) {
    const itemsList = document.getElementById('items-list');
    itemsList.innerHTML = '';
    
    if (items && items.length > 0) {
        items.forEach((item, index) => {
            addItemRow(item, index);
        });
    } else {
        // Show empty state
        itemsList.innerHTML = '<p style="color: #888; text-align: center; padding: 1rem;">No items in this order</p>';
    }
    
    // Update totals display with actual total from database
    updateTotalsDisplay(total, items);
}

/**
 * Add an item row to the items list
 */
function addItemRow(item = {}, index = null) {
    const itemsList = document.getElementById('items-list');
    
    // Remove empty state if present
    const emptyState = itemsList.querySelector('p');
    if (emptyState && emptyState.textContent.includes('No items')) {
        emptyState.remove();
    }
    
    const isPackage = item.is_package == 1 || item.type === 'package';
    const icon = isPackage ? '📦' : '🍽️';
    const name = item.name || 'Unknown Item';
    const qty = item.quantity || 1;
    const price = item.unit_price || item.price || 0;
    const subtotal = item.subtotal || (qty * price) || 0;
    const category = item.category || (isPackage ? 'Package' : 'Item');
    const itemId = item.id || 'new-' + Date.now();
    
    const html = `
        <div class="item-row ${isPackage ? 'package-row' : ''}" data-item-id="${itemId}">
            <div class="item-info" style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 1.2rem;">${icon}</span>
                    <strong>${name}</strong>
                    ${isPackage ? '<span class="package-badge">PACKAGE</span>' : ''}
                </div>
                <small style="color: #888;">${category} • ₱${parseFloat(price).toFixed(2)} each</small>
                <input type="hidden" class="item-name" value="${name}">
                <input type="hidden" class="item-price" value="${price}">
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="number" class="item-qty" value="${qty}" min="1" max="99" style="width: 50px; padding: 0.4rem; border: 1px solid #ddd; border-radius: 4px; text-align: center;" onchange="updateTotals()">
                <div class="item-subtotal" style="font-weight: 600; color: #8a2927; min-width: 80px; text-align: right;">
                    ₱${parseFloat(subtotal).toFixed(2)}
                </div>
                <button type="button" class="btn-remove" onclick="removeItemRow(this)" style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 0.4rem 0.6rem; cursor: pointer; font-size: 0.8rem;">×</button>
            </div>
        </div>
    `;
    itemsList.insertAdjacentHTML('beforeend', html);
}

/**
 * Remove an item row
 */
function removeItemRow(btn) {
    btn.closest('.item-row').remove();
    updateTotals();
}

/**
 * Update total amounts based on items (for editable mode)
 */
function updateTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        const rowSubtotal = qty * price;
        subtotal += rowSubtotal;
        
        // Update the subtotal display for this row
        const subtotalEl = row.querySelector('.item-subtotal');
        if (subtotalEl) {
            subtotalEl.textContent = '₱' + rowSubtotal.toFixed(2);
        }
    });
    
    const tax = subtotal * 0.12; // 12% tax
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total-amount').textContent = '₱' + total.toFixed(2);
}

/**
 * Display totals from database/API (for view mode)
 */
function updateTotalsDisplay(total, items) {
    // Calculate subtotal from items
    let subtotal = 0;
    if (items && items.length > 0) {
        items.forEach(item => {
            subtotal += (parseFloat(item.subtotal) || (item.unit_price * item.quantity) || 0);
        });
    }
    
    const tax = total > subtotal ? total - subtotal : 0;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = tax > 0 ? '₱' + tax.toFixed(2) : '₱0.00';
    document.getElementById('total-amount').textContent = '₱' + parseFloat(total).toFixed(2);
}

/**
 * Close the sidebar
 */
function closeSidebar() {
    document.getElementById('edit-sidebar').classList.remove('active');
    currentEditId = null;
    currentEditType = null;
}

/**
 * Save changes made in the sidebar
 */
function saveSidebarChanges() {
    if (!currentEditId || !currentEditType) return;
    
    // Collect items data with all necessary fields
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const name = row.querySelector('.item-name')?.value || '';
        const qty = parseInt(row.querySelector('.item-qty')?.value) || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        const isPackage = row.classList.contains('package-row') ? 1 : 0;
        const itemId = row.dataset.itemId || null;
        
        if (name && qty > 0) {
            items.push({
                id: itemId,
                name: name,
                quantity: qty,
                unit_price: price,
                price: price,
                subtotal: qty * price,
                is_package: isPackage,
                type: isPackage ? 'package' : 'item'
            });
        }
    });
    
    // Get total amount
    const totalText = document.getElementById('total-amount').textContent;
    const totalAmount = parseFloat(totalText.replace('₱', '')) || 0;
    
    // Prepare update data
    const updateData = {
        id: currentEditId,
        type: currentEditType,
        event_date: document.getElementById('edit-event-date').value,
        event_type: document.getElementById('edit-event-type').value,
        guest_count: parseInt(document.getElementById('edit-guest-count').value) || 0,
        items_json: JSON.stringify(items),
        total_amount: totalAmount,
        special_requests: document.getElementById('edit-special-requests').value,
    };
    
    // Send update request
    fetch(`../api/update-${currentEditType}.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(updateData),
    })
    .then(async response => {
        const data = await response.json();
        if (data.success) {
            alert('Changes saved successfully!');
            closeSidebar();
            // Reload the table
            if (typeof applyFilters === 'function') {
                applyFilters();
            } else {
                window.location.reload();
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to save'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save changes. Check console for details.');
    });
}

// Menu Item Picker Functions
let allMenuItems = [];
let allPackages = [];
let menuCategories = [];
let currentMenuSource = 'MENU';

function openMenuPicker() {
    const modal = document.getElementById('menuPickerModal');
    modal.classList.add('active');
    loadPickerItems();
}

function closeMenuPicker() {
    document.getElementById('menuPickerModal').classList.remove('active');
}

function loadPickerItems() {
    const grid = document.getElementById('menuItemsGrid');
    grid.innerHTML = '<div class="loading-text">Loading items...</div>';
    
    // Load both menu items and packages
    Promise.all([
        fetch('../api/get-all-menu.php').then(r => r.json()),
        fetch('../api/get-all-packages.php').then(r => r.json())
    ])
    .then(([menuData, pkgData]) => {
        if (menuData.success) {
            allMenuItems = menuData.items || [];
            menuCategories = Object.keys(menuData.grouped || {});
        }
        if (pkgData.success) {
            allPackages = pkgData.items || [];
        }
        renderPickerItems();
    })
    .catch(err => {
        console.error('Error loading items:', err);
        grid.innerHTML = '<div class="empty-state">Failed to load items</div>';
    });
}

function filterMenuBySource(source) {
    currentMenuSource = source;
    
    // Update active tab
    document.querySelectorAll('#menuCategoryTabs .category-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.category === source);
    });
    
    renderPickerItems();
}

function renderPickerItems() {
    const grid = document.getElementById('menuItemsGrid');
    
    if (currentMenuSource === 'PACKAGES') {
        // Show packages
        if (allPackages.length === 0) {
            grid.innerHTML = '<div class="empty-state">No packages available</div>';
            return;
        }
        
        let html = '';
        allPackages.forEach(pkg => {
            html += `
                <div class="menu-item-card package-card" onclick="selectPackageItem(${pkg.id}, '${escapeHtml(pkg.name)}', ${pkg.price})">
                    <span class="item-category" style="background: #8a2927; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem;">PACKAGE</span>
                    <span class="item-name">${escapeHtml(pkg.name)}</span>
                    <span class="item-price">₱${parseFloat(pkg.price).toFixed(2)}</span>
                </div>
            `;
        });
        grid.innerHTML = html;
    } else {
        // Show menu items with category filter
        const items = allMenuItems.filter(item => item.is_active == 1);
        
        if (items.length === 0) {
            grid.innerHTML = '<div class="empty-state">No menu items available</div>';
            return;
        }
        
        let html = '';
        items.forEach(item => {
            html += `
                <div class="menu-item-card" onclick="selectMenuItem(${item.id}, '${escapeHtml(item.name)}', ${item.price}, '${escapeHtml(item.category)}')">
                    <span class="item-category">${escapeHtml(item.category)}</span>
                    <span class="item-name">${escapeHtml(item.name)}</span>
                    <span class="item-price">₱${parseFloat(item.price).toFixed(2)}</span>
                </div>
            `;
        });
        grid.innerHTML = html;
    }
}

function selectMenuItem(id, name, price, category) {
    // Add item to the order
    const newItem = {
        product_id: id,
        name: name,
        unit_price: price,
        price: price,
        quantity: 1,
        category: category,
        type: 'item',
        is_package: 0,
        subtotal: price
    };
    
    addItemRow(newItem);
    closeMenuPicker();
    updateTotals();
}

function selectPackageItem(id, name, price) {
    // Add package to the order
    const newItem = {
        product_id: id,
        name: name,
        unit_price: price,
        price: price,
        quantity: 1,
        category: 'Package',
        type: 'package',
        is_package: 1,
        subtotal: price
    };
    
    addItemRow(newItem);
    closeMenuPicker();
    updateTotals();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btn-close-sidebar').addEventListener('click', closeSidebar);
    document.getElementById('sidebar-overlay').addEventListener('click', closeSidebar);
    document.getElementById('btn-cancel-edit').addEventListener('click', closeSidebar);
    document.getElementById('btn-save-changes').addEventListener('click', saveSidebarChanges);
    
    // Update totals when item values change
    document.addEventListener('input', (e) => {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
            updateTotals();
        }
    });
    
    // Close menu picker on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMenuPicker();
        }
    });
});
</script>

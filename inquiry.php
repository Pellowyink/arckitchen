<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'ARC Kitchen | Booking Inquiry';
$menuItems = getMenuItems();
$packages = getPackages();
$errors = [];

// Get cart items if coming from menu
$cartItems = $_SESSION['cart'] ?? [];
$cartTotal = 0;
$cartPackage = null; // Track if there's a package in cart

foreach ($cartItems as $item) {
    $cartTotal += ($item['product_price'] * $item['quantity']);
    // Check if this is a package
    if ($item['type'] === 'package') {
        $cartPackage = $item;
    }
}

if (isPostRequest()) {
    $errors = validateRequiredFields([
        'full_name' => 'Full name',
        'email' => 'Email address',
        'phone' => 'Phone number',
        'event_date' => 'Event date',
    ]);

    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Check if cart is empty
    if (empty($_SESSION['cart'])) {
        $errors[] = 'Please add at least one menu item or package to your order.';
    }

    if (!$errors) {
        // Save inquiry first
        $connection = getDbConnection();
        if ($connection) {
            $stmt = $connection->prepare(
                "INSERT INTO inquiries (full_name, email, phone, event_date, event_type, guest_count, package_interest, message, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            
            if ($stmt) {
                $stmt->bind_param(
                    'sssssiss',
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['event_date'],
                    $_POST['event_type'],
                    $_POST['guest_count'],
                    $_POST['package_interest'],
                    $_POST['message']
                );
                
                if ($stmt->execute()) {
                    $inquiryId = $connection->insert_id;
                    
                    // Save cart items as inquiry items
                    $cartItems = $_SESSION['cart'] ?? [];
                    $savedItems = 0;
                    $failedItems = [];
                    
                    foreach ($cartItems as $item) {
                        // For items, save directly. For packages, save with package flag
                        $menuItemId = $item['type'] === 'package' ? null : ($item['product_id'] ?? null);
                        $isPackage = $item['type'] === 'package' ? 1 : 0;
                        $packageId = $item['type'] === 'package' ? ($item['product_id'] ?? null) : null;
                        
                        $itemStmt = $connection->prepare(
                            "INSERT INTO inquiry_items (inquiry_id, menu_item_id, is_package, package_id, quantity, unit_price, subtotal, notes) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                        );
                        
                        if (!$itemStmt) {
                            // Track failed item
                            $failedItems[] = $item['product_name'];
                            error_log("Prepare failed for item {$item['product_name']}: " . $connection->error);
                            continue;
                        }
                        
                        $subtotal = $item['product_price'] * $item['quantity'];
                        $notes = $item['notes'] ?? '';
                        
                        $itemStmt->bind_param(
                            'iiiiddsd',
                            $inquiryId,
                            $menuItemId,
                            $isPackage,
                            $packageId,
                            $item['quantity'],
                            $item['product_price'],
                            $subtotal,
                            $notes
                        );
                        
                        if ($itemStmt->execute()) {
                            $savedItems++;
                        } else {
                            $failedItems[] = $item['product_name'];
                            error_log("Execute failed for item {$item['product_name']}: " . $itemStmt->error);
                        }
                        $itemStmt->close();
                    }
                    
                    // Clear cart after successful submission
                    $_SESSION['cart'] = [];
                    
                    // Build success message
                    $successMsg = "Your order has been submitted successfully! ARC Kitchen will contact you shortly to confirm your booking.";
                    if (!empty($failedItems)) {
                        $successMsg .= " Note: Some items could not be saved (" . implode(", ", $failedItems) . "). Please contact us.";
                    }
                    
                    setFlashMessage('success', $successMsg);
                    redirect('inquiry.php?success=1&inquiry_id=' . $inquiryId);
                }
                $stmt->close();
            }
        }
        
        $errors[] = 'Failed to save your order. Please try again later.';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Booking Hero -->
<section class="page-hero">
    <div class="container">
        <div class="page-hero-card reveal">
            <span class="eyebrow">Complete Your Order</span>
            <h1>Review your order and select your event date</h1>
            <p>Your cart items are shown below. Select a date from the calendar, then fill in your details to confirm your booking.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <!-- Order Summary with Edit Controls -->
        <div class="section-card reveal" style="margin-bottom: 1.5rem;">
            <div class="order-summary-header">
                <h2>📦 Your Order Summary</h2>
                <button type="button" class="button button-small" onclick="openAddItemsSidebar()">+ Add More Items</button>
            </div>
            
            <?php if (!empty($cartItems)): ?>
            <div class="order-summary-list" id="orderSummaryList">
                <?php foreach ($cartItems as $index => $item): ?>
                <div class="order-item editable" data-item-id="<?php echo $item['id']; ?>">
                    <div class="order-item-info">
                        <strong><?php echo escape($item['product_name']); ?></strong>
                        <?php if ($item['notes']): ?>
                        <small><?php echo escape($item['notes']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="order-item-controls">
                        <button type="button" class="qty-btn" onclick="updateCartItemQty(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>−</button>
                        <span class="qty-display"><?php echo $item['quantity']; ?></span>
                        <button type="button" class="qty-btn" onclick="updateCartItemQty(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)" <?php echo $item['quantity'] >= 99 ? 'disabled' : ''; ?>>+</button>
                    </div>
                    <div class="order-item-price">₱<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></div>
                    <button type="button" class="remove-btn-sm" onclick="removeCartItem(<?php echo $item['id']; ?>)" title="Remove">×</button>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="order-total-bar">
                <span>Order Total:</span>
                <strong id="cartTotalDisplay">₱<?php echo number_format($cartTotal, 2); ?></strong>
            </div>
            <?php else: ?>
            <div class="empty-cart-message" style="text-align: center; padding: 2rem; color: #666;">
                <p>Your cart is empty. Add items from the menu to get started.</p>
                <a href="menu.php" class="button" style="margin-top: 1rem;">Browse Menu</a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Two Column Layout: Calendar + Form -->
        <div class="booking-layout">
            <!-- Left: Calendar -->
            <div class="calendar-section">
                <div class="section-card reveal">
                    <h3>📅 Select Event Date</h3>
                    <div id="selectedDateDisplay" class="selected-date-box" style="display: none;">
                        <span id="selectedDateValue"></span>
                    </div>
                    
                    <?php
                    // Get all booking dates
                    $allBookings = getBookings();
                    $bookedDates = [];
                    foreach ($allBookings as $b) {
                        if ($b['status'] === 'confirmed') {
                            $bookedDates[] = date('Y-m-d', strtotime($b['event_date']));
                        }
                    }
                    $bookedDates = array_unique($bookedDates);
                    
                    // Generate calendar (current month)
                    $currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
                    $currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
                    
                    $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                    $daysInMonth = date('t', $firstDay);
                    $monthName = date('F', $firstDay);
                    $firstDayOfWeek = date('w', $firstDay);
                    $today = date('Y-m-d');
                    
                    $prevMonth = $currentMonth - 1;
                    $prevYear = $currentYear;
                    if ($prevMonth < 1) {
                        $prevMonth = 12;
                        $prevYear--;
                    }
                    
                    $nextMonth = $currentMonth + 1;
                    $nextYear = $currentYear;
                    if ($nextMonth > 12) {
                        $nextMonth = 1;
                        $nextYear++;
                    }
                    ?>
                    
                    <?php require_once __DIR__ . '/includes/calendar.php'; ?>
                    
                    <div class="calendar-legend" style="margin-top: 1rem; display: flex; gap: 1rem; justify-content: center; font-size: 0.8rem;">
                        <span><span style="display: inline-block; width: 12px; height: 12px; background: #f44336; border-radius: 2px; margin-right: 4px;"></span>Booked</span>
                        <span><span style="display: inline-block; width: 12px; height: 12px; background: #9e9e9e; border-radius: 2px; margin-right: 4px;"></span>Blocked</span>
                        <span><span style="display: inline-block; width: 12px; height: 12px; background: #d5a437; border-radius: 2px; margin-right: 4px;"></span>Selected</span>
                    </div>
                </div>
            </div>
            
            <!-- Right: Booking Form -->
            <div class="form-section">
                <div class="section-card reveal">
            <h2>✅ Complete Your Booking</h2>
            <p style="color: var(--text-soft); margin-bottom: 1rem;">Review your order above and fill in your details to confirm your booking.</p>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message" style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <strong>✓ Order Submitted!</strong><br>
                    Your booking inquiry #<?php echo (int)$_GET['inquiry_id']; ?> has been received. ARC Kitchen will contact you shortly to confirm.
                </div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo escape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="post" data-validate>
                <input type="hidden" id="selectedDate" name="event_date" value="">
                
                <div class="form-grid">
                    <div class="field">
                        <label for="full_name">Full Name *</label>
                        <input id="full_name" name="full_name" type="text" required value="<?php echo escape($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email Address *</label>
                        <input id="email" name="email" type="email" required value="<?php echo escape($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="phone">Phone Number *</label>
                        <input id="phone" name="phone" type="text" required value="<?php echo escape($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="field">
                        <label for="event_type">Event Type</label>
                        <select id="event_type" name="event_type">
                            <option value="">Select event type</option>
                            <?php
                            $eventTypes = ['Birthday', 'Wedding', 'Corporate', 'Baptism', 'Family Gathering', 'Anniversary', 'Other'];
                            foreach ($eventTypes as $eventType):
                                $selected = ($_POST['event_type'] ?? '') === $eventType ? 'selected' : '';
                            ?>
                                <option value="<?php echo escape($eventType); ?>" <?php echo $selected; ?>><?php echo escape($eventType); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="guest_count">Guest Count</label>
                        <input id="guest_count" name="guest_count" type="number" min="1" value="<?php echo escape($_POST['guest_count'] ?? '50'); ?>">
                    </div>
                    <div class="field">
                        <label>Packages</label>
                        <button type="button" class="button" onclick="openPackagePicker()" style="width: 100%; background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);">
                            📦 Browse & Add Packages
                        </button>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Click to view available packages and add to your order
                        </small>
                    </div>
                </div>
                
                <div class="field-full">
                    <label for="message">Special Requirements & Menu Preferences</label>
                    <textarea id="message" name="message" rows="4" placeholder="Tell us about your dietary requirements, preferred menu items, venue details, or any special requests..."><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <button type="submit" class="button" style="padding: 1rem 2.5rem; font-size: 1.1rem; background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);">📩 Confirm & Submit Order</button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add Items Sidebar (Slide-out panel with menu) -->
<div class="add-items-overlay" id="addItemsOverlay" onclick="closeAddItemsSidebar()"></div>
<aside class="add-items-sidebar" id="addItemsSidebar">
    <div class="add-items-header">
        <h3>🍽️ Add to Your Order</h3>
        <button type="button" class="close-btn" onclick="closeAddItemsSidebar()">×</button>
    </div>
    <div class="add-items-content">
        <!-- Quick Add Menu Items -->
        <div class="quick-add-section">
            <h4>Menu Items</h4>
            <div class="quick-add-list">
                <?php foreach ($menuItems as $item): ?>
                <div class="quick-add-item" onclick="quickAddToCart(<?php echo $item['id']; ?>, 'item', '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)">
                    <div class="quick-add-info">
                        <strong><?php echo escape($item['name']); ?></strong>
                        <span class="quick-add-category"><?php echo escape($item['category']); ?></span>
                    </div>
                    <div class="quick-add-price">₱<?php echo number_format($item['price'], 0); ?></div>
                    <button type="button" class="quick-add-btn">+</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Quick Add Packages -->
        <div class="quick-add-section">
            <h4>Packages</h4>
            <div class="quick-add-list">
                <?php foreach ($packages as $package): ?>
                <div class="quick-add-item package" onclick="quickAddToCart(<?php echo $package['id']; ?>, 'package', '<?php echo addslashes($package['name']); ?>', <?php echo $package['total_price']; ?>)">
                    <div class="quick-add-info">
                        <strong><?php echo escape($package['name']); ?></strong>
                        <span class="quick-add-serves"><?php echo escape($package['serves']); ?></span>
                    </div>
                    <div class="quick-add-price">₱<?php echo number_format($package['total_price'], 0); ?></div>
                    <button type="button" class="quick-add-btn">+</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="add-items-footer">
        <button type="button" class="btn-done" onclick="closeAddItemsSidebar()">Done Adding Items</button>
    </div>
</aside>

<!-- Package Picker Modal -->
<div class="package-picker-modal" id="packagePickerModal">
    <div class="package-picker-overlay" onclick="closePackagePicker()"></div>
    <div class="package-picker-content">
        <div class="package-picker-header">
            <h3>📦 Select a Package</h3>
            <button class="btn-close" onclick="closePackagePicker()">✕</button>
        </div>
        <div class="package-picker-body">
            <div class="packages-grid" id="packagesGrid">
                <div class="loading-text">Loading packages...</div>
            </div>
        </div>
    </div>
</div>

<style>
/* Package Picker Modal Styles */
.package-picker-modal {
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
.package-picker-modal.active {
    display: flex;
}
.package-picker-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}
.package-picker-content {
    position: relative;
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease;
}
@keyframes modalSlideIn {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.package-picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #eee;
    background: #8a2927;
    color: white;
}
.package-picker-header h3 {
    margin: 0;
    font-size: 1.25rem;
}
.package-picker-header .btn-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: background 0.2s;
}
.package-picker-header .btn-close:hover {
    background: rgba(255,255,255,0.2);
}
.package-picker-body {
    padding: 1.5rem;
    max-height: 60vh;
    overflow-y: auto;
}
.packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}
.package-card {
    background: white;
    border: 2px solid #e5d5c5;
    border-radius: 10px;
    padding: 1.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}
.package-card:hover {
    border-color: #8a2927;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(138,41,39,0.15);
}
.package-card.selected {
    border-color: #4CAF50;
    background: #f8fff8;
}
.package-card h4 {
    margin: 0 0 0.5rem 0;
    color: #8a2927;
    font-size: 1.1rem;
}
.package-card .serves {
    color: #666;
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
}
.package-card .description {
    color: #555;
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 1rem;
    min-height: 2.5rem;
}
.package-card .price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #8a2927;
}
.package-card .add-btn {
    position: absolute;
    bottom: 1.25rem;
    right: 1.25rem;
    background: #d5a437;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.package-card .add-btn:hover {
    background: #c49430;
}
.loading-text {
    text-align: center;
    color: #666;
    padding: 2rem;
}
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}
</style>

<script>
// Override calendar component's selectDate
function selectDate(dateStr) {
    document.getElementById('selectedDate').value = dateStr;
    document.getElementById('selectedDateValue').textContent = new Date(dateStr).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const display = document.getElementById('selectedDateDisplay');
    display.style.display = 'block';
    display.classList.add('has-date');
}

// Initialize selected date if present
window.addEventListener('DOMContentLoaded', function() {
    const selectedDateInput = document.getElementById('selectedDate');
    if (selectedDateInput && selectedDateInput.value) {
        document.getElementById('selectedDateValue').textContent = selectedDateInput.value;
        const display = document.getElementById('selectedDateDisplay');
        display.style.display = 'block';
        display.classList.add('has-date');
    }
});

// Cart Management on Booking Page
function updateCartItemQty(itemId, newQty) {
    if (newQty < 1) return;
    
    fetch('includes/sidebar.php?action=update_cart_item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ item_id: itemId, quantity: newQty })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            // Update UI without reload
            updateCartUI(result.cart_items || [], result.cart_total || 0);
            showToast('Quantity updated');
        }
    });
}

function removeCartItem(itemId) {
    if (!confirm('Remove this item from your order?')) return;
    
    fetch('includes/sidebar.php?action=remove_cart_item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            // Update UI without reload
            updateCartUI(result.cart_items || [], result.cart_total || 0);
            showToast('Item removed');
        }
    });
}

// Add Items Sidebar
function openAddItemsSidebar() {
    document.getElementById('addItemsSidebar').classList.add('is-open');
    document.getElementById('addItemsOverlay').classList.add('is-visible');
    document.body.style.overflow = 'hidden';
}

function closeAddItemsSidebar() {
    document.getElementById('addItemsSidebar').classList.remove('is-open');
    document.getElementById('addItemsOverlay').classList.remove('is-visible');
    document.body.style.overflow = '';
}

function quickAddToCart(id, type, name, price) {
    const data = {
        product_id: id,
        product_name: name,
        product_price: price,
        quantity: 1,
        type: type,
        special_instructions: ''
    };
    
    fetch('includes/sidebar.php?action=add_to_cart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            // Update UI without reload
            updateCartUI(result.cart_items || [], result.cart_total || 0);
            showToast(`Added ${name}`);
        } else {
            alert('Failed to add item');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to add item');
    });
}

// Package Picker Functions
let allPackages = [];

function openPackagePicker() {
    const modal = document.getElementById('packagePickerModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    loadPackages();
}

function closePackagePicker() {
    const modal = document.getElementById('packagePickerModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function loadPackages() {
    const grid = document.getElementById('packagesGrid');
    grid.innerHTML = '<div class="loading-text">Loading packages...</div>';
    
    fetch('api/get-all-packages.php')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                allPackages = data.items || [];
                renderPackages(allPackages);
            } else {
                grid.innerHTML = '<div class="empty-state">Failed to load packages</div>';
            }
        })
        .catch(err => {
            console.error('Error loading packages:', err);
            grid.innerHTML = '<div class="empty-state">Error loading packages</div>';
        });
}

function renderPackages(packages) {
    const grid = document.getElementById('packagesGrid');
    
    if (packages.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <p>No packages available</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    packages.forEach(pkg => {
        html += `
            <div class="package-card" onclick="addPackageToCart(${pkg.id}, '${escapeHtml(pkg.name)}', ${pkg.price})">
                <h4>${escapeHtml(pkg.name)}</h4>
                <div class="serves">${pkg.description ? escapeHtml(pkg.description.substring(0, 60)) + '...' : 'Package'}</div>
                <div class="price">₱${parseFloat(pkg.price).toFixed(2)}</div>
                <button class="add-btn">+ Add</button>
            </div>
        `;
    });
    
    grid.innerHTML = html;
}

function addPackageToCart(pkgId, pkgName, pkgPrice) {
    const data = {
        product_id: pkgId,
        product_name: pkgName,
        product_price: pkgPrice,
        quantity: 1,
        type: 'package',
        special_instructions: ''
    };
    
    fetch('includes/sidebar.php?action=add_to_cart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            // Update UI without reload
            updateCartUI(result.cart_items || [], result.cart_total || 0);
            
            showToast(`Added ${pkgName}`);
            closePackagePicker();
        } else {
            alert('Failed to add package');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to add package');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Update cart UI dynamically without page reload
function updateCartUI(cartItems, cartTotal) {
    const container = document.getElementById('orderSummaryList');
    const totalDisplay = document.getElementById('cartTotalDisplay');
    
    if (cartItems.length === 0) {
        container.innerHTML = `
            <div class="empty-cart-message" style="text-align: center; padding: 2rem; color: #666;">
                <p>Your cart is empty. Add items from the menu to get started.</p>
                <a href="menu.php" class="button" style="margin-top: 1rem;">Browse Menu</a>
            </div>
        `;
    } else {
        let html = '';
        cartItems.forEach(item => {
            const isPackage = item.type === 'package';
            const icon = isPackage ? '📦' : '🍽️';
            const badge = isPackage ? '<span style="background: #8a2927; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.65rem; margin-left: 0.5rem;">PACKAGE</span>' : '';
            
            html += `
                <div class="order-item editable ${isPackage ? 'package-item' : ''}" data-item-id="${item.id}">
                    <div class="order-item-info">
                        <div style="display: flex; align-items: center;">
                            <span style="margin-right: 0.5rem;">${icon}</span>
                            <strong>${escapeHtml(item.product_name)}</strong>
                            ${badge}
                        </div>
                        ${item.notes ? `<small>${escapeHtml(item.notes)}</small>` : ''}
                    </div>
                    <div class="order-item-controls">
                        <button type="button" class="qty-btn" onclick="updateCartItemQty(${item.id}, ${item.quantity - 1})" ${item.quantity <= 1 ? 'disabled' : ''}>−</button>
                        <span class="qty-display">${item.quantity}</span>
                        <button type="button" class="qty-btn" onclick="updateCartItemQty(${item.id}, ${item.quantity + 1})" ${item.quantity >= 99 ? 'disabled' : ''}>+</button>
                    </div>
                    <div class="order-item-price">₱${parseFloat(item.product_price * item.quantity).toFixed(2)}</div>
                    <button type="button" class="remove-btn-sm" onclick="removeCartItem(${item.id})" title="Remove">×</button>
                </div>
            `;
        });
        container.innerHTML = html;
    }
    
    // Update total
    if (totalDisplay) {
        totalDisplay.textContent = '₱' + parseFloat(cartTotal).toFixed(2);
    }
}

// Escape HTML helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Toast notification
function showToast(message) {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = `<span style="font-size: 1.1rem;">✓</span> ${escapeHtml(message)}`;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('fade-out'), 2000);
    setTimeout(() => toast.remove(), 2300);
}
</script>

<style>
/* Order Summary Header */
.order-summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.order-summary-header h2 {
    margin: 0;
}

/* Editable Order Items */
.order-item.editable {
    display: grid;
    grid-template-columns: 1fr auto auto auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.75rem;
    background: rgba(247, 241, 231, 0.5);
    border-radius: 10px;
    margin-bottom: 0.5rem;
}

.order-item-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn {
    width: 28px;
    height: 28px;
    border: 1px solid #d5a437;
    border-radius: 6px;
    background: white;
    color: #8a2927;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.qty-btn:hover:not(:disabled) {
    background: #d5a437;
    color: white;
}

.qty-display {
    min-width: 24px;
    text-align: center;
    font-weight: 600;
    color: #4a1414;
}

.remove-btn-sm {
    width: 24px;
    height: 24px;
    border: none;
    background: rgba(244, 67, 54, 0.1);
    color: #f44336;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.2rem;
    line-height: 1;
}

.remove-btn-sm:hover {
    background: #f44336;
    color: white;
}

/* Two Column Layout */
.booking-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

@media (max-width: 900px) {
    .booking-layout {
        grid-template-columns: 1fr;
    }
}

.calendar-section h3 {
    margin: 0 0 1rem 0;
    text-align: center;
}

.selected-date-box {
    text-align: center;
    padding: 0.75rem;
    background: rgba(213, 164, 55, 0.1);
    border-radius: 12px;
    border: 2px solid #d5a437;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #4a1414;
}

.selected-date-box.has-date {
    background: #d5a437;
    color: white;
}

/* Add Items Sidebar */
.add-items-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.add-items-overlay.is-visible {
    opacity: 1;
    visibility: visible;
}

.add-items-sidebar {
    position: fixed;
    top: 0;
    right: -500px;
    width: 500px;
    max-width: 95vw;
    height: 100vh;
    background: #fffdf8;
    z-index: 999;
    transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.add-items-sidebar.is-open {
    right: 0;
}

.add-items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 2px solid rgba(138, 41, 39, 0.1);
}

.add-items-header h3 {
    margin: 0;
    font-size: 1.3rem;
    color: #4a1414;
}

.close-btn {
    width: 36px;
    height: 36px;
    border: 2px solid rgba(138, 41, 39, 0.2);
    border-radius: 10px;
    background: transparent;
    cursor: pointer;
    font-size: 1.5rem;
    color: #8a2927;
    display: flex;
    align-items: center;
    justify-content: center;
}

.add-items-content {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 1.5rem;
}

.quick-add-section {
    margin-bottom: 1.5rem;
}

.quick-add-section h4 {
    margin: 0 0 0.75rem 0;
    color: #8a2927;
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.quick-add-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border-radius: 10px;
    margin-bottom: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.quick-add-item:hover {
    border-color: #d5a437;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.quick-add-item.package {
    background: linear-gradient(135deg, #fffdf8 0%, #f7efe2 100%);
}

.quick-add-info {
    flex: 1;
}

.quick-add-info strong {
    display: block;
    color: #4a1414;
    font-size: 0.95rem;
}

.quick-add-category,
.quick-add-serves {
    font-size: 0.75rem;
    color: #888;
}

.quick-add-price {
    font-weight: 600;
    color: #8a2927;
    font-size: 0.95rem;
}

.quick-add-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
    border-radius: 8px;
    font-size: 1.2rem;
    cursor: pointer;
    transition: transform 0.2s;
}

.quick-add-btn:hover {
    transform: scale(1.1);
}

.add-items-footer {
    padding: 1rem 1.5rem;
    border-top: 2px solid rgba(138, 41, 39, 0.1);
}

.btn-done {
    width: 100%;
    padding: 1rem;
    border: none;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
}

/* Original styles preserved */
.order-summary-list {
    margin: 1rem 0;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: rgba(247, 241, 231, 0.5);
    border-radius: 10px;
    margin-bottom: 0.5rem;
}

.order-item-info {
    flex: 1;
}

.order-item-info small {
    display: block;
    color: #888;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.order-item-qty {
    margin: 0 1rem;
    color: #666;
}

.order-item-price {
    font-weight: 600;
    color: #8a2927;
    min-width: 80px;
    text-align: right;
}

.order-total-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    border-radius: 12px;
    color: white;
    margin-top: 1rem;
}

.order-total-bar strong {
    font-size: 1.3rem;
}

/* Toast Notification */
.toast-notification {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
    animation: slideUp 0.3s ease;
}

.toast-notification.fade-out {
    animation: fadeOut 0.3s ease forwards;
}

@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes fadeOut {
    to { transform: translateY(100%); opacity: 0; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

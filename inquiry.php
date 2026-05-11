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
        'event_time' => 'Event time',
        'delivery_time' => 'Delivery time',
        'street_address' => 'Street address',
        'city' => 'City',
        'province' => 'Province',
        'zip_code' => 'ZIP code',
    ]);

    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Check if cart is empty
    if (empty($_SESSION['cart'])) {
        $errors[] = 'Please add at least one menu item or package to your order.';
    }

    $eventDate = $_POST['event_date'] ?? '';
    if ($eventDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
        $availability = checkDateAvailability($eventDate);
        if (!$availability['can_select']) {
            $errors[] = 'The selected event date is fully booked. Please choose another date.';
        }
    } elseif ($eventDate) {
        $errors[] = 'Please select a valid event date.';
    }

    if (!$errors) {
        // Save inquiry first
        $connection = getDbConnection();
        if ($connection) {
            $stmt = $connection->prepare(
                "INSERT INTO inquiries (full_name, email, phone, event_date, event_time, delivery_time, street_address, city, province, zip_code, landmarks, event_location, event_type, guest_count, package_interest, message, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            
            if ($stmt) {
                // Build full event location from structured address
                $fullLocation = $_POST['street_address'] . ', ' . $_POST['city'] . ', ' . $_POST['province'] . ' ' . $_POST['zip_code'];
                
                $stmt->bind_param(
                    'sssssssssssssiiss',
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['event_date'],
                    $_POST['event_time'],
                    $_POST['delivery_time'],
                    $_POST['street_address'],
                    $_POST['city'],
                    $_POST['province'],
                    $_POST['zip_code'],
                    $_POST['landmarks'] ?? '',
                    $fullLocation,
                    $_POST['event_type'],
                    $_POST['guest_count'],
                    $_POST['package_interest'] ?? '',
                    $_POST['message'] ?? ''
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
                    
                    // Send confirmation email to customer
                    $emailData = [
                        'inquiry_id' => $inquiryId,
                        'full_name' => $_POST['full_name'],
                        'email' => $_POST['email'],
                        'phone' => $_POST['phone'],
                        'event_type' => $_POST['event_type'],
                        'event_date' => $_POST['event_date'],
                        'event_time' => $_POST['event_time'],
                        'delivery_time' => $_POST['delivery_time'],
                        'street_address' => $_POST['street_address'],
                        'city' => $_POST['city'],
                        'province' => $_POST['province'],
                        'zip_code' => $_POST['zip_code'],
                        'landmarks' => $_POST['landmarks'] ?? '',
                        'event_location' => $fullLocation,
                        'guest_count' => $_POST['guest_count'],
                        'message' => $_POST['message'] ?? '',
                        'items' => $cartItems
                    ];
                    
                    $emailResult = sendArcEmail(
                        $_POST['email'],
                        'Inquiry Received! - Arc Kitchen',
                        'new_inquiry',
                        $emailData
                    );
                    
                    if (!$emailResult['success']) {
                        error_log("Failed to send inquiry confirmation email: " . $emailResult['message']);
                    }
                    
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
        
        // Log the actual error for debugging
        if (isset($connection) && $connection->error) {
            error_log("Inquiry save failed: " . $connection->error);
            $errors[] = 'Database error: ' . $connection->error;
        } else {
            $errors[] = 'Failed to save your order. Please try again later.';
        }
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
            
            <div class="order-summary-list" id="orderSummaryList">
                <?php if (!empty($cartItems)): ?>
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
                <?php else: ?>
                <div class="empty-cart-message" style="text-align: center; padding: 2rem; color: #666;">
                    <p>Your cart is empty. Add items from the menu to get started.</p>
                    <a href="menu.php" class="button" style="margin-top: 1rem;">Browse Menu</a>
                </div>
                <?php endif; ?>
            </div>
            <div class="order-total-bar">
                <span>Order Total:</span>
                <strong id="cartTotalDisplay">₱<?php echo number_format($cartTotal, 2); ?></strong>
            </div>
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
                    
                    <div class="calendar-legend" style="margin-top: 1rem; display: flex; gap: 1rem; justify-content: center; font-size: 0.8rem; flex-wrap: wrap;">
                        <span><span style="display: inline-block; width: 12px; height: 12px; background: #4CAF50; border-radius: 50%; margin-right: 4px;"></span>Available</span>
                        <span><span style="display: inline-block; width: 12px; height: 12px; background: linear-gradient(135deg, #FFC107 0%, #FFB300 100%); border-radius: 50%; margin-right: 4px;"></span>Limited Slots</span>
                        <span><span style="display: inline-block; width: 12px; height: 12px; background: #f44336; border-radius: 50%; margin-right: 4px;"></span>Fully Booked</span>
                    </div>
                </div>
            </div>
            
            <!-- Right: Booking Form -->
            <div class="form-section">
                <div class="section-card reveal">
            <h2>✅ Complete Your Booking</h2>
            <p style="color: var(--text-soft); margin-bottom: 1rem;">Review your order above and fill in your details to confirm your booking.</p>
            
            
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
                
                <!-- Complete Address Section -->
                <div class="field-full" style="margin-top: 1rem; background: #fffdf8; padding: 1rem; border-radius: 12px; border: 2px solid #e5d5c5;">
                    <h4 style="color: #4a1414; margin: 0 0 1rem 0; font-size: 1rem;">📍 Complete Delivery Address</h4>
                    
                    <div style="display: grid; gap: 0.75rem;">
                        <div>
                            <label for="street_address">Street Address *</label>
                            <textarea id="street_address" name="street_address" required rows="2" placeholder="House/Building number, Street name, Barangay..." style="background: #fff; border: 2px solid #e5d5c5; border-radius: 25px; padding: 0.75rem 1rem; color: #4a1414; width: 100%; resize: vertical;"><?php echo escape($_POST['street_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <div>
                                <label for="city">City/Municipality *</label>
                                <select id="city" name="city" required style="background: #fff; border: 2px solid #e5d5c5; border-radius: 25px; padding: 0.75rem 1rem; color: #4a1414; width: 100%;">
                                    <option value="">Select City/Municipality</option>
                                    <option value="Angeles City" <?php echo ($_POST['city'] ?? '') === 'Angeles City' ? 'selected' : ''; ?>>Angeles City</option>
                                    <option value="Apalit" <?php echo ($_POST['city'] ?? '') === 'Apalit' ? 'selected' : ''; ?>>Apalit</option>
                                    <option value="Arayat" <?php echo ($_POST['city'] ?? '') === 'Arayat' ? 'selected' : ''; ?>>Arayat</option>
                                    <option value="Bacolor" <?php echo ($_POST['city'] ?? '') === 'Bacolor' ? 'selected' : ''; ?>>Bacolor</option>
                                    <option value="Balibago" <?php echo ($_POST['city'] ?? '') === 'Balibago' ? 'selected' : ''; ?>>Balibago</option>
                                    <option value="Basa Air Base" <?php echo ($_POST['city'] ?? '') === 'Basa Air Base' ? 'selected' : ''; ?>>Basa Air Base</option>
                                    <option value="Candaba" <?php echo ($_POST['city'] ?? '') === 'Candaba' ? 'selected' : ''; ?>>Candaba</option>
                                    <option value="Clark" <?php echo ($_POST['city'] ?? '') === 'Clark' ? 'selected' : ''; ?>>Clark</option>
                                    <option value="Dau, Mabalacat" <?php echo ($_POST['city'] ?? '') === 'Dau, Mabalacat' ? 'selected' : ''; ?>>Dau, Mabalacat</option>
                                    <option value="Floridablanca" <?php echo ($_POST['city'] ?? '') === 'Floridablanca' ? 'selected' : ''; ?>>Floridablanca</option>
                                    <option value="Guagua" <?php echo ($_POST['city'] ?? '') === 'Guagua' ? 'selected' : ''; ?>>Guagua</option>
                                    <option value="Lubao" <?php echo ($_POST['city'] ?? '') === 'Lubao' ? 'selected' : ''; ?>>Lubao</option>
                                    <option value="Mabalacat" <?php echo ($_POST['city'] ?? '') === 'Mabalacat' ? 'selected' : ''; ?>>Mabalacat</option>
                                    <option value="Macabebe" <?php echo ($_POST['city'] ?? '') === 'Macabebe' ? 'selected' : ''; ?>>Macabebe</option>
                                    <option value="Magalang" <?php echo ($_POST['city'] ?? '') === 'Magalang' ? 'selected' : ''; ?>>Magalang</option>
                                    <option value="Masantol" <?php echo ($_POST['city'] ?? '') === 'Masantol' ? 'selected' : ''; ?>>Masantol</option>
                                    <option value="Mexico" <?php echo ($_POST['city'] ?? '') === 'Mexico' ? 'selected' : ''; ?>>Mexico</option>
                                    <option value="Minalin" <?php echo ($_POST['city'] ?? '') === 'Minalin' ? 'selected' : ''; ?>>Minalin</option>
                                    <option value="Porac" <?php echo ($_POST['city'] ?? '') === 'Porac' ? 'selected' : ''; ?>>Porac</option>
                                    <option value="San Fernando" <?php echo ($_POST['city'] ?? '') === 'San Fernando' ? 'selected' : ''; ?>>San Fernando</option>
                                    <option value="San Luis" <?php echo ($_POST['city'] ?? '') === 'San Luis' ? 'selected' : ''; ?>>San Luis</option>
                                    <option value="San Simon" <?php echo ($_POST['city'] ?? '') === 'San Simon' ? 'selected' : ''; ?>>San Simon</option>
                                    <option value="Santa Ana" <?php echo ($_POST['city'] ?? '') === 'Santa Ana' ? 'selected' : ''; ?>>Santa Ana</option>
                                    <option value="Santa Cruz, Lubao" <?php echo ($_POST['city'] ?? '') === 'Santa Cruz, Lubao' ? 'selected' : ''; ?>>Santa Cruz, Lubao</option>
                                    <option value="Santa Rita" <?php echo ($_POST['city'] ?? '') === 'Santa Rita' ? 'selected' : ''; ?>>Santa Rita</option>
                                    <option value="Santo Tomas" <?php echo ($_POST['city'] ?? '') === 'Santo Tomas' ? 'selected' : ''; ?>>Santo Tomas</option>
                                    <option value="Sasmuan" <?php echo ($_POST['city'] ?? '') === 'Sasmuan' ? 'selected' : ''; ?>>Sasmuan</option>
                                </select>
                            </div>
                            <div>
                                <label for="province">Province *</label>
                                <input id="province" name="province" type="text" readonly value="Pampanga" style="background: #f9f9f9; border: 2px solid #e5d5c5; border-radius: 25px; padding: 0.75rem 1rem; color: #666; width: 100;">
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0.75rem;">
                            <div>
                                <label for="zip_code">ZIP Code *</label>
                                <input id="zip_code" name="zip_code" type="text" readonly placeholder="Auto-filled" maxlength="10" value="<?php echo escape($_POST['zip_code'] ?? ''); ?>" style="background: #f9f9f9; border: 2px solid #e5d5c5; border-radius: 25px; padding: 0.75rem 1rem; color: #666; width: 100;">
                                <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.8rem;">Auto-filled based on city selection</small>
                            </div>
                            <div>
                                <label for="landmarks">Nearby Landmarks</label>
                                <input id="landmarks" name="landmarks" type="text" placeholder="e.g., Near SM Mall, beside Shell gas station..." value="<?php echo escape($_POST['landmarks'] ?? ''); ?>" style="background: #fff; border: 2px solid #e5d5c5; border-radius: 25px; padding: 0.75rem 1rem; color: #4a1414; width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Preferred Delivery Time -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                    <div class="field">
                        <label for="event_time">Event Start Time *</label>
                        <input id="event_time" name="event_time" type="time" required value="<?php echo escape($_POST['event_time'] ?? '12:00'); ?>" style="background: #fffdf8; border: 2px solid #e5d5c5; border-radius: 25px; padding: 0.75rem 1rem; color: #4a1414;">
                    </div>
                    <div class="field">
                        <label for="delivery_time">Preferred Delivery Time *</label>
                        <input id="delivery_time" name="delivery_time" type="time" required value="<?php echo escape($_POST['delivery_time'] ?? '11:00'); ?>" style="background: #fffdf8; border: 2px solid #e5d5c5; border-radius: 25px; padding: 0.75rem 1rem; color: #4a1414;">
                        <small style="color: #8a2927; display: block; margin-top: 0.25rem;">What time to pick up/deliver your order?</small>
                    </div>
                </div>
                
                <div class="field-full">
                    <label for="message">Special Requirements & Menu Preferences</label>
                    <textarea id="message" name="message" rows="4" placeholder="Tell us about your dietary requirements, preferred menu items, venue details, or any special requests..."><?php echo escape($_POST['message'] ?? ''); ?></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <button type="button" onclick="showOrderSummary()" class="button" style="padding: 1rem 2.5rem; font-size: 1.1rem; background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);">📩 Confirm & Submit Order</button>
                </div>
            </form>

<!-- Order Summary Modal -->
<div id="orderSummaryOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; backdrop-filter: blur(4px);"></div>
<div id="orderSummaryModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 16px; max-width: 600px; width: 90%; max-height: 85vh; overflow-y: auto; z-index: 10001; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
    <div style="background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%); padding: 1.5rem; border-radius: 16px 16px 0 0;">
        <h3 style="color: white; margin: 0; font-size: 1.3rem; display: flex; align-items: center; gap: 0.75rem;">
            <span>📋</span> Review Your Order
        </h3>
    </div>
    <div style="padding: 1.5rem;">
        <div id="summaryContent">
            <!-- Content populated by JavaScript -->
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="button" onclick="closeOrderSummary()" style="flex: 1; padding: 0.875rem; border: 2px solid #ddd; background: white; border-radius: 8px; cursor: pointer; font-weight: 500; color: #666;">
                ← Edit Order
            </button>
            <button type="button" onclick="submitOrder()" style="flex: 1; padding: 0.875rem; border: none; background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%); color: white; border-radius: 8px; cursor: pointer; font-weight: 600;">
                ✓ Confirm & Submit
            </button>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- OTP Verification Modal -->
<div id="otpModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 11000; justify-content: center; align-items: center;">
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 11001;" onclick="closeOtpModal()"></div>
    <div style="position: relative; background: #ffffff; border-radius: 25px; max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(74, 20, 20, 0.3); z-index: 11002;">
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #4a1414 0%, #6c1d12 100%); padding: 1.5rem; text-align: center; border-radius: 25px 25px 0 0;">
            <h3 style="color: white; margin: 0; font-family: 'League Spartan', sans-serif; font-size: 1.5rem;">
                <span style="margin-right: 0.5rem;">🔐</span> Email Verification
            </h3>
        </div>
        
        <!-- Body -->
        <div style="padding: 2rem; text-align: center;">
            <p style="color: #5c4a42; font-size: 1rem; margin-bottom: 1.5rem; line-height: 1.6;">
                We've sent a 6-digit verification code to<br>
                <strong id="otpEmailDisplay" style="color: #4a1414;"></strong>
            </p>
            
            <!-- OTP Input -->
            <div style="margin: 2rem 0;">
                <input type="text" id="otpInput" maxlength="6" placeholder="000000" 
                    style="width: 100%; padding: 1rem; font-size: 2rem; text-align: center; letter-spacing: 0.5rem; 
                           border: 2px solid #ddd; border-radius: 15px; font-family: 'League Spartan', sans-serif;
                           color: #4a1414; font-weight: 700; outline: none; transition: border-color 0.3s;"
                    onfocus="this.style.borderColor='#8a2927'" 
                    onblur="this.style.borderColor='#ddd'"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            
            <!-- Error Message -->
            <div id="otpError" style="display: none; color: #f44336; font-size: 0.9rem; margin-bottom: 1rem; padding: 0.75rem; background: #ffebee; border-radius: 8px;"></div>
            
            <!-- Verify Button -->
            <button type="button" id="verifyOtpBtn" onclick="verifyOtp()" 
                style="width: 100%; padding: 1rem; background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%); 
                       color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 600; 
                       cursor: pointer; transition: all 0.3s; font-family: 'League Spartan', sans-serif;">
                Verify Code
            </button>
            
            <!-- Resend Code -->
            <div style="margin-top: 1.5rem;">
                <button type="button" id="resendOtpBtn" onclick="resendOtp()" 
                    style="background: none; border: none; color: #8a2927; font-size: 0.9rem; cursor: pointer; 
                           text-decoration: underline; font-weight: 500;" disabled>
                    Resend Code <span id="otpCountdown">(60s)</span>
                </button>
            </div>
            
            <!-- Cancel -->
            <button type="button" onclick="closeOtpModal()" 
                style="margin-top: 1rem; background: none; border: none; color: #666; font-size: 0.9rem; cursor: pointer;">
                ← Go Back
            </button>
        </div>
    </div>
</div>

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

<script src="assets/js/notifications.js"></script>
<script>
// Pampanga City to ZIP Code mapping
const pampangaZipCodes = {
    'Angeles City': '2009',
    'Apalit': '2016',
    'Arayat': '2012',
    'Bacolor': '2001',
    'Balibago': '2024',
    'Basa Air Base': '2007',
    'Candaba': '2013',
    'Clark': '2023',
    'Dau, Mabalacat': '2026',
    'Floridablanca': '2006',
    'Guagua': '2003',
    'Lubao': '2005',
    'Mabalacat': '2010',
    'Macabebe': '2018',
    'Magalang': '2011',
    'Masantol': '2017',
    'Mexico': '2021',
    'Minalin': '2019',
    'Porac': '2008',
    'San Fernando': '2000',
    'San Luis': '2014',
    'San Simon': '2015',
    'Santa Ana': '2022',
    'Santa Cruz, Lubao': '2025',
    'Santa Rita': '2002',
    'Santo Tomas': '2020',
    'Sasmuan': '2004'
};

// Auto-populate ZIP code when city is selected
document.getElementById('city').addEventListener('change', function() {
    const selectedCity = this.value;
    const zipCodeField = document.getElementById('zip_code');

    if (selectedCity && pampangaZipCodes[selectedCity]) {
        zipCodeField.value = pampangaZipCodes[selectedCity];
    } else {
        zipCodeField.value = '';
    }
});

// Initialize ZIP code on page load if city is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const citySelect = document.getElementById('city');
    const zipCodeField = document.getElementById('zip_code');

    if (citySelect.value && pampangaZipCodes[citySelect.value]) {
        zipCodeField.value = pampangaZipCodes[citySelect.value];
    }
});
</script>

<script>
(function() {
    'use strict';

    // System initialization state
    let systemReady = false;
    let formSubmitting = false;
    
    // Wait for DOM and verify session is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Check if cart/session is initialized
        checkSystemReady();
    });
    
    // Verify system state before allowing actions
    function checkSystemReady() {
        // Test API connection
        fetch('api/get-all-menu.php', { method: 'HEAD' })
            .then(() => {
                systemReady = true;
                console.log('ARC Kitchen: System ready');
            })
            .catch(() => {
                systemReady = false;
                console.warn('ARC Kitchen: System initializing...');
            });
    }
    
    // Add-to-cart wrapper for validation
    const originalQuickAdd = window.quickAddToCart;
    window.quickAddToCart = function(id, type, name, price) {
        if (!id || !price) {
            showArcError ? showArcError('Invalid item data. Please refresh the page.') : alert('Invalid item data');
            return;
        }
        return originalQuickAdd(id, type, name, price);
    };
    
    // Protect form submission - BLOCK default submission, use OTP flow instead
    const bookingForm = document.querySelector('form[action="inquiry.php"]');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            // ALWAYS prevent default form submission - we use OTP AJAX flow
            e.preventDefault();
            
            // Check system ready
            if (!systemReady) {
                if (typeof showArcWait === 'function') {
                    showArcWait('System initializing, please wait...');
                } else {
                    alert('System initializing, please wait...');
                }
                return false;
            }
            
            // Check if already submitting (double-click protection)
            if (formSubmitting) {
                return false;
            }
            
            // Validate cart has items
            const cartItems = document.querySelectorAll('.order-item');
            if (cartItems.length === 0) {
                if (typeof showArcError === 'function') {
                    showArcError('Please add at least one item to your order before submitting.');
                } else {
                    alert('Please add at least one item to your order before submitting.');
                }
                return false;
            }
            
            // The OTP flow is triggered by submitOrder() which is called from the Order Summary modal
            // This handler just prevents the default form submission
            return false;
        });
    }
    
    // Replace removeCartItem confirm with modal (if notifications are available)
    if (typeof showArcConfirm === 'function') {
        const originalRemoveCartItem = window.removeCartItem;
        window.removeCartItem = function(itemId) {
            showArcConfirm('Remove this item from your order?', function(confirmed) {
                if (confirmed) {
                    fetch('includes/sidebar.php?action=remove_cart_item', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ item_id: itemId })
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) {
                            updateCartUI(result.cart_items || [], result.cart_total || 0);
                            if (typeof showArcSuccess === 'function') {
                                showArcSuccess('Item removed');
                            }
                        } else {
                            if (typeof showArcError === 'function') {
                                showArcError('Failed to remove item');
                            }
                        }
                    });
                }
            });
        };
    }
    
    // Add spinner style
    const spinnerStyle = document.createElement('style');
    spinnerStyle.textContent = `
        .arc-spinner-inline {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: arc-spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
    `;
    document.head.appendChild(spinnerStyle);
})();
</script>

<script>
// Override calendar component's selectDate
function selectDate(dateStr) {
    const selectedCell = document.querySelector(`.calendar-table td[data-date="${dateStr}"]`);
    if (selectedCell && (
        selectedCell.classList.contains('fully_booked') ||
        selectedCell.classList.contains('booked') ||
        selectedCell.classList.contains('blocked') ||
        selectedCell.classList.contains('past')
    )) {
        return;
    }

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
    console.log('Opening sidebar...');
    const sidebar = document.getElementById('addItemsSidebar');
    const overlay = document.getElementById('addItemsOverlay');
    console.log('Sidebar element:', sidebar);
    console.log('Overlay element:', overlay);
    if (sidebar && overlay) {
        sidebar.classList.add('is-open');
        overlay.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
        console.log('Sidebar opened');
    } else {
        console.error('Sidebar elements not found!');
    }
}

function closeAddItemsSidebar() {
    const sidebar = document.getElementById('addItemsSidebar');
    const overlay = document.getElementById('addItemsOverlay');
    if (sidebar && overlay) {
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        document.body.style.overflow = '';
    }
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
    .then(async r => {
        const text = await r.text();
        try {
            // Try to find JSON in response (in case there's whitespace before/after)
            const jsonMatch = text.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                return JSON.parse(jsonMatch[0]);
            }
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', text);
            throw new Error('Invalid response from server');
        }
    })
    .then(result => {
        if (result.success) {
            // Update UI without reload
            updateCartUI(result.cart_items || [], result.cart_total || 0);
            showToast(`Added ${name}`);
        } else {
            console.error('Server error:', result.message);
            if (typeof showArcError === 'function') {
                showArcError(result.message || 'Failed to add item');
            } else {
                alert(result.message || 'Failed to add item');
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof showArcError === 'function') {
            showArcError('Failed to add item. Please try again.');
        } else {
            alert('Failed to add item. Please try again.');
        }
    });
}

// Package Picker Functions
let allPackages = [];

function openPackagePicker() {
    console.log('Opening package picker...');
    const modal = document.getElementById('packagePickerModal');
    console.log('Modal element:', modal);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        loadPackages();
        console.log('Package picker opened');
    } else {
        console.error('Package picker modal not found!');
    }
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
    .then(async r => {
        const text = await r.text();
        try {
            // Try to find JSON in response (in case there's whitespace before/after)
            const jsonMatch = text.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                return JSON.parse(jsonMatch[0]);
            }
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response:', text);
            throw new Error('Invalid response from server');
        }
    })
    .then(result => {
        if (result.success) {
            // Update UI without reload
            updateCartUI(result.cart_items || [], result.cart_total || 0);

            showToast(`Added ${pkgName}`);
            closePackagePicker();
        } else {
            console.error('Server error:', result.message);
            if (typeof showArcError === 'function') {
                showArcError(result.message || 'Failed to add package');
            } else {
                alert(result.message || 'Failed to add package');
            }
        }
    })
    .catch(err => {
        console.error('Error:', err);
        if (typeof showArcError === 'function') {
            showArcError('Failed to add package. Please try again.');
        } else {
            alert('Failed to add package. Please try again.');
        }
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

    // Check if elements exist (sidebar might not be loaded yet)
    if (!container) {
        console.warn('updateCartUI: orderSummaryList element not found');
        return;
    }

    // Safely parse cart data if it's a string
    let cartItemsArray = [];
    try {
        if (typeof cartItems === 'string') {
            cartItemsArray = JSON.parse(cartItems);
            if (typeof cartItemsArray === 'string') {
                cartItemsArray = JSON.parse(cartItemsArray); // Parse a second time if double-serialized
            }
        } else {
            cartItemsArray = cartItems;
        }
    } catch (e) {
        console.error("Failed to parse cart items:", e);
        cartItemsArray = [];
    }

    if (cartItemsArray.length === 0) {
        container.innerHTML = `
            <div class="empty-cart-message" style="text-align: center; padding: 2rem; color: #666;">
                <p>Your cart is empty. Add items from the menu to get started.</p>
                <a href="menu.php" class="button" style="margin-top: 1rem;">Browse Menu</a>
            </div>
        `;
        if (totalDisplay) {
            totalDisplay.textContent = '₱0.00';
        }
        return;
    }

    let html = '';
    cartItemsArray.forEach(item => {
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

// Order Summary Modal Functions
function showOrderSummary() {
    // Get form values
    const fullName = document.getElementById('full_name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const eventType = document.getElementById('event_type').value;
    const guestCount = document.getElementById('guest_count').value;
    const eventDate = document.getElementById('selectedDate').value;
    const message = document.getElementById('message').value;

    const eventTime = document.getElementById('event_time').value;
    const deliveryTime = document.getElementById('delivery_time').value;
    const streetAddress = document.getElementById('street_address').value.trim();
    const city = document.getElementById('city').value.trim();
    const province = document.getElementById('province').value.trim();
    const zipCode = document.getElementById('zip_code').value.trim();
    const landmarks = document.getElementById('landmarks')?.value?.trim() || '';

    // Validate required fields
    if (!fullName || !email || !phone || !eventDate || !eventTime || !deliveryTime || !streetAddress || !city || !province || !zipCode) {
        let missingFields = [];
        if (!fullName) missingFields.push('Full Name');
        if (!email) missingFields.push('Email');
        if (!phone) missingFields.push('Phone');
        if (!eventDate) missingFields.push('Event Date');
        if (!eventTime) missingFields.push('Event Start Time');
        if (!deliveryTime) missingFields.push('Delivery Time');
        if (!streetAddress) missingFields.push('Street Address');
        if (!city) missingFields.push('City');
        if (!province) missingFields.push('Province');
        if (!zipCode) missingFields.push('ZIP Code');
        
        if (typeof showArcError === 'function') {
            showArcError('Please fill in all required fields: ' + missingFields.join(', ') + '.');
        } else {
            alert('Please fill in all required fields: ' + missingFields.join(', ') + '.');
        }
        return;
    }

    const selectedCell = document.querySelector(`.calendar-table td[data-date="${eventDate}"]`);
    if (selectedCell && (
        selectedCell.classList.contains('fully_booked') ||
        selectedCell.classList.contains('booked') ||
        selectedCell.classList.contains('blocked') ||
        selectedCell.classList.contains('past')
    )) {
        if (typeof showArcError === 'function') {
            showArcError('That date is fully booked. Please choose another available date.');
        } else {
            alert('That date is fully booked. Please choose another available date.');
        }
        return;
    }

    // Check for items in the DOM (Order Summary) instead of AJAX call
    const orderItems = document.querySelectorAll('#orderSummaryList .order-item');
    if (orderItems.length === 0) {
        if (typeof showArcError === 'function') {
            showArcError('Your cart is empty. Please add items before submitting.');
        } else {
            alert('Your cart is empty. Please add items before submitting.');
        }
        return;
    }

    // Build summary HTML from DOM items
    let itemsHtml = '';
    let total = 0;
    orderItems.forEach(item => {
        const nameEl = item.querySelector('.order-item-info strong');
        const priceEl = item.querySelector('.order-item-price');
        const qtyEl = item.querySelector('.qty-display');
        const isPackage = item.classList.contains('package-item');

        const name = nameEl ? nameEl.textContent : 'Unknown Item';
        const priceText = priceEl ? priceEl.textContent : '₱0.00';
        const qty = qtyEl ? parseInt(qtyEl.textContent) : 1;
        const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
        const subtotal = price;
        total += subtotal;
        const icon = isPackage ? '📦' : '🍽️';

        itemsHtml += `
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9f9f9; border-radius: 8px; margin-bottom: 0.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span>${icon}</span>
                    <div>
                        <div style="font-weight: 600; color: #4a1414;">${name}</div>
                        <small style="color: #666;">Qty: ${qty} × ₱${(price/qty).toFixed(2)}</small>
                    </div>
                </div>
                <div style="font-weight: 600; color: #8a2927;">₱${subtotal.toFixed(2)}</div>
            </div>
        `;
    });

    // Format date
    const formattedDate = eventDate ? new Date(eventDate).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }) : 'Not selected';

    // Build complete summary
    const summaryHtml = `
        <div style="margin-bottom: 1.5rem;">
            <h4 style="color: #8a2927; margin: 0 0 1rem 0; font-size: 1.1rem;">📞 Customer Information</h4>
            <div style="background: #fafafa; padding: 1rem; border-radius: 8px;">
                <p style="margin: 0.25rem 0;"><strong>Name:</strong> ${fullName}</p>
                <p style="margin: 0.25rem 0;"><strong>Email:</strong> ${email}</p>
                <p style="margin: 0.25rem 0;"><strong>Phone:</strong> ${phone}</p>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h4 style="color: #8a2927; margin: 0 0 1rem 0; font-size: 1.1rem;">📅 Event Details</h4>
            <div style="background: #fafafa; padding: 1rem; border-radius: 8px;">
                <p style="margin: 0.25rem 0;"><strong>Date:</strong> ${formattedDate}</p>
                <p style="margin: 0.25rem 0;"><strong>Event Time:</strong> ${eventTime}</p>
                <p style="margin: 0.25rem 0;"><strong>Delivery Time:</strong> ${deliveryTime}</p>
                <p style="margin: 0.25rem 0;"><strong>Type:</strong> ${eventType || 'Not specified'}</p>
                <p style="margin: 0.25rem 0;"><strong>Guests:</strong> ${guestCount} pax</p>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h4 style="color: #8a2927; margin: 0 0 1rem 0; font-size: 1.1rem;">📍 Delivery Address</h4>
            <div style="background: #fafafa; padding: 1rem; border-radius: 8px;">
                <p style="margin: 0.25rem 0;"><strong>Street:</strong> ${streetAddress}</p>
                <p style="margin: 0.25rem 0;"><strong>City:</strong> ${city}</p>
                <p style="margin: 0.25rem 0;"><strong>Province:</strong> ${province}</p>
                <p style="margin: 0.25rem 0;"><strong>ZIP:</strong> ${zipCode}</p>
                ${landmarks ? `<p style="margin: 0.25rem 0;"><strong>Landmarks:</strong> ${landmarks}</p>` : ''}
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h4 style="color: #8a2927; margin: 0 0 1rem 0; font-size: 1.1rem;">🍽️ Order Items</h4>
            ${itemsHtml}
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #8a2927; color: white; border-radius: 8px; margin-top: 0.5rem;">
                <strong>Total:</strong>
                <strong style="font-size: 1.25rem;">₱${total.toFixed(2)}</strong>
                    </div>
                </div>

                ${message ? `
                <div style="margin-bottom: 1rem;">
                    <h4 style="color: #8a2927; margin: 0 0 1rem 0; font-size: 1.1rem;">📝 Special Requests</h4>
                    <div style="background: #fafafa; padding: 1rem; border-radius: 8px; color: #555;">
                        ${message}
                    </div>
                </div>
                ` : ''}
            `;

            document.getElementById('summaryContent').innerHTML = summaryHtml;

            // Show modal
            document.getElementById('orderSummaryOverlay').style.display = 'block';
            document.getElementById('orderSummaryModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
}


function closeOrderSummary() {
    document.getElementById('orderSummaryOverlay').style.display = 'none';
    document.getElementById('orderSummaryModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Global variables for OTP flow
let otpCountdownInterval = null;
let currentFormData = null;

function submitOrder() {
    // Get form data first
    const form = document.querySelector('form[data-validate]');
    if (!form) {
        showArcError('Form not found. Please refresh the page.');
        return;
    }

    // Validate form
    const email = form.querySelector('[name="email"]')?.value;
    if (!email || !email.includes('@')) {
        showArcError('Please enter a valid email address.');
        return;
    }

    // Close order summary modal
    closeOrderSummary();

    // Collect form data
    currentFormData = new FormData(form);

    // Show OTP modal and send code
    sendOtp();
}

function sendOtp() {
    const form = document.querySelector('form[data-validate]');
    const email = form.querySelector('[name="email"]')?.value;

    if (!email) {
        showArcError('Email address is required.');
        return;
    }

    if (typeof showArcLoading === 'function') showArcLoading('Sending verification code...');

    const formData = new FormData();
    formData.append('email', email);
    formData.append('customer_name', form.querySelector('[name="full_name"]')?.value || '');
    formData.append('customer_phone', form.querySelector('[name="phone"]')?.value || '');
    formData.append('event_type', form.querySelector('[name="event_type"]')?.value || '');
    formData.append('guest_count', form.querySelector('[name="guest_count"]')?.value || '');
    formData.append('event_date', form.querySelector('[name="event_date"]')?.value || '');
    formData.append('event_time', form.querySelector('[name="event_time"]')?.value || '');
    formData.append('delivery_time', form.querySelector('[name="delivery_time"]')?.value || '');
    formData.append('street_address', form.querySelector('[name="street_address"]')?.value || '');
    formData.append('city', form.querySelector('[name="city"]')?.value || '');
    formData.append('province', form.querySelector('[name="province"]')?.value || '');
    formData.append('zip_code', form.querySelector('[name="zip_code"]')?.value || '');
    formData.append('landmarks', form.querySelector('[name="landmarks"]')?.value || '');
    formData.append('special_requests', form.querySelector('[name="message"]')?.value || '');
    formData.append('total_amount', document.getElementById('cartTotalDisplay')?.textContent?.replace(/[^\d.-]/g, '') || '0');

    // FIX: Get cart items properly with the correct class names
    const cartItems = [];
    document.querySelectorAll('#orderSummaryList .order-item').forEach(item => {
        const name = item.querySelector('.order-item-info strong')?.textContent || '';
        const priceText = item.querySelector('.order-item-price')?.textContent || '0';
        const qtyText = item.querySelector('.qty-display')?.textContent || '1';

        // Clean price and calculate unit price
        const totalPrice = parseFloat(priceText.replace(/[^\d.-]/g, '')) || 0;
        const quantity = parseInt(qtyText) || 1;
        const unitPrice = totalPrice / quantity;

        cartItems.push({ name: name, price: unitPrice, quantity: quantity });
    });
    formData.append('items', JSON.stringify(cartItems));

    fetch('api/send-otp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Server crashed. Raw output:", text);
            throw new Error("Server error. Check network console.");
        }

        if (data.status === 'success') {
            showOtpModal(email);
            startOtpCountdown(data.cooldown || 60);
        } else {
            showArcError(data.message || 'Failed to send verification code.');
        }
    })
    .catch(error => {
        console.error('OTP Error:', error);
        showArcError(error.message || 'Network error.');
    })
    .finally(() => {
        if (typeof hideArcLoading === 'function') hideArcLoading();
    });
}

function showOtpModal(email) {
    // CRITICAL: Hide loading modal first to prevent overlay issues
    if (typeof hideArcLoading === 'function') {
        hideArcLoading();
    }

    document.getElementById('otpEmailDisplay').textContent = email;
    document.getElementById('otpInput').value = '';
    document.getElementById('otpError').style.display = 'none';
    document.getElementById('verifyOtpBtn').disabled = false;
    document.getElementById('verifyOtpBtn').textContent = 'Verify Code';

    const modal = document.getElementById('otpModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Focus on input after modal is visible
    setTimeout(() => {
        const otpInput = document.getElementById('otpInput');
        if (otpInput) otpInput.focus();
    }, 200);
}

function closeOtpModal() {
    const modal = document.getElementById('otpModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';

    // Clear countdown
    if (otpCountdownInterval) {
        clearInterval(otpCountdownInterval);
        otpCountdownInterval = null;
    }
}

function startOtpCountdown(seconds) {
    const btn = document.getElementById('resendOtpBtn');
    let countdownEl = document.getElementById('otpCountdown');

    // FIX: Guard clause to prevent UI crash
    if (!btn) return;

    // FIX: Recreate the countdown span if it was accidentally destroyed previously
    if (!countdownEl) {
        btn.innerHTML = 'Resend Code <span id="otpCountdown"></span>';
        countdownEl = document.getElementById('otpCountdown');
    }

    let remaining = seconds;
    btn.disabled = true;
    countdownEl.textContent = `(${remaining}s)`;

    otpCountdownInterval = setInterval(() => {
        remaining--;
        if (countdownEl) countdownEl.textContent = `(${remaining}s)`;

        if (remaining <= 0) {
            clearInterval(otpCountdownInterval);
            otpCountdownInterval = null;
            btn.disabled = false;
            // FIX: Keep the span alive when resetting text!
            btn.innerHTML = 'Resend Code <span id="otpCountdown"></span>';
        }
    }, 1000);
}

function resendOtp() {
    if (otpCountdownInterval) {
        clearInterval(otpCountdownInterval);
        otpCountdownInterval = null;
    }
    sendOtp();
}

function verifyOtp() {
    const otpInput = document.getElementById('otpInput');
    // CRITICAL: Remove ALL non-digit characters (spaces, dashes, etc.)
    const otpCode = otpInput.value.replace(/\D/g, '');
    const errorEl = document.getElementById('otpError');
    const verifyBtn = document.getElementById('verifyOtpBtn');

    if (otpCode.length !== 6 || !/^\d{6}$/.test(otpCode)) {
        errorEl.textContent = 'Please enter a valid 6-digit code (numbers only).';
        errorEl.style.display = 'block';
        verifyBtn.disabled = false;
        return;
    }

    // Disable button
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<span class="arc-spinner-inline"></span> Verifying...';

    const form = document.querySelector('form[data-validate]');
    const email = form.querySelector('[name="email"]')?.value;

    const formData = new FormData();
    formData.append('otp_code', otpCode);
    formData.append('email', email);

    fetch('api/verify-otp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Read as text first
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Malformed JSON response:", text);
            throw new Error("Server returned invalid response.");
        }

        if (data.status === 'success') {
            // OTP verified, submit the booking
            submitVerifiedBooking();
        } else {
            errorEl.textContent = data.message || 'Invalid verification code.';
            errorEl.style.display = 'block';
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify Code';

            if (data.remaining_attempts !== undefined && data.remaining_attempts <= 0) {
                // Too many attempts, close modal
                setTimeout(() => {
                    closeOtpModal();
                    showArcError('Too many failed attempts. Please try again.');
                }, 2000);
            }
        }
    })
    .catch(error => {
        console.error('Verification Error:', error);
        errorEl.textContent = error.message || 'Network error. Please try again.';
        errorEl.style.display = 'block';
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify Code';
    });
}

function submitVerifiedBooking() {
    closeOtpModal();

    if (typeof showArcLoading === 'function') {
        showArcLoading('Finalizing your booking...');
    }

    fetch('api/submit-booking.php', {
        method: 'POST'
    })
    .then(response => response.text()) // Read as text first
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Malformed JSON response:", text);
            throw new Error("Server returned invalid response.");
        }

        if (data.status === 'success') {
            // Show success modal
            showSuccessModal();
            // Clear cart UI
            document.getElementById('orderSummaryList').innerHTML = '';
            document.getElementById('cartTotalDisplay').textContent = '₱0.00';
            // Reset form
            document.querySelector('form[data-validate]').reset();
        } else {
            showArcError(data.message || 'Failed to submit booking. Please try again.');
        }
    })
    .catch(error => {
        console.error('Submission Error:', error);
        showArcError(error.message || 'Network error. Please try again.');
    })
    .finally(() => {
        // CRITICAL: Always hide loader
        if (typeof hideArcLoading === 'function') {
            hideArcLoading();
        }
    });
}

// Handle Enter key in OTP input
document.getElementById('otpInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        verifyOtp();
    }
});

// Auto-format OTP input - remove non-digits as user types
document.getElementById('otpInput')?.addEventListener('input', function(e) {
    // Remove any non-digit characters immediately
    this.value = this.value.replace(/\D/g, '');
    // Limit to 6 digits
    if (this.value.length > 6) {
        this.value = this.value.slice(0, 6);
    }
});
</script>

<!-- Centered Success Modal Overlay -->
<div id="successModalOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 9999;"></div>

<!-- Centered Success Modal -->
<div id="successModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9); background: #fffdf8; border-radius: 25px; padding: 3rem; max-width: 500px; width: 90%; box-shadow: 0 25px 50px rgba(0,0,0,0.3); z-index: 10000; text-align: center; border: 3px solid #8a2927;">
    <div style="font-size: 5rem; margin-bottom: 1rem; animation: checkmarkBounce 0.6s ease;">✅</div>
    <h2 style="color: #4a1414; margin: 0 0 1rem 0; font-size: 2rem; font-weight: 700;">Thank you!</h2>
    <p style="color: #666; margin: 0 0 2rem 0; font-size: 1.1rem; line-height: 1.6;">Your order has been received. ARC Kitchen will contact you shortly to confirm your booking details.</p>
    <a href="index.php" onclick="closeSuccessModal()" class="button" style="padding: 1rem 2.5rem; font-size: 1.1rem; background: #8a2927; color: white; border-radius: 25px; text-decoration: none; display: inline-block; font-weight: 600;">Back to Home</a>
</div>

<style>
    @keyframes checkmarkBounce {
        0% { transform: scale(0); opacity: 0; }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    @keyframes modalSlideIn {
        from { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
        to { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    }
    
    #successModal.show {
        animation: modalSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    }
    
    @media (max-width: 600px) {
        #successModal {
            padding: 2rem 1.5rem;
            width: 95%;
        }
        #successModal h2 {
            font-size: 1.5rem;
        }
        #successModal p {
            font-size: 1rem;
        }
    }
</style>

<script>
    function showSuccessModal() {
        document.getElementById('successModalOverlay').style.display = 'block';
        document.getElementById('successModal').style.display = 'block';
        document.getElementById('successModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSuccessModal() {
        document.getElementById('successModalOverlay').style.display = 'none';
        document.getElementById('successModal').style.display = 'none';
        document.getElementById('successModal').classList.remove('show');
        document.body.style.overflow = '';
    }
    
    // Close modal on overlay click
    document.getElementById('successModalOverlay')?.addEventListener('click', closeSuccessModal);
    
    <?php if (isset($_GET['success'])): ?>
    // Auto-show centered modal on success
    document.addEventListener('DOMContentLoaded', function() {
        showSuccessModal();
    });
    <?php endif; ?>
</script>

<script src="assets/js/notifications.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

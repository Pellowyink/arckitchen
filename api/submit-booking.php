<?php
/**
 * ARC Kitchen Booking Submission API (OTP-Protected)
 * Saves booking to database only after email verification
 */

// Set timezone to prevent time calculation issues
date_default_timezone_set('Asia/Manila'); // Philippine Time

// Production: Suppress error display, log to file only
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

session_start();

// Clear any accidental output/warnings before sending JSON
ob_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/functions.php';

// Strict Verification Check
if (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] !== true) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Access denied: Email verification required.']);
    exit;
}

// Get the pending booking data from session
$bookingData = $_SESSION['pending_booking_data'] ?? null;

if (!$bookingData) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'No pending booking data found.']);
    exit;
}

// Verify email matches
if ($_SESSION['verified_email_address'] !== $bookingData['customer_email']) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Email verification mismatch.']);
    exit;
}

// Clear verification flags after use
$_SESSION['email_verified'] = false;
unset($_SESSION['verified_email_address']);

// Get database connection
$connection = getDbConnection();
if (!$connection) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// Prepare data - Map to inquiries table column names
$full_name = $connection->real_escape_string($bookingData['customer_name']);
$email = $connection->real_escape_string($bookingData['customer_email']);
$phone = $connection->real_escape_string($bookingData['customer_phone']);
$event_type = $connection->real_escape_string($bookingData['event_type']);
$guest_count = intval($bookingData['guest_count']);
$event_date = $connection->real_escape_string($bookingData['event_date']);
$event_time = $connection->real_escape_string($bookingData['event_time']);
$event_location = $connection->real_escape_string($bookingData['event_location']);
$message = $connection->real_escape_string($bookingData['special_requests']);
$package_interest = $connection->real_escape_string($bookingData['items']);

$availability = checkDateAvailability($bookingData['event_date']);
if (!$availability['can_select']) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'The selected event date is unavailable. Please choose another date.']);
    exit;
}

// Insert into inquiries table using correct column names
$sql = "INSERT INTO inquiries (
    full_name, 
    email, 
    phone, 
    event_date,
    event_time,
    event_location,
    event_type, 
    guest_count, 
    package_interest, 
    message, 
    status, 
    created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

$stmt = $connection->prepare($sql);

if (!$stmt) {
    error_log("Booking insert prepare failed: " . $connection->error);
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $connection->error]);
    exit;
}

$stmt->bind_param(
    'ssssssisss',
    $full_name,
    $email,
    $phone,
    $event_date,
    $event_time,
    $event_location,
    $event_type,
    $guest_count,
    $package_interest,
    $message
);

if ($stmt->execute()) {
    $inquiry_id = $stmt->insert_id;
    $stmt->close();

    // Save cart items to inquiry_items
    $cartItemsJson = $bookingData['items'] ?? '[]';
    $cartItems = json_decode($cartItemsJson, true);
    if (is_array($cartItems)) {
        foreach ($cartItems as $item) {
            $itemName = $connection->real_escape_string($item['name'] ?? '');
            $itemPrice = (float)($item['price'] ?? 0);
            $itemQuantity = (int)($item['quantity'] ?? 1);
            $itemType = $item['type'] ?? 'item';
            $isPackage = $itemType === 'package' ? 1 : 0;

            // Find menu_item_id or package_id
            $menuItemId = null;
            $packageId = null;
            if ($isPackage) {
                $pkgStmt = $connection->prepare("SELECT id FROM packages WHERE name = ? LIMIT 1");
                $pkgStmt->bind_param('s', $itemName);
                $pkgStmt->execute();
                $pkgResult = $pkgStmt->get_result();
                if ($pkgRow = $pkgResult->fetch_assoc()) {
                    $packageId = $pkgRow['id'];
                }
                $pkgStmt->close();
            } else {
                $menuStmt = $connection->prepare("SELECT id FROM menu_items WHERE name = ? LIMIT 1");
                $menuStmt->bind_param('s', $itemName);
                $menuStmt->execute();
                $menuResult = $menuStmt->get_result();
                if ($menuRow = $menuResult->fetch_assoc()) {
                    $menuItemId = $menuRow['id'];
                }
                $menuStmt->close();
            }

            $itemStmt = $connection->prepare(
                "INSERT INTO inquiry_items (inquiry_id, menu_item_id, is_package, package_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $subtotal = $itemPrice * $itemQuantity;
            $itemStmt->bind_param('iiiiddd', $inquiry_id, $menuItemId, $isPackage, $packageId, $itemQuantity, $itemPrice, $subtotal);
            $itemStmt->execute();
            $itemStmt->close();
        }
    }

    // Clear pending data from session
    unset($_SESSION['pending_booking_data']);

    // Send notification to admin
    try {
        // Get inquiry data for email
        $inquiryData = [
            'id' => $inquiry_id,
            'customer_name' => $full_name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'event_date' => $event_date,
            'event_time' => $event_time,
            'event_location' => $event_location,
            'event_type' => $event_type,
            'guest_count' => $guest_count,
            'total_amount' => floatval($bookingData['total_amount']),
            'special_requests' => $message
        ];
        
        // Send customer confirmation email
        $customerEmailSent = sendCustomerNotification('new_inquiry', $inquiry_id);
        
        error_log("Booking saved successfully. ID: $inquiry_id");
        
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'Booking submitted successfully!',
            'inquiry_id' => $inquiry_id,
            'email_sent' => $customerEmailSent['success'] ?? false
        ]);
        
    } catch (Exception $e) {
        error_log("Email notification error: " . $e->getMessage());
        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'Booking submitted successfully!',
            'inquiry_id' => $inquiry_id,
            'email_sent' => false
        ]);
    }
    
} else {
        error_log("Booking insert failed: " . $stmt->error);
        $stmt->close();
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Failed to save booking. Please try again.']);
    }

} catch (Exception $e) {
    error_log("Booking submission exception: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please try again.']);
}

ob_end_flush();
exit;

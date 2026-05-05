<?php
/**
 * ARC Kitchen Add Booking API
 * Handles new booking submissions with proper field mapping and error handling
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Get database connection
$connection = getDbConnection();
if (!$connection) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// Retrieve POST variables with safe defaults
$customer_name = $_POST['customer_name'] ?? $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['customer_phone'] ?? $_POST['phone'] ?? '';
$event_date = $_POST['event_date'] ?? null;
$event_time = $_POST['event_time'] ?? null;
$event_location = $_POST['event_location'] ?? $_POST['venue_location'] ?? null;
$event_type = $_POST['event_type'] ?? '';
$guest_count = intval($_POST['guest_count'] ?? 0);
$special_requests = $_POST['special_requests'] ?? $_POST['message'] ?? '';
$total_amount = floatval($_POST['total_amount'] ?? 0);
$amount_paid = floatval($_POST['amount_paid'] ?? 0);
$items = $_POST['items'] ?? '[]';

// Validate required fields
if (empty($customer_name) || empty($email) || empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields missing: name, email, and phone are required.']);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

// Convert items to JSON if it's an array
if (is_array($items)) {
    $items_json = json_encode($items);
} else {
    $items_json = $items;
}

// Determine which table to insert into (inquiries for new bookings)
// This API handles direct booking creation (typically from admin or alternative flows)

try {
    // Check if we're updating an existing inquiry or creating a new booking
    $inquiry_id = $_POST['inquiry_id'] ?? null;
    
    if ($inquiry_id) {
        // Update existing inquiry
        $sql = "UPDATE inquiries SET 
            full_name = ?, 
            email = ?, 
            phone = ?, 
            event_date = ?, 
            event_time = ?, 
            event_location = ?, 
            event_type = ?, 
            guest_count = ?, 
            package_interest = ?, 
            message = ?, 
            status = 'approved'
            WHERE id = ?";
        
        $stmt = $connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $connection->error);
        }
        
        $stmt->bind_param(
            'sssssssisss',
            $customer_name,
            $email,
            $phone,
            $event_date,
            $event_time,
            $event_location,
            $event_type,
            $guest_count,
            $items_json,
            $special_requests,
            $inquiry_id
        );
        
    } else {
        // Insert new inquiry/booking
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
            throw new Exception("Prepare failed: " . $connection->error);
        }
        
        $stmt->bind_param(
            'ssssssisss',
            $customer_name,
            $email,
            $phone,
            $event_date,
            $event_time,
            $event_location,
            $event_type,
            $guest_count,
            $items_json,
            $special_requests
        );
    }
    
    if ($stmt->execute()) {
        $insert_id = $inquiry_id ?? $stmt->insert_id;
        $stmt->close();
        
        // Send notification if it's a new inquiry
        if (!$inquiry_id) {
            try {
                // Prepare inquiry data for email
                $inquiryData = [
                    'id' => $insert_id,
                    'customer_name' => $customer_name,
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'event_date' => $event_date,
                    'event_time' => $event_time,
                    'event_location' => $event_location,
                    'event_type' => $event_type,
                    'guest_count' => $guest_count,
                    'total_amount' => $total_amount,
                    'special_requests' => $special_requests
                ];
                
                // Attempt to send notification (non-blocking)
                // This is a best-effort notification
            } catch (Exception $e) {
                error_log("Notification error in add-booking: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Booking saved successfully!',
            'id' => $insert_id
        ]);
        
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Add Booking Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

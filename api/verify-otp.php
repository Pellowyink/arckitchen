<?php
/**
 * ARC Kitchen Email OTP Verification API
 * Validates the OTP entered by the user
 */

// Set timezone to prevent time calculation issues
date_default_timezone_set('Asia/Manila'); // Philippine Time

// Start session and configure for security
session_start();

// Suppress all error display (log to file instead)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clear any accidental output/warnings before sending JSON
ob_start();
header('Content-Type: application/json');

try {
    // CRITICAL: Strip ALL non-digit characters from OTP (spaces, dashes, etc.)
    $user_code = preg_replace('/\D/', '', $_POST['otp_code'] ?? '');
    $customer_email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

    // DEBUG: Log session state for troubleshooting
    error_log("OTP Verify Debug - Session ID: " . session_id());
    error_log("OTP Verify Debug - User Code: [{$user_code}]");
    error_log("OTP Verify Debug - Session OTP: [" . ($_SESSION['email_otp'] ?? 'NOT SET') . "]");
    error_log("OTP Verify Debug - Session Expiry: " . ($_SESSION['otp_expiry'] ?? 'NOT SET') . " | Current time: " . time());

    // 1. Basic validation
    if (empty($user_code) || !$customer_email) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Verification details missing.']);
        exit;
    }

    // 2. Ensure OTP exists and has not expired
    if (!isset($_SESSION['email_otp']) || !isset($_SESSION['otp_expiry'])) {
        error_log("OTP Verify Error: Session variables not set");
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please request a new code.']);
        exit;
    }

    if (time() > $_SESSION['otp_expiry']) {
        // Clear expired OTP data
        unset($_SESSION['email_otp']);
        unset($_SESSION['otp_expiry']);
        unset($_SESSION['otp_attempts']);
        
        error_log("OTP Verify Error: Code expired. Expiry: {$_SESSION['otp_expiry']}, Current: " . time());
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Verification code has expired. Please request a new one.']);
        exit;
    }

    // 3. Prevent Session Hijacking & Brute Force
    if (!isset($_SESSION['otp_email']) || $_SESSION['otp_email'] !== $customer_email) {
        error_log("OTP Verify Error: Email mismatch. Session: " . ($_SESSION['otp_email'] ?? 'NOT SET') . ", User: {$customer_email}");
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Email mismatch. Please request a new code.']);
        exit;
    }

    // Track attempts - safely using null coalescing
    $current_attempts = ($_SESSION['otp_attempts'] ?? 0) + 1;
    $_SESSION['otp_attempts'] = $current_attempts;

    if ($current_attempts > 3) {
        // Overwrite existing OTP to prevent brute-forcing
        unset($_SESSION['email_otp']);
        unset($_SESSION['otp_expiry']);
        unset($_SESSION['otp_attempts']);
        
        ob_clean();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Too many failed attempts. Code invalidated. Please request a new one.'
        ]);
        exit;
    }

    // 4. Match check
    if ($user_code === $_SESSION['email_otp']) {
        // Store remaining attempts BEFORE clearing session
        $remaining_attempts = max(0, 3 - $current_attempts);
        
        // Clear OTP variables immediately on success
        unset($_SESSION['email_otp']);
        unset($_SESSION['otp_expiry']);
        unset($_SESSION['otp_attempts']);
        
        // Set a secure variable state to allow database writing
        $_SESSION['email_verified'] = true;
        $_SESSION['verified_email_address'] = $customer_email;
        
        ob_clean();
        echo json_encode([
            'status' => 'success', 
            'message' => 'Verification successful!',
            'remaining_attempts' => $remaining_attempts
        ]);
    } else {
        $remaining = max(0, 3 - $current_attempts);
        ob_clean();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Incorrect verification code. Please try again.',
            'remaining_attempts' => $remaining
        ]);
    }

} catch (Exception $e) {
    error_log("OTP Verify Exception: " . $e->getMessage());
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error. Please try again.'
    ]);
}

ob_end_flush();
exit;

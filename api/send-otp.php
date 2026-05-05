<?php
/**
 * ARC Kitchen Email OTP Generation & Sending API
 * Generates a secure 6-digit OTP and sends it via PHPMailer
 * Includes SMTP timeout safeguards and localhost bypass for testing
 */

// Set timezone to prevent time calculation issues
date_default_timezone_set('Asia/Manila'); // Philippine Time

// Ensure session persists across AJAX calls
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Enable error reporting temporarily for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

session_start();

// Clear any accidental output/warnings before sending JSON
ob_start();
header('Content-Type: application/json');

try {
    // DEBUG: Log session info
    error_log("OTP Send Debug - Session ID: " . session_id());
    error_log("OTP Send Debug - Session Data: " . print_r($_SESSION, true));

    require_once __DIR__ . '/../includes/mailer_init.php';

    // 1. Cooldown Guard - Prevent spam
$cooldown_time = 60; // seconds
if (isset($_SESSION['otp_last_sent']) && (time() - $_SESSION['otp_last_sent']) < $cooldown_time) {
    $seconds_left = $cooldown_time - (time() - $_SESSION['otp_last_sent']);
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => "Please wait {$seconds_left}s before requesting a new code."
    ]);
    exit;
}

// 2. Validate Email
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

// 3. Generate Secure 6-Digit OTP
$otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

// 4. Save Session State
$_SESSION['email_otp'] = $otp;
$_SESSION['otp_email'] = $email;
$_SESSION['otp_expiry'] = time() + 300; // 5-minute validity window
$_SESSION['otp_last_sent'] = time();
$_SESSION['otp_attempts'] = 0; // Track brute-force attempts

// Store form data temporarily for later submission
$_SESSION['pending_booking_data'] = [
    'customer_name' => $_POST['customer_name'] ?? '',
    'customer_email' => $email,
    'customer_phone' => $_POST['customer_phone'] ?? '',
    'event_type' => $_POST['event_type'] ?? '',
    'guest_count' => $_POST['guest_count'] ?? '',
    'event_date' => $_POST['event_date'] ?? '',
    'event_time' => $_POST['event_time'] ?? '',
    'event_location' => $_POST['event_location'] ?? '',
    'special_requests' => $_POST['special_requests'] ?? '',
    'items' => $_POST['items'] ?? '[]',
    'total_amount' => $_POST['total_amount'] ?? '0'
];

// 5. LOCALHOST BYPASS MODE (for testing without SMTP)
// Detect if running on localhost/127.0.0.1 and bypass email sending
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || 
               strpos($_SERVER['SERVER_ADDR'] ?? '', '127.0.0.1') !== false ||
               $_SERVER['SERVER_NAME'] === 'localhost';

if ($isLocalhost && defined('OTP_LOCALHOST_BYPASS') && OTP_LOCALHOST_BYPASS) {
    // Log OTP to file for testing instead of sending email
    $logFile = __DIR__ . '/../logs/otp_test.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Email: {$email}, OTP: {$otp}" . PHP_EOL, FILE_APPEND);
    
    ob_clean();
    echo json_encode([
        'status' => 'success', 
        'message' => 'DEV MODE: OTP is ' . $otp . ' (check logs/otp_test.log)',
        'cooldown' => $cooldown_time,
        'dev_mode' => true,
        'otp' => $otp // Only shown in dev mode
    ]);
    exit;
}

// 6. Send Email via PHPMailer
$mail = initializeArcMailer();

if (!$mail) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Failed to initialize mailer. Please check PHPMailer installation.']);
    exit;
}

// CRITICAL: Set SMTP timeout to prevent hanging (default is 5 minutes!)
$mail->Timeout = 10; // 10 seconds max
$mail->SMTPKeepAlive = false;

// Sender information - MUST match authenticated Gmail address
$mail->setFrom('dailyjunkie173@gmail.com', 'Arc Kitchen');
$mail->addReplyTo('dailyjunkie173@gmail.com', 'Arc Kitchen');

// Encoding
$mail->CharSet = 'UTF-8';
$mail->isHTML(true);

$mail->addAddress($email);
$mail->isHTML(true);
$mail->Subject = "Your ARC Kitchen Verification Code";

// Brand-consistent HTML template
$mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARC Kitchen Verification</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap');

        body {
            margin: 0;
            padding: 0;
            background-color: #fdf8f3;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .email-wrapper {
            max-width: 500px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(74, 20, 20, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #4a1414 0%, #6c1d12 100%);
            padding: 40px 30px;
            text-align: center;
        }

        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-family: 'League Spartan', sans-serif;
            font-size: 24px;
            font-weight: 700;
        }

        .email-body {
            padding: 40px 30px;
            background: #ffffff;
            text-align: center;
        }

        .email-body p {
            color: #5c4a42;
            font-size: 16px;
            line-height: 1.7;
            margin: 0 0 24px 0;
        }

        .otp-box {
            background: linear-gradient(135deg, #4a1414 0%, #6c1d12 100%);
            color: #ffffff;
            font-size: 36px;
            font-weight: 700;
            font-family: 'League Spartan', sans-serif;
            padding: 20px 30px;
            border-radius: 15px;
            letter-spacing: 8px;
            margin: 30px 0;
            display: inline-block;
        }

        .expiry-note {
            color: #8a6d5b;
            font-size: 14px;
            margin-top: 20px;
        }

        .email-footer {
            background: #faf6f1;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #f0e6dc;
        }

        .email-footer p {
            color: #8a6d5b;
            font-size: 13px;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>🔐 ARC Kitchen Verification</h1>
        </div>
        <div class="email-body">
            <p>Use the code below to finalize your booking inquiry.<br>This code is valid for <strong>5 minutes</strong>:</p>
            <div class="otp-box">{$otp}</div>
            <p class="expiry-note">If you did not request this, please ignore this email.</p>
        </div>
        <div class="email-footer">
            <p>Arc Kitchen Catering | Premium Catering Services</p>
        </div>
    </div>
</body>
</html>
HTML;

    // Wrap PHPMailer sending in try-catch
    try {
        $mail->send();
        // Clear recipient for next use
        $mail->clearAddresses();

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'message' => 'Verification code sent to your email!',
            'cooldown' => $cooldown_time
        ]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Mail delivery failed: ' . $mail->ErrorInfo
        ]);
    }

} catch (Throwable $e) { // FIX: Changed Exception to Throwable to catch fatal 500 crashes
    error_log("OTP Send Fatal Error: " . $e->getMessage() . " on line " . $e->getLine());
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}

ob_end_flush();
exit;

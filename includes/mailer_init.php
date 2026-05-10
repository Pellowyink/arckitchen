<?php
/**
 * Arc Kitchen PHPMailer Initialization
 * Centralized SMTP configuration for automated email notifications
 * 
 * SETUP INSTRUCTIONS:
 * 1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
 * 2. Extract to: d:\xampp\htdocs\arckitchen\includes\PHPMailer\
 * 3. Or install via Composer: composer require phpmailer/phpmailer
 */

// DEV MODE: Set to true to bypass SMTP on localhost and log OTP to file instead
// This prevents email hanging during local development
if (!defined('OTP_LOCALHOST_BYPASS')) {
    define('OTP_LOCALHOST_BYPASS', false); // Set to TRUE for testing without SMTP
}

// Option 1: Using Composer (vendor/autoload.php)
// require_once __DIR__ . '/../vendor/autoload.php';

// Option 2: Manual PHPMailer installation (download from GitHub)
$phpmailerPath = __DIR__ . '/PHPMailer/src/';
if (file_exists($phpmailerPath . 'PHPMailer.php')) {
    require_once $phpmailerPath . 'PHPMailer.php';
    require_once $phpmailerPath . 'SMTP.php';
    require_once $phpmailerPath . 'Exception.php';
} else {
    // PHPMailer not installed - define stub functions to prevent errors
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Return null mailer which will gracefully fail
        function initializeArcMailer(): ?object {
            error_log("PHPMailer not installed. Please download from https://github.com/PHPMailer/PHPMailer");
            return null;
        }
        
        function generateEmailWrapper(string $title, string $content): string {
            return $content;
        }
        
        // Skip rest of file if PHPMailer not available
        return;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Initialize and configure PHPMailer with Arc Kitchen SMTP settings
 * @return PHPMailer|null Configured PHPMailer instance or null on failure
 */
function initializeArcMailer(): ?PHPMailer {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Change to your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'dailyjunkie173@gmail.com';  // Change to your email
        $mail->Password   = 'ljza eclt mypn uthk';  // Change to your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // CRITICAL: Set timeout to prevent hanging (default is 5 minutes!)
        $mail->Timeout    = 10;  // 10 seconds connection timeout
        $mail->SMTPKeepAlive = false;
        
        // Sender information - MUST match authenticated Gmail address
        $mail->setFrom('dailyjunkie173@gmail.com', 'Arc Kitchen');
        $mail->addReplyTo('dailyjunkie173@gmail.com', 'Arc Kitchen');
        
        // Encoding
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        
        return $mail;
        
    } catch (Exception $e) {
        error_log("Arc Kitchen Mailer Init Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate Arc Kitchen branded email HTML wrapper
 * @param string $title Email title
 * @param string $content Main content HTML
 * @return string Complete HTML email body
 */
function getArcEmailTemplate(string $title, string $content): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap');
        
        body {
            margin: 0;
            padding: 0;
            background-color: #fdf8f3;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        
        .email-wrapper {
            max-width: 600px;
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
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .email-header .logo-text {
            color: #f5e6d3;
            font-size: 14px;
            margin-top: 8px;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .email-body {
            padding: 40px 30px;
            background: #ffffff;
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
            margin: 0 0 8px 0;
        }
        
        .email-footer .brand {
            color: #4a1414;
            font-weight: 600;
            font-family: 'League Spartan', sans-serif;
        }
        
        h2 {
            color: #4a1414;
            font-family: 'League Spartan', sans-serif;
            font-size: 22px;
            margin: 0 0 20px 0;
            font-weight: 600;
        }
        
        p {
            color: #5c4a42;
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 16px 0;
        }
        
        .info-box {
            background: #faf6f1;
            border-left: 4px solid #8a2927;
            padding: 20px;
            margin: 24px 0;
            border-radius: 0 10px 10px 0;
        }
        
        .info-box strong {
            color: #4a1414;
            display: block;
            margin-bottom: 8px;
            font-family: 'League Spartan', sans-serif;
        }
        
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 14px;
        }
        
        .order-table th {
            background: #4a1414;
            color: #ffffff;
            padding: 14px 12px;
            text-align: left;
            font-family: 'League Spartan', sans-serif;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .order-table th:first-child {
            border-radius: 10px 0 0 0;
        }
        
        .order-table th:last-child {
            border-radius: 0 10px 0 0;
            text-align: right;
        }
        
        .order-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f0e6dc;
            color: #5c4a42;
        }
        
        .order-table td:last-child {
            text-align: right;
            font-weight: 600;
            color: #4a1414;
        }
        
        .order-table tr:last-child td {
            border-bottom: none;
        }
        
        .order-table tr:nth-child(even) {
            background: #fdfbf9;
        }
        
        .total-row {
            background: #4a1414 !important;
            color: #ffffff !important;
        }
        
        .total-row td {
            color: #ffffff !important;
            font-weight: 700;
            font-family: 'League Spartan', sans-serif;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'League Spartan', sans-serif;
        }
        
        .status-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-inprogress {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .status-ready {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .status-complete {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .eta-box {
            background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
            color: #ffffff;
            padding: 24px;
            border-radius: 15px;
            text-align: center;
            margin: 24px 0;
        }
        
        .eta-box h3 {
            margin: 0 0 8px 0;
            font-family: 'League Spartan', sans-serif;
            font-size: 16px;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .eta-box .time {
            font-size: 32px;
            font-weight: 700;
            font-family: 'League Spartan', sans-serif;
            margin: 0;
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e0d5cc, transparent);
            margin: 30px 0;
        }

        /* Payment Summary Styles */
        .payment-summary {
            margin: 24px 0;
        }

        .payment-box {
            background: #fff8f0;
            border: 2px solid #d5a437;
            border-radius: 15px;
            padding: 24px;
            margin: 16px 0;
        }

        .payment-box.full-payment {
            border-color: #4caf50;
            background: #f0f8f0;
        }

        .payment-box.downpayment {
            border-color: #ff9800;
            background: #fff8e1;
        }

        .payment-box.pending-payment {
            border-color: #f44336;
            background: #ffebee;
        }

        .payment-box h3 {
            margin: 0 0 16px 0;
            color: #4a1414;
            font-family: 'League Spartan', sans-serif;
            font-size: 18px;
            font-weight: 600;
        }

        .payment-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0e6dc;
        }

        .payment-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 16px;
            color: #4a1414;
        }

        .payment-row.remaining-balance {
            color: #f44336;
        }

        .payment-row.balance-zero {
            color: #4caf50;
        }

        .payment-note {
            margin: 16px 0 0 0;
            font-size: 14px;
            color: #8a6d5b;
            font-style: italic;
            text-align: center;
        }

        @media (max-width: 600px) {
            .email-wrapper {
                margin: 0;
                border-radius: 0;
            }
            
            .email-body {
                padding: 24px 20px;
            }
            
            .order-table {
                font-size: 13px;
            }
            
            .order-table th,
            .order-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>🍽️ Arc Kitchen</h1>
            <div class="logo-text">Premium Catering Services</div>
        </div>
        <div class="email-body">
            {$content}
        </div>
        <div class="email-footer">
            <p class="brand">Arc Kitchen Catering</p>
            <p>Thank you for choosing us for your special event!</p>
            <p style="margin-top: 16px; font-size: 12px; color: #a89080;">
                This is an automated message. Please do not reply to this email.<br>
                For inquiries, contact us at arckitchen.catering@gmail.com
            </p>
        </div>
    </div>
</body>
</html>
HTML;
}

<?php
// Direct test of payment function
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain');

echo "=== Testing approveInquiryWithPayment ===\n\n";

// Test with inquiry ID 1 (change this to an actual pending inquiry ID)
$testInquiryId = 1;
$downPayment = 2500;
$fullPayment = 0;
$totalAmount = 5994;

echo "Testing with:\n";
echo "- Inquiry ID: $testInquiryId\n";
echo "- Down Payment: $downPayment\n";
echo "- Full Payment: $fullPayment\n";
echo "- Total Amount: $totalAmount\n\n";

echo "Result:\n";

// Capture output and errors
ob_start();
$result = approveInquiryWithPayment($testInquiryId, $downPayment, $fullPayment, $totalAmount);
$output = ob_get_clean();

if ($result) {
    echo "✓ SUCCESS! Payment was saved and inquiry approved.\n";
} else {
    echo "✗ FAILED - function returned false\n";
}

if ($output) {
    echo "\nOutput captured:\n$output\n";
}

echo "\nDone.\n";

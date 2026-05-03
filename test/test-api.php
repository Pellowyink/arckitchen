<?php
// Test the API directly
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing API Components ===\n\n";

// Test 1: Database connection
echo "1. Database connection:\n";
$conn = getDbConnection();
if ($conn) {
    echo "   ✓ Connected\n\n";
} else {
    echo "   ✗ FAILED - Could not connect\n\n";
    exit;
}

// Test 2: Check if approveInquiryWithPayment function exists
echo "2. Function exists:\n";
if (function_exists('approveInquiryWithPayment')) {
    echo "   ✓ approveInquiryWithPayment exists\n\n";
} else {
    echo "   ✗ FAILED - Function not found\n\n";
}

// Test 3: Check inquiries
echo "3. Pending inquiries:\n";
$result = $conn->query("SELECT id, full_name, status FROM inquiries WHERE status = 'pending' LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "   - ID {$row['id']}: {$row['full_name']} (status: {$row['status']})\n";
    }
} else {
    echo "   No pending inquiries found\n";
}

echo "\n=== Test Complete ===\n";

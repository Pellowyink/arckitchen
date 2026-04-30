<?php
// Database test script - run this to check if payment columns exist
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

echo "=== ARC Kitchen Database Check ===\n\n";

$conn = getDbConnection();
if (!$conn) {
    echo "ERROR: Could not connect to database\n";
    exit;
}

echo "✓ Database connection OK\n\n";

// Check inquiries table
echo "=== Checking 'inquiries' table ===\n";
$result = $conn->query("DESCRIBE inquiries");
if ($result) {
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $required = ['down_payment', 'full_payment', 'payment_status', 'total_amount'];
    foreach ($required as $col) {
        if (in_array($col, $columns)) {
            echo "✓ Column '$col' exists\n";
        } else {
            echo "✗ MISSING: Column '$col' does NOT exist\n";
        }
    }
} else {
    echo "ERROR: Could not describe inquiries table: " . $conn->error . "\n";
}

echo "\n=== Checking 'bookings' table ===\n";
$result = $conn->query("DESCRIBE bookings");
if ($result) {
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $required = ['down_payment', 'full_payment', 'payment_status', 'total_amount'];
    foreach ($required as $col) {
        if (in_array($col, $columns)) {
            echo "✓ Column '$col' exists\n";
        } else {
            echo "✗ MISSING: Column '$col' does NOT exist\n";
        }
    }
} else {
    echo "ERROR: Could not describe bookings table: " . $conn->error . "\n";
}

echo "\n=== SQL to fix missing columns ===\n";
echo "ALTER TABLE inquiries 
  ADD COLUMN down_payment DECIMAL(12,2) DEFAULT 0.00,
  ADD COLUMN full_payment DECIMAL(12,2) DEFAULT 0.00,
  ADD COLUMN payment_status ENUM('pending', 'partial', 'fully_paid') DEFAULT 'pending',
  ADD COLUMN total_amount DECIMAL(12,2) DEFAULT 0.00;

ALTER TABLE bookings
  ADD COLUMN down_payment DECIMAL(12,2) DEFAULT 0.00,
  ADD COLUMN full_payment DECIMAL(12,2) DEFAULT 0.00,
  ADD COLUMN payment_status ENUM('pending', 'partial', 'fully_paid') DEFAULT 'pending';\n";

echo "\n=== Test Complete ===\n";

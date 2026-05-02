<?php
/**
 * Database Migration: Add ETA column to inquiries table
 * Run this script to add the eta column needed for the email notification system
 */

require_once __DIR__ . '/../includes/db.php';

$connection = getDbConnection();

if (!$connection) {
    die("Failed to connect to database\n");
}

echo "Checking if ETA column exists...\n";

// Check if eta column exists
$result = $connection->query("SHOW COLUMNS FROM inquiries LIKE 'eta'");

if ($result->num_rows > 0) {
    echo "✓ ETA column already exists\n";
} else {
    echo "Adding ETA column to inquiries table...\n";
    
    $sql = "ALTER TABLE inquiries ADD COLUMN eta VARCHAR(255) NULL AFTER status";
    
    if ($connection->query($sql)) {
        echo "✓ ETA column added successfully\n";
    } else {
        echo "✗ Error adding ETA column: " . $connection->error . "\n";
    }
}

echo "\nMigration complete!\n";

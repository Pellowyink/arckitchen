<?php
/**
 * Database Migration: Add archived_at column to inquiries table
 * Run this script to add the archived_at column needed for inquiry filtering
 */

require_once __DIR__ . '/../includes/db.php';

$connection = getDbConnection();

if (!$connection) {
    die("Failed to connect to database\n");
}

echo "Checking if archived_at column exists in inquiries table...\n";

// Check if archived_at column exists
$result = $connection->query("SHOW COLUMNS FROM inquiries LIKE 'archived_at'");

if ($result->num_rows > 0) {
    echo "✓ archived_at column already exists\n";
} else {
    echo "Adding archived_at column to inquiries table...\n";
    
    $sql = "ALTER TABLE inquiries ADD COLUMN archived_at DATETIME NULL AFTER status";
    
    if ($connection->query($sql)) {
        echo "✓ archived_at column added successfully\n";
    } else {
        echo "✗ Error adding archived_at column: " . $connection->error . "\n";
    }
}

echo "\nMigration complete!\n";

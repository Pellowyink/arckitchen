<?php

// Database configuration using PDO
$dbHost = 'localhost';
$dbName = 'arc_kitchen';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Enable prepared statement emulation (optional)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log error and exit gracefully
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

/**
 * Get PDO database connection
 * @return PDO
 */
function getDbConnection(): PDO
{
    global $pdo;
    return $pdo;
}

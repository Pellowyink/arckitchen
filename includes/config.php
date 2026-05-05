<?php
/**
 * ARC Kitchen Configuration File
 * Centralizes all configuration settings and database connection
 */

// Include database configuration
require_once __DIR__ . '/db.php';

// Define base paths for the application
if (!defined('ARC_ROOT')) {
    define('ARC_ROOT', dirname(__DIR__));
}

if (!defined('ARC_INCLUDES')) {
    define('ARC_INCLUDES', ARC_ROOT . '/includes');
}

if (!defined('ARC_API')) {
    define('ARC_API', ARC_ROOT . '/api');
}

// Application settings
if (!defined('APP_NAME')) {
    define('APP_NAME', 'ARC Kitchen');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}

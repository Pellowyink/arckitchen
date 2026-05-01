<?php
/**
 * Admin Index - Redirects to login page
 */

require_once __DIR__ . '/../includes/functions.php';

// If not logged in, go to login
if (empty($_SESSION['admin_id'])) {
    redirect('login.php');
}

// If logged in, go to dashboard
redirect('dashboard.php');

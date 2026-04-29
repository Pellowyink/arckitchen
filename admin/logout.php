<?php

require_once __DIR__ . '/../includes/functions.php';

// Only allow logout if user is logged in
requireAdmin();

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page
redirect('login.php');


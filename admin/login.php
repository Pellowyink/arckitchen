<?php

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = null;

// Process login form submission
if (isPostRequest()) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate required fields
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (loginAdmin($username, $password)) {
        // Successful login
        redirect('dashboard.php');
    } else {
        // Failed login attempt
        $error = 'Invalid login credentials. Please check and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARC Kitchen Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-card reveal is-visible">
            <!-- Brand Header -->
            <div class="auth-header">
                <div class="brand auth-brand">
                    <span class="brand-mark" aria-hidden="true">
                        <img src="../assets/images/arc-logo.png" alt="ARC Kitchen logo" class="brand-logo-image">
                    </span>
                </div>
                <h1>ARC Kitchen</h1>
                <p class="auth-subtitle">Admin Panel — Authorized Access Only</p>
            </div>

            <!-- Error Message Display -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Login Failed:</strong> <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="post" class="auth-form" data-validate>
                <!-- Username Field -->
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        id="username" 
                        name="username" 
                        type="text" 
                        class="form-input"
                        placeholder="Enter your username"
                        required
                        autocomplete="username"
                    >
                </div>

                <!-- Password Field with Eye Icon -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            class="form-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            class="password-toggle" 
                            aria-label="Toggle password visibility"
                            data-password-toggle
                        >
                            <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-button">Login to Admin Panel</button>
            </form>

            <!-- Back Link -->
            <div class="auth-footer">
                <a href="../home.php" class="auth-link">← Back to ARC Kitchen Website</a>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>


<?php

require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = null;

if (isPostRequest()) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (loginAdmin($username, $password)) {
        redirect('dashboard.php');
    }

    $error = 'Invalid login credentials or database not ready yet.';
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
            <div class="brand auth-brand">
                <span class="brand-mark" aria-hidden="true">
                    <img src="../assets/images/arc-logo.png" alt="ARC Kitchen logo" class="brand-logo-image">
                </span>
                <span>ARC Kitchen</span>
            </div>
            <span class="eyebrow">Admin Panel</span>
            <h1>Sign in to ARC Kitchen</h1>
            <p class="lead">Use the default seeded account after importing the SQL file.</p>

            <?php if ($error): ?>
                <div class="flash error"><?php echo escape($error); ?></div>
            <?php endif; ?>

            <form method="post" data-validate>
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" required>
                </div>
                <div class="field spacer-top-md">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <div class="stack-inline">
                    <button type="submit" class="button">Login</button>
                    <a href="../home.php" class="button button-outline">Back to Website</a>
                </div>
            </form>
            <p class="helper-text spacer-top-md">Default account: <strong>admin</strong> / <strong>admin123</strong></p>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>


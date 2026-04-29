<?php
/**
 * ARC Kitchen Admin Account Setup Script
 * 
 * This script allows you to:
 * 1. Create new admin accounts with secure password hashing
 * 2. Update existing admin passwords
 * 3. View all admin accounts
 * 
 * SECURITY WARNING: Delete this file after creating all admin accounts!
 * This script should NOT be left accessible on a production server.
 * 
 * Usage: Navigate to http://localhost/arckitchen/admin/setup_admin.php
 */

require_once __DIR__ . '/../includes/db.php';

// Simple security check - verify this is only accessible locally
$allowed_hosts = ['127.0.0.1', 'localhost', '::1'];
$is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_hosts);

if (!$is_local) {
    die('<h1>Access Denied</h1><p>This setup script can only be accessed from localhost. For security reasons, it is disabled on remote connections.</p>');
}

$message = null;
$message_type = null;
$all_admins = [];

$connection = getDbConnection();

// Fetch all admin users for display
if ($connection) {
    $result = $connection->query("SELECT id, username, full_name, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
    if ($result) {
        $all_admins = $result->fetch_all(MYSQLI_ASSOC) ?: [];
        $result->free();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');

        // Validation
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, dots, hyphens, and underscores.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }

        if (empty($errors)) {
            if ($connection) {
                // Check if username already exists
                $check = $connection->prepare("SELECT id FROM users WHERE username = ?");
                $check->bind_param('s', $username);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;
                $check->close();

                if ($exists) {
                    $message = 'Username already exists. Please choose a different username.';
                    $message_type = 'error';
                } else {
                    // Hash password using bcrypt (PASSWORD_DEFAULT)
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    // Insert new admin user
                    $stmt = $connection->prepare(
                        "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')"
                    );
                    $stmt->bind_param('sss', $username, $password_hash, $full_name);

                    if ($stmt->execute()) {
                        $message = "✅ Admin account created successfully!<br><strong>Username:</strong> {$username}<br><strong>Full Name:</strong> {$full_name}";
                        $message_type = 'success';
                        
                        // Refresh admin list
                        $result = $connection->query("SELECT id, username, full_name, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
                        $all_admins = $result->fetch_all(MYSQLI_ASSOC) ?: [];
                        $result->free();
                    } else {
                        $message = 'Error creating admin account: ' . $stmt->error;
                        $message_type = 'error';
                    }
                    $stmt->close();
                }
            } else {
                $message = 'Database connection failed.';
                $message_type = 'error';
            }
        } else {
            $message = 'Validation errors:<br>' . implode('<br>', array_map(fn($e) => '• ' . $e, $errors));
            $message_type = 'error';
        }
    } elseif ($action === 'reset_password') {
        $admin_id = (int) ($_POST['admin_id'] ?? 0);
        $new_password = trim($_POST['new_password'] ?? '');

        $errors = [];

        if ($admin_id <= 0) {
            $errors[] = 'Invalid admin ID.';
        }

        if (empty($new_password)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if (empty($errors) && $connection) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $connection->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'admin'");
            $stmt->bind_param('si', $password_hash, $admin_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = '✅ Password updated successfully!';
                    $message_type = 'success';
                    
                    // Refresh admin list
                    $result = $connection->query("SELECT id, username, full_name, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
                    $all_admins = $result->fetch_all(MYSQLI_ASSOC) ?: [];
                    $result->free();
                } else {
                    $message = 'Admin not found.';
                    $message_type = 'error';
                }
            } else {
                $message = 'Error updating password: ' . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } elseif (!empty($errors)) {
            $message = 'Validation errors:<br>' . implode('<br>', array_map(fn($e) => '• ' . $e, $errors));
            $message_type = 'error';
        }
    } elseif ($action === 'delete') {
        $admin_id = (int) ($_POST['admin_id'] ?? 0);

        if ($admin_id <= 0) {
            $message = 'Invalid admin ID.';
            $message_type = 'error';
        } elseif ($connection) {
            // Prevent deleting the last admin
            $count = $connection->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
            $row = $count->fetch_assoc();
            
            if ($row['total'] <= 1) {
                $message = '⚠️ Cannot delete the last admin account. Create another admin account first.';
                $message_type = 'error';
            } else {
                $stmt = $connection->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
                $stmt->bind_param('i', $admin_id);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = '✅ Admin account deleted successfully!';
                        $message_type = 'success';
                        
                        // Refresh admin list
                        $result = $connection->query("SELECT id, username, full_name, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
                        $all_admins = $result->fetch_all(MYSQLI_ASSOC) ?: [];
                        $result->free();
                    } else {
                        $message = 'Admin not found.';
                        $message_type = 'error';
                    }
                } else {
                    $message = 'Error deleting admin: ' . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARC Kitchen Admin Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            padding: 2rem 1rem;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #6c1d12 0%, #8a2927 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .warning strong {
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .form-section h2 {
            color: #6c1d12;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #8a2927;
            box-shadow: 0 0 0 3px rgba(138, 41, 39, 0.1);
        }
        
        input::placeholder {
            color: #999;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .btn-primary {
            background: #8a2927;
            color: white;
        }
        
        .btn-primary:hover {
            background: #7a2420;
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .admins-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .admins-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        
        .admins-table td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
        }
        
        .admins-table tr:hover {
            background: #f8f9fa;
        }
        
        .admin-row-form {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .admin-row-form input {
            flex: 1;
            padding: 0.5rem;
            font-size: 0.85rem;
        }
        
        .code {
            background: #f4f4f4;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
        }
        
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔐 ARC Kitchen Admin Setup</h1>
            <p>Manage admin accounts and passwords securely</p>
        </div>

        <!-- Security Warning -->
        <div class="warning">
            <div>
                <strong>⚠️ SECURITY WARNING</strong>
                <p>This setup page should only be accessed from localhost. <strong>Delete this file (setup_admin.php) immediately after setting up all admin accounts.</strong> Leaving it accessible is a serious security risk.</p>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Create New Admin Section -->
        <div class="form-section">
            <h2>➕ Create New Admin Account</h2>
            <form method="post">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        placeholder="e.g., john_doe"
                        required
                    >
                    <p class="help-text">Alphanumeric, dots, hyphens, and underscores. Min 3 characters.</p>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        placeholder="e.g., John Doe"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Minimum 8 characters"
                        required
                    >
                    <p class="help-text">Use a strong password with mixed case, numbers, and symbols.</p>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary">Create Admin Account</button>
                    <button type="reset" class="btn-secondary">Clear Form</button>
                </div>
            </form>
        </div>

        <!-- Manage Admin Accounts Section -->
        <?php if (!empty($all_admins)): ?>
        <div class="form-section">
            <h2>👥 Manage Admin Accounts (<?php echo count($all_admins); ?>)</h2>
            
            <table class="admins-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_admins as $admin): ?>
                    <tr>
                        <td><code class="code"><?php echo htmlspecialchars($admin['username']); ?></code></td>
                        <td><?php echo htmlspecialchars($admin['full_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                        <td>
                            <!-- Reset Password Form -->
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <input 
                                    type="password" 
                                    name="new_password" 
                                    placeholder="New password"
                                    style="width: 150px; display: inline-block; margin-right: 0.5rem;"
                                    required
                                >
                                <button type="submit" class="btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">Reset</button>
                            </form>
                            
                            <!-- Delete Form -->
                            <?php if (count($all_admins) > 1): ?>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this admin account?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <button type="submit" class="btn-danger">Delete</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Info Section -->
        <div class="form-section">
            <h2>ℹ️ Setup Information</h2>
            
            <h3 style="color: #333; margin-top: 1rem; margin-bottom: 0.75rem;">Default Admin Account</h3>
            <p>The following account was created during initial database setup:</p>
            <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                <li><strong>Username:</strong> <code class="code">admin</code></li>
                <li><strong>Password:</strong> <code class="code">admin123</code></li>
            </ul>
            <p style="color: #dc3545; font-weight: 600; margin-top: 1rem;">⚠️ Change this password immediately!</p>

            <h3 style="color: #333; margin-top: 1.5rem; margin-bottom: 0.75rem;">Password Security</h3>
            <p>All passwords are hashed using <strong>bcrypt</strong> (PHP's PASSWORD_DEFAULT), which is:</p>
            <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                <li>✅ Industry standard secure hashing</li>
                <li>✅ Resistant to GPU attacks (high computation cost)</li>
                <li>✅ Automatically salted</li>
                <li>✅ Future-proof (PHP will upgrade algorithm as needed)</li>
            </ul>

            <h3 style="color: #333; margin-top: 1.5rem; margin-bottom: 0.75rem;">When to Delete This File</h3>
            <p>Delete <code class="code">setup_admin.php</code> after:</p>
            <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                <li>✅ Creating all necessary admin accounts</li>
                <li>✅ Testing that logins work correctly</li>
                <li>✅ Changing the default admin password</li>
                <li>✅ Moving to production</li>
            </ul>

            <h3 style="color: #333; margin-top: 1.5rem; margin-bottom: 0.75rem;">Login Page</h3>
            <p>Your admin login is at: <code class="code"><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>/arckitchen/admin/login.php</code></p>
        </div>
    </div>
</body>
</html>
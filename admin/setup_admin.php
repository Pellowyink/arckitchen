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
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

/**
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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">⚙️ Admin Setup</h1>
            </div>

            <!-- Security Warning -->
            <div class="admin-card" style="border-left: 4px solid #f0a500; background: #fffbf0;">
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <span style="font-size: 1.5rem; line-height: 1;">⚠️</span>
                    <div>
                        <p style="font-weight: 700; color: #7a5200; margin-bottom: 0.35rem;">Security Warning</p>
                        <p style="color: #7a5200; font-size: 0.875rem; line-height: 1.6;">
                            This setup page is only accessible from localhost. 
                            <strong>Delete <code style="background: rgba(0,0,0,0.07); padding: 0.1rem 0.35rem; border-radius: 4px;">setup_admin.php</code> immediately after setting up all admin accounts.</strong>
                            Leaving it accessible is a serious security risk.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Alert Message -->
            <?php if ($message): ?>
            <div class="admin-card" style="border-left: 4px solid <?php echo $message_type === 'success' ? '#22a355' : '#dc3545'; ?>; background: <?php echo $message_type === 'success' ? '#f0faf4' : '#fff5f5'; ?>;">
                <p style="color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>; font-size: 0.9rem; line-height: 1.6;">
                    <?php echo $message; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Two-column: Create + Manage -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">

                <!-- Create New Admin -->
                <div class="admin-card">
                    <h2>➕ Create New Admin Account</h2>
                    <form method="post" data-validate>
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-input" placeholder="e.g., john_doe" required>
                            <p style="font-size: 0.78rem; color: var(--text-soft); margin-top: 0.35rem;">Alphanumeric, dots, hyphens, underscores. Min 3 characters.</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="form-input" placeholder="e.g., John Doe" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-input" placeholder="Minimum 8 characters" required>
                            <p style="font-size: 0.78rem; color: var(--text-soft); margin-top: 0.35rem;">Use a strong password with mixed case, numbers, and symbols.</p>
                        </div>
                        <div style="display: flex; gap: 0.75rem; margin-top: 1.25rem;">
                            <button type="submit" class="btn-admin btn-primary-admin" style="flex: 1;">Create Account</button>
                            <button type="reset" class="btn-admin btn-secondary-admin">Clear</button>
                        </div>
                    </form>
                </div>

                <!-- Info Panel -->
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <!-- Default Credentials -->
                    <div class="admin-card" style="border-left: 4px solid #dc3545;">
                        <h2 style="color: #dc3545;">🔑 Default Credentials</h2>
                        <p style="font-size: 0.875rem; color: var(--text-soft); margin-bottom: 0.75rem;">Created during initial database setup:</p>
                        <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 0.4rem 0; color: var(--text-soft); width: 35%;">Username</td>
                                <td><code style="background: rgba(53,21,15,0.07); padding: 0.2rem 0.5rem; border-radius: 5px; font-size: 0.82rem;">admin</code></td>
                            </tr>
                            <tr>
                                <td style="padding: 0.4rem 0; color: var(--text-soft);">Password</td>
                                <td><code style="background: rgba(53,21,15,0.07); padding: 0.2rem 0.5rem; border-radius: 5px; font-size: 0.82rem;">admin123</code></td>
                            </tr>
                        </table>
                        <p style="font-size: 0.8rem; color: #dc3545; font-weight: 700; margin-top: 0.75rem;">⚠️ Change this password immediately!</p>
                    </div>

                    <!-- Security Info -->
                    <div class="admin-card">
                        <h2>🔒 Password Security</h2>
                        <p style="font-size: 0.875rem; color: var(--text-soft); margin-bottom: 0.75rem;">
                            Passwords use <strong>bcrypt</strong> (PHP PASSWORD_DEFAULT):
                        </p>
                        <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 0.4rem;">
                            <?php foreach ([
                                'Industry standard secure hashing',
                                'Resistant to GPU attacks',
                                'Automatically salted',
                                'Future-proof algorithm upgrades',
                            ] as $point): ?>
                            <li style="font-size: 0.82rem; color: var(--text-soft); display: flex; gap: 0.5rem; align-items: center;">
                                <span style="color: #22a355; font-size: 0.9rem;">✅</span> <?php echo $point; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- When to delete -->
                    <div class="admin-card">
                        <h2>🗑️ When to Delete This File</h2>
                        <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem;">
                            <?php foreach ([
                                'All admin accounts created',
                                'Logins tested and working',
                                'Default password changed',
                                'Moving to production',
                            ] as $step): ?>
                            <li style="font-size: 0.82rem; color: var(--text-soft); display: flex; gap: 0.5rem; align-items: center;">
                                <span style="color: #22a355;">✅</span> <?php echo $step; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p style="font-size: 0.8rem; margin-top: 0.9rem; color: var(--text-soft);">
                            Login page: 
                            <code style="background: rgba(53,21,15,0.07); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.78rem; word-break: break-all;">
                                <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>/arckitchen/admin/login.php
                            </code>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Manage Existing Admins -->
            <?php if (!empty($all_admins)): ?>
            <div class="admin-card" style="margin-top: 0;">
                <h2>👥 Manage Admin Accounts <span style="font-size: 0.9rem; font-weight: 500; opacity: 0.6;">(<?php echo count($all_admins); ?>)</span></h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Created</th>
                            <th>Reset Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_admins as $admin): ?>
                        <tr>
                            <td>
                                <code style="background: rgba(53,21,15,0.07); padding: 0.2rem 0.5rem; border-radius: 5px; font-size: 0.82rem; font-family: monospace;">
                                    <?php echo htmlspecialchars($admin['username']); ?>
                                </code>
                            </td>
                            <td><strong><?php echo htmlspecialchars($admin['full_name'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <form method="post" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    <input type="password" name="new_password" class="form-input" placeholder="New password" style="width: 160px; padding: 0.4rem 0.65rem; font-size: 0.82rem;" required>
                                    <button type="submit" class="btn-admin btn-primary-admin btn-small">Reset</button>
                                </form>
                            </td>
                            <td>
                                <?php if (count($all_admins) > 1): ?>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Delete this admin account?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    <button type="submit" class="btn-admin btn-small" style="background: #dc3545; color: #fff; border: none;">Delete</button>
                                </form>
                                <?php else: ?>
                                <span style="font-size: 0.78rem; color: var(--text-soft);">Last admin</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
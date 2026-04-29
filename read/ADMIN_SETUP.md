# ARC Kitchen Admin Panel - Setup & Security Guide

## Database Setup

### Admin Users Table

The admin authentication system uses the `users` table in the `arc_kitchen` database. The table structure is:

```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) DEFAULT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Important Columns:**
- `username`: The login username (must be unique)
- `password`: Stores the bcrypt-hashed password
- `role`: User role (set to 'admin' for admin access)

## Password Security

### Creating Admin Accounts with Secure Passwords

**Important:** Never store plain-text passwords in the database. Always use PHP's `password_hash()` function with bcrypt.

#### Using PHP Script to Create Admin Users

Create a new admin account using this PHP snippet (run it once in a test environment, then delete):

```php
<?php
// Admin creation script (run once only, then delete this file)
require_once __DIR__ . '/includes/db.php';

$username = 'admin';
$password = 'admin123'; // Change this to a strong password
$full_name = 'ARC Kitchen Administrator';

// Hash the password using bcrypt with PHP's recommended defaults
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
$connection = getDbConnection();
if ($connection) {
    $stmt = $connection->prepare(
        "INSERT INTO users (username, password, full_name, role) 
         VALUES (?, ?, ?, 'admin')
         ON DUPLICATE KEY UPDATE password = VALUES(password)"
    );
    $stmt->bind_param('sss', $username, $password_hash, $full_name);
    
    if ($stmt->execute()) {
        echo "Admin user created successfully!";
        echo "<br>Username: " . htmlspecialchars($username);
        echo "<br>Password: " . htmlspecialchars($password);
        echo "<br><strong>Note: Change this password immediately after first login!</strong>";
    } else {
        echo "Error creating admin user: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Database connection failed";
}
?>
```

### Password Hashing Best Practices

#### Using `password_hash()`

```php
// Current PHP recommended approach (bcrypt)
$hashed = password_hash($password, PASSWORD_DEFAULT);
```

**Parameters:**
- `$password`: The plain-text password to hash
- `PASSWORD_DEFAULT`: Uses bcrypt (currently the most secure option)
  - Algorithm: bcrypt
  - Cost: 10 (default, increases computation time)
  - Salt: Generated automatically

#### Verifying Passwords

```php
// Check password against hash (used in login)
if (password_verify($user_input_password, $stored_hash)) {
    // Password is correct
} else {
    // Password is incorrect
}
```

### Password Hashing Options

#### 1. **bcrypt (RECOMMENDED - Currently Used)**
```php
password_hash($password, PASSWORD_DEFAULT); // Uses bcrypt
// or explicitly:
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]); // cost 12 for more security
```
- **Pros:** Industry standard, resistant to GPU attacks due to high computation cost
- **Cons:** Slower (by design - a feature, not a bug)
- **Recommended cost:** 10-12 (higher = slower but more secure)

#### 2. **Argon2id (MOST SECURE - Recommended for new projects)**
```php
password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64 MB
    'time_cost' => 4,        // 4 iterations
    'threads' => 1
]);
```
- **Pros:** Winner of Password Hashing Competition (2015), resistant to both GPU and ASIC attacks
- **Cons:** Requires PHP 7.2+
- **Recommended for:** New applications and high-security scenarios

#### 3. **Argon2i (Alternative)**
```php
password_hash($password, PASSWORD_ARGON2I);
```
- Similar to Argon2id but older version
- Requires PHP 7.2+

### Migration from Old Hashing Methods

If you have passwords hashed with older methods (MD5, SHA1), migrate them on login:

```php
function loginAdmin(string $username, string $password): bool
{
    $connection = getDbConnection();
    $stmt = $connection->prepare(
        "SELECT id, username, password FROM users WHERE username = ? AND role = 'admin' LIMIT 1"
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return false;
    }

    // Try modern bcrypt/argon2 verification
    if (password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = (int)$user['id'];
        $_SESSION['admin_username'] = $user['username'];
        return true;
    }

    // Fallback for legacy hashes (remove once all users migrated)
    // if (md5($password) === $user['password']) {
    //     // Rehash with bcrypt
    //     $new_hash = password_hash($password, PASSWORD_DEFAULT);
    //     $update = $connection->prepare("UPDATE users SET password = ? WHERE id = ?");
    //     $update->bind_param('si', $new_hash, $user['id']);
    //     $update->execute();
    //     $update->close();
    //     // Then set session...
    // }

    return false;
}
```

## Session Security

### How Session Protection Works

1. **Login Page** (`admin/login.php`):
   - User submits username and password
   - `loginAdmin()` function verifies credentials
   - On success, sets `$_SESSION['admin_id']` and `$_SESSION['admin_username']`

2. **Protected Pages** (all in `/admin/` directory):
   - Call `requireAdmin()` at the top of each page
   - If `$_SESSION['admin_id']` is not set, redirects to login page

3. **Logout** (`admin/logout.php`):
   - Destroys the session
   - Redirects to homepage

### Session Check Implementation

Every admin page MUST include this at the top:

```php
<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();
// ... rest of page code
?>
```

The `requireAdmin()` function:

```php
function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect('login.php');
    }
}
```

## Protected Admin Pages

The following pages are protected with session checks:
- `admin/dashboard.php` ✅
- `admin/bookings.php` ✅
- `admin/menu-manager.php` ✅
- `admin/logout.php` ✅

All admin pages should verify they call `requireAdmin()` after including `functions.php`.

## Security Checklist

- [ ] Change default admin password immediately after first login
- [ ] Use strong passwords (minimum 12 characters, mixed case, numbers, symbols)
- [ ] Regularly review admin user accounts and remove unused ones
- [ ] Keep PHP and dependencies updated
- [ ] Use HTTPS in production
- [ ] Enable CSRF protection for sensitive forms
- [ ] Implement rate limiting on login attempts
- [ ] Log admin actions for audit trails
- [ ] Regular backups of the database
- [ ] Keep password hashing algorithm up to date with `PASSWORD_DEFAULT`

## Updating All Admin Users to Stronger Passwords

To upgrade all admin passwords to use `PASSWORD_ARGON2ID` (most secure):

```php
<?php
require_once __DIR__ . '/includes/db.php';

$connection = getDbConnection();
if (!$connection) {
    die('Database connection failed');
}

// Get all admin users
$result = $connection->query("SELECT id, password FROM users WHERE role = 'admin'");

if ($result) {
    while ($user = $result->fetch_assoc()) {
        // Check if password needs upgrading
        // (password_needs_rehash detects outdated hashes)
        if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
            // Note: We can't upgrade without the plain password
            // Manual update required or force password reset
            echo "User {$user['id']} needs password upgrade<br>";
        }
    }
    $result->free();
}
?>
```

Better approach: Force password reset for all admins:

```php
$connection->query("UPDATE users SET password = NULL WHERE role = 'admin'");
// Then implement password reset flow in login page
```

## Troubleshooting

### "Invalid login credentials" error
- Verify username exists in database
- Check password hash format (should start with `$2y$` for bcrypt)
- Ensure `password_verify()` is being used
- Check for special characters in password

### Session not persisting
- Ensure `session_start()` is called (it's in `functions.php`)
- Check PHP session storage configuration
- Verify cookies are enabled
- Check session timeout settings

### Lockout after failed attempts
Implement rate limiting to prevent brute force:

```php
function checkLoginAttempts(string $username): bool
{
    $attempts_key = 'login_attempts_' . md5($username);
    $attempts = $_SESSION[$attempts_key] ?? 0;
    $last_attempt = $_SESSION[$attempts_key . '_time'] ?? 0;
    
    // Reset if more than 15 minutes passed
    if (time() - $last_attempt > 900) {
        $_SESSION[$attempts_key] = 0;
        return true;
    }
    
    // Lock out after 5 attempts
    if ($attempts >= 5) {
        return false;
    }
    
    return true;
}

// Call before loginAdmin() in login.php
```

## Additional Resources

- PHP Password Hashing: https://www.php.net/manual/en/function.password-hash.php
- OWASP Authentication Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html
- Password Hashing Competition Winner: https://password-hashing.info/

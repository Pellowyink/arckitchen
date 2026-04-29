<?php

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isPostRequest(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function setFlashMessage(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function getFlashMessage(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function defaultMenuItems(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Sample Dish 1',
            'description' => 'Menu description placeholder. Replace this text with your final dish details later.',
            'price' => 0.00,
            'image' => 'assets/images/food-placeholder.svg',
            'category' => 'Sample Category',
        ],
        [
            'id' => 2,
            'name' => 'Sample Dish 2',
            'description' => 'Menu description placeholder. Replace this text with your final dish details later.',
            'price' => 0.00,
            'image' => 'assets/images/food-placeholder.svg',
            'category' => 'Sample Category',
        ],
        [
            'id' => 3,
            'name' => 'Sample Dish 3',
            'description' => 'Menu description placeholder. Replace this text with your final dish details later.',
            'price' => 0.00,
            'image' => 'assets/images/food-placeholder.svg',
            'category' => 'Sample Category',
        ],
    ];
}

function defaultPackages(): array
{
    return [
        [
            'id' => 1,
            'name' => 'Sample Package 1',
            'description' => 'Package description placeholder. Replace this with your final package inclusions and notes.',
            'price' => 0.00,
            'serves' => 'XX - XX pax',
        ],
        [
            'id' => 2,
            'name' => 'Sample Package 2',
            'description' => 'Package description placeholder. Replace this with your final package inclusions and notes.',
            'price' => 0.00,
            'serves' => 'XX - XX pax',
        ],
        [
            'id' => 3,
            'name' => 'Sample Package 3',
            'description' => 'Package description placeholder. Replace this with your final package inclusions and notes.',
            'price' => 0.00,
            'serves' => 'XX - XX pax',
        ],
    ];
}

function fetchAllRecords(string $sql, array $fallback = []): array
{
    $connection = getDbConnection();

    if (!$connection) {
        return $fallback;
    }

    $result = $connection->query($sql);

    if (!$result) {
        return $fallback;
    }

    $records = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();

    return $records ?: $fallback;
}

function getMenuItems(): array
{
    return fetchAllRecords(
        "SELECT id, name, description, price, image, category FROM menu_items WHERE is_active = 1 ORDER BY id DESC",
        defaultMenuItems()
    );
}

function getPackages(): array
{
    return fetchAllRecords(
        "SELECT id, name, description, price, serves FROM packages WHERE is_active = 1 ORDER BY id DESC",
        defaultPackages()
    );
}

function getInquiries(): array
{
    return fetchAllRecords("SELECT * FROM inquiries ORDER BY created_at DESC");
}

function getContactMessages(): array
{
    return fetchAllRecords("SELECT * FROM contact_messages ORDER BY created_at DESC");
}

function bookingStatuses(): array
{
    return ['Pending', 'Accepted', 'Completed', 'Cancelled'];
}

function validateRequiredFields(array $fields): array
{
    $errors = [];

    foreach ($fields as $field => $label) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[] = $label . ' is required.';
        }
    }

    return $errors;
}

function saveInquiry(array $data): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare(
        "INSERT INTO inquiries (full_name, email, phone, event_date, event_type, guest_count, package_interest, message, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param(
        'sssssiss',
        $data['full_name'],
        $data['email'],
        $data['phone'],
        $data['event_date'],
        $data['event_type'],
        $data['guest_count'],
        $data['package_interest'],
        $data['message']
    );

    $saved = $statement->execute();
    $statement->close();

    return $saved;
}

function saveContactMessage(array $data): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare(
        "INSERT INTO contact_messages (full_name, email, subject, message)
         VALUES (?, ?, ?, ?)"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param(
        'ssss',
        $data['full_name'],
        $data['email'],
        $data['subject'],
        $data['message']
    );

    $saved = $statement->execute();
    $statement->close();

    return $saved;
}

function countRows(string $table): int
{
    $connection = getDbConnection();

    if (!$connection) {
        return 0;
    }

    $safeTable = preg_replace('/[^a-z_]/i', '', $table);
    $result = $connection->query("SELECT COUNT(*) AS total FROM {$safeTable}");

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();
    $result->free();

    return (int) ($row['total'] ?? 0);
}

function loginAdmin(string $username, string $password): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare("SELECT id, username, password FROM users WHERE username = ? AND role = 'admin' LIMIT 1");

    if (!$statement) {
        return false;
    }

    $statement->bind_param('s', $username);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $statement->close();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION['admin_username'] = $user['username'];

    return true;
}

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect('login.php');
    }
}

function updateInquiryStatus(int $id, string $status): bool
{
    $connection = getDbConnection();

    if (!$connection || !in_array($status, bookingStatuses(), true)) {
        return false;
    }

    $statement = $connection->prepare("UPDATE inquiries SET status = ? WHERE id = ?");

    if (!$statement) {
        return false;
    }

    $statement->bind_param('si', $status, $id);
    $saved = $statement->execute();
    $statement->close();

    return $saved;
}

function deleteInquiry(int $id): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare("DELETE FROM inquiries WHERE id = ?");

    if (!$statement) {
        return false;
    }

    $statement->bind_param('i', $id);
    $deleted = $statement->execute();
    $statement->close();

    return $deleted;
}

function saveMenuItem(array $data): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare(
        "INSERT INTO menu_items (name, description, price, image, category, is_active)
         VALUES (?, ?, ?, ?, ?, 1)"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param(
        'ssdss',
        $data['name'],
        $data['description'],
        $data['price'],
        $data['image'],
        $data['category']
    );

    $saved = $statement->execute();
    $statement->close();

    return $saved;
}

function savePackage(array $data): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare(
        "INSERT INTO packages (name, description, price, serves, is_active)
         VALUES (?, ?, ?, ?, 1)"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param(
        'ssds',
        $data['name'],
        $data['description'],
        $data['price'],
        $data['serves']
    );

    $saved = $statement->execute();
    $statement->close();

    return $saved;
}

function currentPageName(): string
{
    return basename($_SERVER['PHP_SELF'] ?? '');
}

// =====================================================
// STATE MACHINE & DASHBOARD COUNTER FUNCTIONS
// =====================================================

/**
 * Get count of pending inquiries (for dashboard counter)
 * Trigger: +1 on new submission, -1 when moved to 'approved' or 'rejected'
 */
function countPendingInquiries(): int
{
    $connection = getDbConnection();
    if (!$connection) return 0;
    
    $result = $connection->query("SELECT COUNT(*) AS total FROM inquiries WHERE status = 'pending'");
    if (!$result) return 0;
    
    $row = $result->fetch_assoc();
    $result->free();
    return (int) ($row['total'] ?? 0);
}

/**
 * Get count of confirmed bookings (for dashboard counter)
 * Trigger: +1 when an Inquiry is approved, -1 when marked as 'completed' or 'cancelled'
 */
function countConfirmedBookings(): int
{
    $connection = getDbConnection();
    if (!$connection) return 0;
    
    $result = $connection->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'confirmed'");
    if (!$result) return 0;
    
    $row = $result->fetch_assoc();
    $result->free();
    return (int) ($row['total'] ?? 0);
}

/**
 * Get count of completed bookings (for dashboard counter)
 * Trigger: +1 when a Confirmed Booking is finalized
 */
function countCompletedBookings(): int
{
    $connection = getDbConnection();
    if (!$connection) return 0;
    
    $result = $connection->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'completed'");
    if (!$result) return 0;
    
    $row = $result->fetch_assoc();
    $result->free();
    return (int) ($row['total'] ?? 0);
}

/**
 * Get count of active packages (is_active = 1)
 */
function countActivePackages(): int
{
    $connection = getDbConnection();
    if (!$connection) return 0;
    
    $result = $connection->query("SELECT COUNT(*) AS total FROM packages WHERE is_active = 1");
    if (!$result) return 0;
    
    $row = $result->fetch_assoc();
    $result->free();
    return (int) ($row['total'] ?? 0);
}

/**
 * Get count of active menu items (is_active = 1)
 */
function countActiveMenuItems(): int
{
    $connection = getDbConnection();
    if (!$connection) return 0;
    
    $result = $connection->query("SELECT COUNT(*) AS total FROM menu_items WHERE is_active = 1");
    if (!$result) return 0;
    
    $row = $result->fetch_assoc();
    $result->free();
    return (int) ($row['total'] ?? 0);
}

// =====================================================
// BOOKINGS TABLE FUNCTIONS
// =====================================================

/**
 * Get all bookings with optional filters
 * @param array $filters - Optional: ['status' => 'confirmed', 'search' => 'name/email', 'date_from' => '2026-01-01', 'date_to' => '2026-12-31', 'package_id' => 1]
 */
function getBookings(array $filters = []): array
{
    $connection = getDbConnection();
    if (!$connection) return [];
    
    $sql = "SELECT * FROM bookings WHERE 1=1";
    
    // Filter by status
    if (!empty($filters['status'])) {
        $status = $filters['status'];
        $sql .= " AND status = '$status'";
    }
    
    // Search by customer name or email
    if (!empty($filters['search'])) {
        $search = '%' . $connection->real_escape_string($filters['search']) . '%';
        $sql .= " AND (customer_name LIKE '$search' OR customer_email LIKE '$search')";
    }
    
    // Filter by date range
    if (!empty($filters['date_from'])) {
        $date = $filters['date_from'];
        $sql .= " AND event_date >= '$date'";
    }
    if (!empty($filters['date_to'])) {
        $date = $filters['date_to'];
        $sql .= " AND event_date <= '$date'";
    }
    
    // Filter by package
    if (!empty($filters['package_id'])) {
        $pkg = (int)$filters['package_id'];
        $sql .= " AND package_id = $pkg";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $result = $connection->query($sql);
    if (!$result) return [];
    
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    
    return $bookings ?: [];
}

/**
 * Get a single booking by ID
 */
function getBookingById(int $id): ?array
{
    $connection = getDbConnection();
    if (!$connection) return null;
    
    $statement = $connection->prepare("SELECT * FROM bookings WHERE id = ?");
    if (!$statement) return null;
    
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    $booking = $result->fetch_assoc();
    $statement->close();
    
    return $booking ?: null;
}

/**
 * Save a new booking
 */
function saveBooking(array $data): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    $statement = $connection->prepare(
        "INSERT INTO bookings (inquiry_id, customer_name, customer_email, customer_phone, 
         event_date, event_type, guest_count, items_json, total_amount, package_id, special_requests, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    if (!$statement) return false;
    
    $inquiry_id = $data['inquiry_id'] ?? null;
    $items_json = $data['items_json'] ?? null;
    $total = $data['total_amount'] ?? 0;
    $package_id = $data['package_id'] ?? null;
    $requests = $data['special_requests'] ?? null;
    $status = $data['status'] ?? 'pending';
    
    $statement->bind_param(
        'isssssisssi',
        $inquiry_id,
        $data['customer_name'],
        $data['customer_email'],
        $data['customer_phone'],
        $data['event_date'],
        $data['event_type'],
        $data['guest_count'],
        $items_json,
        $total,
        $package_id,
        $requests,
        $status
    );
    
    $saved = $statement->execute();
    $statement->close();
    
    return $saved;
}

/**
 * Update booking status (state machine transition)
 */
function updateBookingStatus(int $id, string $status): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'blocked'];
    if (!in_array($status, $valid_statuses)) return false;
    
    $statement = $connection->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    if (!$statement) return false;
    
    $statement->bind_param('si', $status, $id);
    $saved = $statement->execute();
    $statement->close();
    
    return $saved;
}

/**
 * Update booking items and total (for editing)
 */
function updateBookingItems(int $id, string $items_json, float $total_amount): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    $statement = $connection->prepare("UPDATE bookings SET items_json = ?, total_amount = ?, updated_at = NOW() WHERE id = ?");
    if (!$statement) return false;
    
    $statement->bind_param('sdi', $items_json, $total_amount, $id);
    $saved = $statement->execute();
    $statement->close();
    
    return $saved;
}

/**
 * Approve an inquiry and create corresponding booking (state machine logic)
 * Trigger: +1 Confirmed Booking, -1 Inquiry
 */
function approveInquiry(int $inquiry_id): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    // Get the inquiry data
    $stmt = $connection->prepare("SELECT * FROM inquiries WHERE id = ?");
    if (!$stmt) return false;
    
    $stmt->bind_param('i', $inquiry_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $inquiry = $result->fetch_assoc();
    $stmt->close();
    
    if (!$inquiry) return false;
    
    // Update inquiry status to 'approved'
    $update_stmt = $connection->prepare("UPDATE inquiries SET status = 'approved' WHERE id = ?");
    if (!$update_stmt) return false;
    
    $update_stmt->bind_param('i', $inquiry_id);
    if (!$update_stmt->execute()) {
        $update_stmt->close();
        return false;
    }
    $update_stmt->close();
    
    // Create corresponding booking
    $insert_stmt = $connection->prepare(
        "INSERT INTO bookings (inquiry_id, customer_name, customer_email, customer_phone, 
         event_date, event_type, guest_count, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    
    if (!$insert_stmt) return false;
    
    $insert_stmt->bind_param(
        'isssssi',
        $inquiry_id,
        $inquiry['full_name'],
        $inquiry['email'],
        $inquiry['phone'],
        $inquiry['event_date'],
        $inquiry['event_type'],
        $inquiry['guest_count']
    );
    
    $inserted = $insert_stmt->execute();
    $insert_stmt->close();
    
    return $inserted;
}

/**
 * Reject an inquiry (state machine logic)
 * Trigger: -1 Inquiry
 */
function rejectInquiry(int $inquiry_id): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    $statement = $connection->prepare("UPDATE inquiries SET status = 'rejected' WHERE id = ?");
    if (!$statement) return false;
    
    $statement->bind_param('i', $inquiry_id);
    $saved = $statement->execute();
    $statement->close();
    
    return $saved;
}

/**
 * Get inquiries with optional filters
 * @param array $filters - Optional: ['status' => 'pending', 'search' => 'name/email', 'date_from' => '2026-01-01', 'date_to' => '2026-12-31']
 */
function getInquiriesFiltered(array $filters = []): array
{
    $connection = getDbConnection();
    if (!$connection) return [];
    
    $sql = "SELECT * FROM inquiries WHERE 1=1";
    
    // Filter by status
    if (!empty($filters['status'])) {
        $status = $filters['status'];
        $sql .= " AND status = '$status'";
    }
    
    // Search by customer name or email
    if (!empty($filters['search'])) {
        $search = '%' . $connection->real_escape_string($filters['search']) . '%';
        $sql .= " AND (full_name LIKE '$search' OR email LIKE '$search')";
    }
    
    // Filter by date range
    if (!empty($filters['date_from'])) {
        $date = $filters['date_from'];
        $sql .= " AND event_date >= '$date'";
    }
    if (!empty($filters['date_to'])) {
        $date = $filters['date_to'];
        $sql .= " AND event_date <= '$date'";
    }
    
    // Filter by package interest
    if (!empty($filters['package_id'])) {
        $pkg = $connection->real_escape_string($filters['package_id']);
        $sql .= " AND package_interest = '$pkg'";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $result = $connection->query($sql);
    if (!$result) return [];
    
    $inquiries = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    
    return $inquiries ?: [];
}

/**
 * Check if admin is authenticated and redirect if not
 * Must be called at the top of every admin page
 */
function requireAdminCheck(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}


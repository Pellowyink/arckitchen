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

/* =====================================================
   MENU SYSTEM - Database Driven
   ===================================================== */

/**
 * Get all menu items from database
 */
function getMenuItems(?string $category = null): array
{
    $connection = getDbConnection();
    if (!$connection) return defaultMenuItems();
    
    $sql = "SELECT * FROM menu_items WHERE is_active = 1";
    if ($category) {
        $cat = $connection->real_escape_string($category);
        $sql .= " AND category = '$cat'";
    }
    $sql .= " ORDER BY category, name";
    
    $result = $connection->query($sql);
    if (!$result || $result->num_rows === 0) {
        return defaultMenuItems();
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all menu categories
 */
function getMenuCategories(): array
{
    $connection = getDbConnection();
    if (!$connection) return ['Sample Category'];
    
    $sql = "SELECT DISTINCT category FROM menu_items WHERE is_active = 1 ORDER BY category";
    $result = $connection->query($sql);
    if (!$result) return ['Sample Category'];
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories ?: ['Sample Category'];
}

/**
 * Get single menu item by ID
 */
function getMenuItem(int $id): ?array
{
    $connection = getDbConnection();
    if (!$connection) return null;
    
    $statement = $connection->prepare("SELECT * FROM menu_items WHERE id = ? AND is_active = 1");
    if (!$statement) return null;
    
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    $item = $result->fetch_assoc();
    $statement->close();
    
    return $item ?: null;
}

/* =====================================================
   PACKAGES SYSTEM - Database Driven
   ===================================================== */

/**
 * Get all packages from database
 */
function getPackages(): array
{
    $connection = getDbConnection();
    if (!$connection) return defaultPackages();
    
    $sql = "SELECT * FROM packages WHERE is_active = 1 ORDER BY total_price ASC";
    $result = $connection->query($sql);
    if (!$result || $result->num_rows === 0) {
        return defaultPackages();
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get single package with items
 */
function getPackage(int $id): ?array
{
    $connection = getDbConnection();
    if (!$connection) return null;
    
    // Get package details
    $statement = $connection->prepare("SELECT * FROM packages WHERE id = ? AND is_active = 1");
    if (!$statement) return null;
    
    $statement->bind_param('i', $id);
    $statement->execute();
    $result = $statement->get_result();
    $package = $result->fetch_assoc();
    $statement->close();
    
    if (!$package) return null;
    
    // Get package items with menu details
    $sql = "SELECT pi.*, mi.name, mi.category, mi.price, mi.description, mi.image 
            FROM package_items pi 
            JOIN menu_items mi ON pi.menu_item_id = mi.id 
            WHERE pi.package_id = ?";
    $statement = $connection->prepare($sql);
    if ($statement) {
        $statement->bind_param('i', $id);
        $statement->execute();
        $result = $statement->get_result();
        $package['items'] = $result->fetch_all(MYSQLI_ASSOC);
        $statement->close();
    }
    
    return $package;
}

/**
 * Get package items only
 */
function getPackageItems(int $packageId): array
{
    $connection = getDbConnection();
    if (!$connection) return [];
    
    $sql = "SELECT pi.*, mi.name, mi.category, mi.price, mi.description, mi.image 
            FROM package_items pi 
            JOIN menu_items mi ON pi.menu_item_id = mi.id 
            WHERE pi.package_id = ?";
    $statement = $connection->prepare($sql);
    if (!$statement) return [];
    
    $statement->bind_param('i', $packageId);
    $statement->execute();
    $result = $statement->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    
    return $items;
}

/* =====================================================
   CALENDAR & UNAVAILABLE DATES
   ===================================================== */

/**
 * Get unavailable dates for calendar
 */
function getUnavailableDates(string $month, string $year): array
{
    $connection = getDbConnection();
    if (!$connection) return [];
    
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $sql = "SELECT date, reason, status FROM unavailable_dates 
            WHERE date BETWEEN ? AND ?
            ORDER BY date";
    $statement = $connection->prepare($sql);
    if (!$statement) return [];
    
    $statement->bind_param('ss', $startDate, $endDate);
    $statement->execute();
    $result = $statement->get_result();
    $dates = $result->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    
    return $dates;
}

/**
 * Check if a specific date is available
 */
function isDateAvailable(string $date): bool
{
    // Check if date is in the past
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return false;
    }
    
    $connection = getDbConnection();
    if (!$connection) return true;
    
    $statement = $connection->prepare("SELECT id FROM unavailable_dates WHERE date = ?");
    if (!$statement) return true;
    
    $statement->bind_param('s', $date);
    $statement->execute();
    $result = $statement->get_result();
    $unavailable = $result->num_rows > 0;
    $statement->close();
    
    return !$unavailable;
}

/**
 * Get all booked dates (confirmed bookings)
 */
function getBookedDates(string $month, string $year): array
{
    $connection = getDbConnection();
    if (!$connection) return [];
    
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $sql = "SELECT event_date as date, COUNT(*) as booking_count 
            FROM bookings 
            WHERE status IN ('confirmed', 'pending') 
            AND event_date BETWEEN ? AND ? 
            GROUP BY event_date";
    $statement = $connection->prepare($sql);
    if (!$statement) return [];
    
    $statement->bind_param('ss', $startDate, $endDate);
    $statement->execute();
    $result = $statement->get_result();
    $dates = $result->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    
    return $dates;
}

/**
 * Mark date as unavailable (admin function)
 */
function blockDate(string $date, string $reason = '', string $status = 'blocked'): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    $statement = $connection->prepare("INSERT INTO unavailable_dates (date, reason, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reason = ?, status = ?");
    if (!$statement) return false;
    
    $statement->bind_param('sssss', $date, $reason, $status, $reason, $status);
    $saved = $statement->execute();
    $statement->close();
    
    return $saved;
}

/**
 * Unblock a date (admin function)
 */
function unblockDate(string $date): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    $statement = $connection->prepare("DELETE FROM unavailable_dates WHERE date = ?");
    if (!$statement) return false;
    
    $statement->bind_param('s', $date);
    $deleted = $statement->execute();
    $statement->close();
    
    return $deleted;
}

/* =====================================================
   INQUIRY ITEMS / ORDER MANAGEMENT
   ===================================================== */

/**
 * Save inquiry items from order
 */
function saveInquiryItems(int $inquiryId, array $items): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    // First clear existing items
    $statement = $connection->prepare("DELETE FROM inquiry_items WHERE inquiry_id = ?");
    if (!$statement) return false;
    $statement->bind_param('i', $inquiryId);
    $statement->execute();
    $statement->close();
    
    // Insert new items
    foreach ($items as $item) {
        $menuItemId = $item['menu_item_id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['unit_price'];
        $subtotal = $quantity * $unitPrice;
        $notes = $item['notes'] ?? '';
        
        $statement = $connection->prepare("INSERT INTO inquiry_items (inquiry_id, menu_item_id, quantity, unit_price, subtotal, notes) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$statement) continue;
        
        $statement->bind_param('iiidds', $inquiryId, $menuItemId, $quantity, $unitPrice, $subtotal, $notes);
        $statement->execute();
        $statement->close();
    }
    
    return true;
}

/**
 * Get inquiry items with package handling
 */
function getInquiryItems(int $inquiryId): array
{
    $connection = getDbConnection();
    if (!$connection) return [];
    
    // Get all items including packages
    $sql = "SELECT ii.*, 
            CASE 
                WHEN ii.is_package = 1 THEN p.name 
                ELSE mi.name 
            END as name,
            CASE 
                WHEN ii.is_package = 1 THEN 'Package' 
                ELSE mi.category 
            END as category,
            CASE 
                WHEN ii.is_package = 1 THEN 'package' 
                ELSE 'item' 
            END as type,
            p.serves as package_serves
            FROM inquiry_items ii 
            LEFT JOIN menu_items mi ON ii.menu_item_id = mi.id 
            LEFT JOIN packages p ON ii.package_id = p.id
            WHERE ii.inquiry_id = ?";
            
    $statement = $connection->prepare($sql);
    if (!$statement) return [];
    
    $statement->bind_param('i', $inquiryId);
    $statement->execute();
    $result = $statement->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    
    return $items;
}

/**
 * Calculate order total
 */
function calculateOrderTotal(array $items): float
{
    $total = 0;
    foreach ($items as $item) {
        $total += ($item['unit_price'] * $item['quantity']);
    }
    return $total;
}

/**
 * Approve inquiry with payment data and create booking
 */
function approveInquiryWithPayment(int $inquiryId, float $downPayment, float $fullPayment, float $totalAmount): bool
{
    $connection = getDbConnection();
    if (!$connection) {
        error_log("approveInquiryWithPayment: No database connection");
        return false;
    }
    
    // Start transaction
    $connection->begin_transaction();
    
    try {
        // Calculate payment status
        $totalPaid = $downPayment + $fullPayment;
        $balance = $totalAmount - $totalPaid;
        $paymentStatus = $balance <= 0 ? 'fully_paid' : ($totalPaid > 0 ? 'partial' : 'pending');
        
        error_log("approveInquiryWithPayment: Inquiry $inquiryId, Total: $totalAmount, Down: $downPayment, Full: $fullPayment, Status: $paymentStatus");
        
        // Update inquiry with payment data and mark as approved
        $stmt = $connection->prepare(
            "UPDATE inquiries 
             SET status = 'approved', 
                 down_payment = ?, 
                 full_payment = ?, 
                 total_amount = ?,
                 payment_status = ?
             WHERE id = ? AND status = 'pending'"
        );
        
        if (!$stmt) {
            error_log("approveInquiryWithPayment: Prepare failed for UPDATE: " . $connection->error);
            $connection->rollback();
            return false;
        }
        
        $stmt->bind_param('dddsi', $downPayment, $fullPayment, $totalAmount, $paymentStatus, $inquiryId);
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("approveInquiryWithPayment: UPDATE execute failed: " . $stmt->error);
            $stmt->close();
            $connection->rollback();
            return false;
        }
        
        if ($stmt->affected_rows === 0) {
            error_log("approveInquiryWithPayment: No rows updated for inquiry $inquiryId");
            $stmt->close();
            $connection->rollback();
            return false;
        }
        $stmt->close();
        
        // Get inquiry data for booking creation
        $stmt = $connection->prepare("SELECT * FROM inquiries WHERE id = ?");
        $stmt->bind_param('i', $inquiryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $inquiry = $result->fetch_assoc();
        $stmt->close();
        
        if (!$inquiry) {
            $connection->rollback();
            return false;
        }
        
        // Get inquiry items for booking
        $items = getInquiryItems($inquiryId);
        $itemsJson = json_encode($items);
        
        // Create booking from inquiry
        $bookingStmt = $connection->prepare(
            "INSERT INTO bookings (inquiry_id, customer_name, customer_email, customer_phone, 
             event_date, event_type, guest_count, items_json, total_amount, down_payment, full_payment, 
             payment_status, special_requests, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        
        if (!$bookingStmt) {
            error_log("approveInquiryWithPayment: Prepare failed for booking INSERT: " . $connection->error);
            $connection->rollback();
            return false;
        }
        
        $bookingStmt->bind_param(
            'issssisdddsss',
            $inquiryId,
            $inquiry['full_name'],
            $inquiry['email'],
            $inquiry['phone'],
            $inquiry['event_date'],
            $inquiry['event_type'],
            $inquiry['guest_count'],
            $itemsJson,
            $totalAmount,
            $downPayment,
            $fullPayment,
            $paymentStatus,
            $inquiry['message']
        );
        
        if (!$bookingStmt->execute()) {
            error_log("approveInquiryWithPayment: Booking INSERT execute failed: " . $bookingStmt->error);
            $bookingStmt->close();
            $connection->rollback();
            return false;
        }
        
        error_log("approveInquiryWithPayment: Successfully created booking for inquiry $inquiryId");
        $bookingStmt->close();
        $connection->commit();
        return true;
        
    } catch (Exception $e) {
        $connection->rollback();
        error_log("Error approving inquiry with payment: " . $e->getMessage());
        return false;
    }
}

/**
 * Update booking status with payment data
 */
function updateBookingStatusWithPayment(int $bookingId, string $status, ?float $downPayment, ?float $fullPayment, ?float $totalAmount): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    // Build query based on what payment data is provided
    $updates = ["status = ?"];
    $types = 's';
    $params = [$status];
    
    if ($downPayment !== null) {
        $updates[] = "down_payment = ?";
        $types .= 'd';
        $params[] = $downPayment;
    }
    
    if ($fullPayment !== null) {
        $updates[] = "full_payment = ?";
        $types .= 'd';
        $params[] = $fullPayment;
    }
    
    if ($totalAmount !== null && $totalAmount > 0) {
        $updates[] = "total_amount = ?";
        $types .= 'd';
        $params[] = $totalAmount;
    }
    
    // Calculate payment status
    $currentBooking = getBookingById($bookingId);
    if ($currentBooking) {
        $dp = $downPayment !== null ? $downPayment : ($currentBooking['down_payment'] ?? 0);
        $fp = $fullPayment !== null ? $fullPayment : ($currentBooking['full_payment'] ?? 0);
        $total = $totalAmount !== null && $totalAmount > 0 ? $totalAmount : ($currentBooking['total_amount'] ?? 0);
        
        $totalPaid = $dp + $fp;
        $paymentStatus = ($totalPaid >= $total && $total > 0) ? 'fully_paid' : ($totalPaid > 0 ? 'partial' : 'pending');
        
        $updates[] = "payment_status = ?";
        $types .= 's';
        $params[] = $paymentStatus;
    }
    
    $sql = "UPDATE bookings SET " . implode(', ', $updates) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $bookingId;
    
    $stmt = $connection->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param($types, ...$params);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get payment status label and color
 */
function getPaymentStatusInfo(string $status): array
{
    $labels = [
        'pending' => ['label' => 'Pending Payment', 'class' => 'badge-pending', 'icon' => '⏳'],
        'partial' => ['label' => 'Partially Paid', 'class' => 'badge-partial', 'icon' => '💳'],
        'fully_paid' => ['label' => 'Fully Paid', 'class' => 'badge-success', 'icon' => '✅'],
    ];
    
    return $labels[$status] ?? $labels['pending'];
}

/**
 * Format currency for display
 */
function formatCurrency(float $amount): string
{
    return '₱' . number_format($amount, 2);
}


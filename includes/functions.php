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

/**
 * Save a new inquiry with duplicate prevention
 * Prevents double-submit within 5 seconds
 */
function saveInquiry(array $data): array
{
    $connection = getDbConnection();
    if (!$connection) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }

    // DUPLICATE PREVENTION: Check for recent inquiry with same email/event_date
    $checkStmt = $connection->prepare(
        "SELECT id, created_at FROM inquiries 
         WHERE email = ? 
         AND event_date = ? 
         AND created_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
         LIMIT 1"
    );
    
    if ($checkStmt) {
        $checkStmt->bind_param('ss', $data['email'], $data['event_date']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            $checkStmt->close();
            return [
                'success' => false, 
                'error' => 'Duplicate inquiry detected',
                'duplicate' => true,
                'inquiry_id' => $existing['id'],
                'message' => 'An inquiry was just submitted for this event. Please wait a moment.'
            ];
        }
        $checkStmt->close();
    }

    $statement = $connection->prepare(
        "INSERT INTO inquiries (full_name, email, phone, event_date, event_type, guest_count, package_interest, message, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );

    if (!$statement) {
        return ['success' => false, 'error' => 'Database prepare failed'];
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
    $insertId = $statement->insert_id;
    $statement->close();

    if ($saved) {
        return [
            'success' => true,
            'inquiry_id' => $insertId,
            'message' => 'Inquiry submitted successfully'
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to save inquiry'];
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

/**
 * Soft delete an inquiry with reason tracking
 */
function softDeleteInquiry(int $id, int $deletedBy, ?string $reason = null): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    // First, get the record data for audit log
    $recordData = null;
    $selectStmt = $connection->prepare("SELECT * FROM inquiries WHERE id = ? AND (deleted_at IS NULL OR is_active = 1)");
    if ($selectStmt) {
        $selectStmt->bind_param('i', $id);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $record = $result->fetch_assoc();
        if ($record) {
            $recordData = json_encode($record);
        }
        $selectStmt->close();
    }

    // Perform soft delete
    $statement = $connection->prepare(
        "UPDATE inquiries SET is_active = 0, deleted_at = NOW(), deleted_by = ?, delete_reason = ? WHERE id = ?"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param('isi', $deletedBy, $reason, $id);
    $deleted = $statement->execute();
    $statement->close();

    // Log the deletion
    if ($deleted && $recordData) {
        logDeletion('inquiry', $id, $recordData, $deletedBy, $reason, 'soft');
    }

    return $deleted;
}

/**
 * Bulk soft delete inquiries
 */
function bulkSoftDeleteInquiries(array $ids, int $deletedBy, ?string $reason = null): array
{
    $connection = getDbConnection();

    if (!$connection || empty($ids)) {
        return ['success' => false, 'deleted_count' => 0, 'message' => 'Invalid request'];
    }

    $deletedCount = 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    // Get records for audit log before deletion
    $selectSql = "SELECT * FROM inquiries WHERE id IN ($placeholders) AND (deleted_at IS NULL OR is_active = 1)";
    $selectStmt = $connection->prepare($selectSql);
    if ($selectStmt) {
        $selectStmt->bind_param($types, ...$ids);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $selectStmt->close();

        // Perform bulk soft delete
        $updateSql = "UPDATE inquiries SET is_active = 0, deleted_at = NOW(), deleted_by = ?, delete_reason = ? WHERE id IN ($placeholders) AND (deleted_at IS NULL OR is_active = 1)";
        $updateStmt = $connection->prepare($updateSql);
        if ($updateStmt) {
            $params = array_merge([$deletedBy, $reason], $ids);
            $typesWithReason = 'si' . $types;
            $updateStmt->bind_param($typesWithReason, ...$params);
            $updateStmt->execute();
            $deletedCount = $updateStmt->affected_rows;
            $updateStmt->close();

            // Log each deletion
            if ($deletedCount > 0) {
                logDeletion('inquiry', 0, json_encode($records), $deletedBy, $reason, 'bulk');
            }
        }
    }

    return [
        'success' => $deletedCount > 0,
        'deleted_count' => $deletedCount,
        'message' => $deletedCount > 0 ? "{$deletedCount} record(s) deleted" : 'No records deleted'
    ];
}

/**
 * Restore a soft-deleted inquiry
 */
function restoreInquiry(int $id, int $restoredBy): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare(
        "UPDATE inquiries SET is_active = 1, deleted_at = NULL, deleted_by = NULL, delete_reason = NULL WHERE id = ?"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param('i', $id);
    $restored = $statement->execute();
    $statement->close();

    // Update deletion log
    if ($restored) {
        $logStmt = $connection->prepare("UPDATE deleted_records_log SET restored_at = NOW(), restored_by = ? WHERE record_type = 'inquiry' AND record_id = ? AND restored_at IS NULL");
        if ($logStmt) {
            $logStmt->bind_param('ii', $restoredBy, $id);
            $logStmt->execute();
            $logStmt->close();
        }
    }

    return $restored;
}

/**
 * Permanently delete an inquiry (for already soft-deleted records)
 */
function permanentlyDeleteInquiry(int $id): bool
{
    return hardDeleteInquiry($id);
}

function dbTableExists(mysqli $connection, string $table): bool
{
    $stmt = $connection->prepare("
        SELECT COUNT(*) AS table_count
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int)($row['table_count'] ?? 0) > 0;
}

function hardDeleteInquiry(int $id): bool
{
    $connection = getDbConnection();
    if (!$connection) {
        return false;
    }

    $connection->begin_transaction();

    try {
        if (dbTableExists($connection, 'payments')) {
            $stmt = $connection->prepare("
                DELETE p FROM payments p
                INNER JOIN bookings b ON b.id = p.booking_id
                WHERE b.inquiry_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $connection->prepare("DELETE FROM payments WHERE inquiry_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        if (dbTableExists($connection, 'order_items')) {
            $stmt = $connection->prepare("
                DELETE oi FROM order_items oi
                INNER JOIN bookings b ON b.id = oi.booking_id
                WHERE b.inquiry_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $stmt = $connection->prepare("DELETE FROM inquiry_items WHERE inquiry_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare inquiry item delete: ' . $connection->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $connection->prepare("DELETE FROM bookings WHERE inquiry_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare related booking delete: ' . $connection->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $connection->prepare("DELETE FROM inquiries WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare inquiry delete: ' . $connection->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$deleted) {
            $connection->rollback();
            return false;
        }

        $connection->commit();
        return true;
    } catch (Throwable $e) {
        $connection->rollback();
        error_log("hardDeleteInquiry failed for ID {$id}: " . $e->getMessage());
        return false;
    }
}

function bulkHardDeleteInquiries(array $ids): array
{
    $deletedCount = 0;
    foreach ($ids as $id) {
        if (hardDeleteInquiry((int)$id)) {
            $deletedCount++;
        }
    }

    return [
        'success' => $deletedCount > 0,
        'deleted_count' => $deletedCount,
        'message' => $deletedCount > 0 ? "{$deletedCount} inquiry record(s) permanently deleted" : 'No inquiry records deleted'
    ];
}

/**
 * Log deletion to audit trail
 */
function logDeletion(string $recordType, int $recordId, ?string $recordData, int $deletedBy, ?string $reason, string $deleteType): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    // Get admin name
    $adminName = 'Unknown';
    $adminStmt = $connection->prepare("SELECT username FROM admins WHERE id = ?");
    if ($adminStmt) {
        $adminStmt->bind_param('i', $deletedBy);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result();
        if ($adminRow = $adminResult->fetch_assoc()) {
            $adminName = $adminRow['username'];
        }
        $adminStmt->close();
    }

    $statement = $connection->prepare(
        "INSERT INTO deleted_records_log (record_type, record_id, record_data, deleted_by, deleted_by_name, delete_reason, delete_type)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param('sisisss', $recordType, $recordId, $recordData, $deletedBy, $adminName, $reason, $deleteType);
    $logged = $statement->execute();
    $statement->close();

    return $logged;
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
    
    // Auto-add missing columns if they don't exist (for fresh database imports)
    static $bookingColumnChecked = false;
    if (!$bookingColumnChecked) {
        // Check and add archived_at
        $checkColumn = $connection->query("SHOW COLUMNS FROM bookings LIKE 'archived_at'");
        if ($checkColumn && $checkColumn->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN archived_at DATETIME NULL AFTER status");
        }
        
        // Check and add down_payment
        $checkDownPayment = $connection->query("SHOW COLUMNS FROM bookings LIKE 'down_payment'");
        if ($checkDownPayment && $checkDownPayment->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN down_payment DECIMAL(10,2) NULL DEFAULT 0 AFTER total_amount");
        }
        
        // Check and add full_payment
        $checkFullPayment = $connection->query("SHOW COLUMNS FROM bookings LIKE 'full_payment'");
        if ($checkFullPayment && $checkFullPayment->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN full_payment DECIMAL(10,2) NULL DEFAULT 0 AFTER down_payment");
        }
        
        // Check and add payment_status
        $checkPaymentStatus = $connection->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
        if ($checkPaymentStatus && $checkPaymentStatus->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(20) NULL DEFAULT 'pending' AFTER full_payment");
        }
        
        // Check and add event_time
        $checkEventTime = $connection->query("SHOW COLUMNS FROM bookings LIKE 'event_time'");
        if ($checkEventTime && $checkEventTime->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN event_time TIME NULL AFTER event_date");
        }
        
        // Check and add event_location
        $checkEventLocation = $connection->query("SHOW COLUMNS FROM bookings LIKE 'event_location'");
        if ($checkEventLocation && $checkEventLocation->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN event_location TEXT NULL AFTER event_time");
        }
        
        $bookingColumnChecked = true;
    }
    
    $sql = "SELECT * FROM bookings WHERE 1=1";
    
    // Filter by archived status - only apply when explicitly set
    if (isset($filters['archived'])) {
        if ($filters['archived'] === true) {
            $sql .= " AND archived_at IS NOT NULL";
        } else {
            $sql .= " AND (archived_at IS NULL OR archived_at = '')";
        }
    }
    
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
 * Save a new booking with duplicate prevention
 * Prevents double-submit within 5 seconds
 */
function saveBooking(array $data): array
{
    $connection = getDbConnection();
    if (!$connection) return ['success' => false, 'error' => 'Database connection failed'];
    
    // DUPLICATE PREVENTION: Check for recent booking with same customer/email/event_date
    $checkStmt = $connection->prepare(
        "SELECT id, created_at FROM bookings 
         WHERE customer_email = ? 
         AND event_date = ? 
         AND created_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
         LIMIT 1"
    );
    
    if ($checkStmt) {
        $checkStmt->bind_param('ss', $data['customer_email'], $data['event_date']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            $checkStmt->close();
            return [
                'success' => false, 
                'error' => 'Duplicate booking detected',
                'duplicate' => true,
                'booking_id' => $existing['id'],
                'message' => 'A booking was just created for this event. Please wait a moment.'
            ];
        }
        $checkStmt->close();
    }
    
    // Proceed with insert
    $statement = $connection->prepare(
        "INSERT INTO bookings (inquiry_id, customer_name, customer_email, customer_phone, 
         event_date, event_type, guest_count, items_json, total_amount, package_id, special_requests, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    if (!$statement) return ['success' => false, 'error' => 'Database prepare failed'];
    
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
    $insertId = $statement->insert_id;
    $statement->close();
    
    if ($saved) {
        return [
            'success' => true,
            'booking_id' => $insertId,
            'message' => 'Booking created successfully'
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to save booking'];
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
    // Get inquiry for booking creation
    $inquiry = getInquiryById($inquiry_id);
    if (!$inquiry) return false;
    
    // Auto-add event_time and event_location columns if they don't exist
    static $eventColumnsChecked2 = false;
    if (!$eventColumnsChecked2) {
        $checkTimeColumn = $connection->query("SHOW COLUMNS FROM bookings LIKE 'event_time'");
        if ($checkTimeColumn && $checkTimeColumn->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN event_time TIME NULL AFTER event_date");
            error_log("approveInquiry: Added event_time column to bookings table");
        }
        $checkLocationColumn = $connection->query("SHOW COLUMNS FROM bookings LIKE 'event_location'");
        if ($checkLocationColumn && $checkLocationColumn->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN event_location TEXT NULL AFTER event_time");
            error_log("approveInquiry: Added event_location column to bookings table");
        }
        $eventColumnsChecked2 = true;
    }
    
    // Update inquiry status to approved
    $update_stmt = $connection->prepare("UPDATE inquiries SET status = 'approved' WHERE id = ? AND status = 'pending'");
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
         event_date, event_time, event_location, event_type, guest_count, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    
    if (!$insert_stmt) return false;
    
    $insert_stmt->bind_param(
        'issssssis',
        $inquiry_id,
        $inquiry['full_name'],
        $inquiry['email'],
        $inquiry['phone'],
        $inquiry['event_date'],
        $inquiry['event_time'],
        $inquiry['event_location'],
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
    
    // Auto-add archived_at column if it doesn't exist (for fresh database imports)
    static $columnChecked = false;
    if (!$columnChecked) {
        $checkColumn = $connection->query("SHOW COLUMNS FROM inquiries LIKE 'archived_at'");
        if ($checkColumn && $checkColumn->num_rows === 0) {
            $connection->query("ALTER TABLE inquiries ADD COLUMN archived_at DATETIME NULL AFTER status");
        }
        $columnChecked = true;
    }
    
    $sql = "SELECT * FROM inquiries WHERE 1=1";
    
    // Filter by archived status - only apply when explicitly set
    if (isset($filters['archived'])) {
        if ($filters['archived'] === true) {
            $sql .= " AND archived_at IS NOT NULL";
        } else {
            $sql .= " AND (archived_at IS NULL OR archived_at = '')";
        }
    }
    
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
 * Get archived bookings (only cancelled ones for the cancelled section)
 */
function getArchivedBookings(): array
{
    return getBookings(['archived' => true, 'status' => 'cancelled']);
}

/**
 * Get archived inquiries
 */
function getArchivedInquiries(): array
{
    return getInquiriesFiltered(['archived' => true]);
}

/**
 * Get archived completed bookings (for Sales Report)
 */
function getArchivedCompletedBookings(): array
{
    return getBookings(['archived' => true, 'status' => 'completed']);
}

/**
 * Get sales report data from archived bookings
 * @param string $dateFrom - Start date (YYYY-MM-DD)
 * @param string $dateTo - End date (YYYY-MM-DD)
 * @return array
 */
function getSalesReport(string $dateFrom = '', string $dateTo = ''): array
{
    $connection = getDbConnection();
    if (!$connection) return [];
    
    // Auto-add archived_at column if it doesn't exist
    static $salesColumnChecked = false;
    if (!$salesColumnChecked) {
        $checkColumn = $connection->query("SHOW COLUMNS FROM bookings LIKE 'archived_at'");
        if ($checkColumn && $checkColumn->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN archived_at DATETIME DEFAULT NULL AFTER status");
            error_log("getSalesReport: Added archived_at column to bookings table");
        }
        $salesColumnChecked = true;
    }
    
    // Also fix any completed bookings that aren't archived
    $connection->query("UPDATE bookings SET archived_at = NOW() WHERE status = 'completed' AND archived_at IS NULL");
    
    $sql = "SELECT 
                id,
                customer_name,
                customer_email,
                total_amount,
                down_payment,
                full_payment,
                payment_status,
                event_date,
                archived_at,
                status
            FROM bookings 
            WHERE archived_at IS NOT NULL 
            AND status = 'completed'";
    
    if (!empty($dateFrom)) {
        $sql .= " AND event_date >= '" . $connection->real_escape_string($dateFrom) . "'";
    }
    if (!empty($dateTo)) {
        $sql .= " AND event_date <= '" . $connection->real_escape_string($dateTo) . "'";
    }
    
    $sql .= " ORDER BY event_date DESC";
    
    $result = $connection->query($sql);
    if (!$result) return [];
    
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    
    // Calculate totals
    $total = 0;
    foreach ($bookings as $booking) {
        $total += (float)($booking['total_amount'] ?? 0);
    }
    
    return [
        'bookings' => $bookings,
        'total_sales' => $total,
        'count' => count($bookings)
    ];
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
function getMenuItem(int $id, bool $includeInactive = false): ?array
{
    $connection = getDbConnection();
    if (!$connection) return null;
    
    $sql = $includeInactive ? "SELECT * FROM menu_items WHERE id = ?" : "SELECT * FROM menu_items WHERE id = ? AND is_active = 1";
    $statement = $connection->prepare($sql);
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
function getPackage(int $id, bool $includeInactive = false): ?array
{
    $connection = getDbConnection();
    if (!$connection) return null;
    
    // Get package details
    $sql = $includeInactive ? "SELECT * FROM packages WHERE id = ?" : "SELECT * FROM packages WHERE id = ? AND is_active = 1";
    $statement = $connection->prepare($sql);
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
    
    $sql = "SELECT date, reason, status, capacity_note FROM unavailable_dates 
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
            WHERE status IN ('confirmed', 'pending', 'completed') 
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
 * Mark date as unavailable with capacity note (admin function)
 * Supports limited availability status with custom message
 */
function blockDateWithNote(string $date, string $reason = '', string $status = 'blocked', string $capacity_note = ''): bool
{
    $connection = getDbConnection();
    if (!$connection) return false;
    
    // Check if capacity_note column exists
    $checkColumn = $connection->query("SHOW COLUMNS FROM unavailable_dates LIKE 'capacity_note'");
    if ($checkColumn->num_rows === 0) {
        $connection->query("ALTER TABLE unavailable_dates ADD COLUMN capacity_note TEXT NULL AFTER status");
    }
    
    // Check if date already exists
    $check = $connection->prepare("SELECT id FROM unavailable_dates WHERE date = ?");
    $check->bind_param('s', $date);
    $check->execute();
    $result = $check->get_result();
    $exists = $result->num_rows > 0;
    $check->close();
    
    if ($exists) {
        // UPDATE existing row
        $stmt = $connection->prepare("UPDATE unavailable_dates SET reason = ?, status = ?, capacity_note = ? WHERE date = ?");
        $stmt->bind_param('ssss', $reason, $status, $capacity_note, $date);
    } else {
        // INSERT new row
        $stmt = $connection->prepare("INSERT INTO unavailable_dates (date, reason, status, capacity_note) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $date, $reason, $status, $capacity_note);
    }
    
    $saved = $stmt->execute();
    $stmt->close();
    
    return $saved;
}

/**
 * Get capacity note for a specific date
 */
function getCapacityNote(string $date): ?string
{
    $connection = getDbConnection();
    if (!$connection) return null;
    
    $statement = $connection->prepare("SELECT capacity_note FROM unavailable_dates WHERE date = ? AND capacity_note IS NOT NULL");
    if (!$statement) return null;
    
    $statement->bind_param('s', $date);
    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $statement->close();
    
    return $row['capacity_note'] ?? null;
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
 * Calculate required downpayment (50% of total amount)
 */
function calculateRequiredDownpayment(float $totalAmount): float
{
    return round($totalAmount * 0.5, 2);
}

/**
 * Generate payment summary HTML for emails
 */
function generatePaymentSummaryHTML(float $totalAmount, float $downPayment, float $fullPayment): string
{
    $totalPaid = $downPayment + $fullPayment;
    $remainingBalance = max(0, $totalAmount - $totalPaid);

    $html = '<div class="payment-summary">';

    if ($totalPaid >= $totalAmount) {
        // Full payment
        $html .= <<<HTML
        <div class="payment-box full-payment">
            <h3>💰 Payment Summary</h3>
            <div class="payment-details">
                <div class="payment-row">
                    <span>Total Amount:</span>
                    <span>₱{$totalAmount}</span>
                </div>
                <div class="payment-row">
                    <span>Paid in Full:</span>
                    <span>₱{$totalPaid}</span>
                </div>
                <div class="payment-row balance-zero">
                    <span>Remaining Balance:</span>
                    <span>₱0.00</span>
                </div>
            </div>
        </div>
        HTML;
    } elseif ($downPayment > 0) {
        // Downpayment made
        $html .= <<<HTML
        <div class="payment-box downpayment">
            <h3>💰 Payment Summary</h3>
            <div class="payment-details">
                <div class="payment-row">
                    <span>Total Amount:</span>
                    <span>₱{$totalAmount}</span>
                </div>
                <div class="payment-row">
                    <span>Downpayment Paid:</span>
                    <span>₱{$downPayment}</span>
                </div>
                <div class="payment-row remaining-balance">
                    <span>Remaining Balance:</span>
                    <span>₱{$remainingBalance}</span>
                </div>
            </div>
            <p class="payment-note">Please settle the remaining balance before your event date.</p>
        </div>
        HTML;
    } else {
        // No payment yet
        $requiredDownpayment = calculateRequiredDownpayment($totalAmount);
        $html .= <<<HTML
        <div class="payment-box pending-payment">
            <h3>💰 Payment Required</h3>
            <div class="payment-details">
                <div class="payment-row">
                    <span>Total Amount:</span>
                    <span>₱{$totalAmount}</span>
                </div>
                <div class="payment-row">
                    <span>Required Downpayment (50%):</span>
                    <span>₱{$requiredDownpayment}</span>
                </div>
            </div>
            <p class="payment-note">A 50% downpayment is required to confirm your booking.</p>
        </div>
        HTML;
    }

    $html .= '</div>';
    return $html;
}

/**
 * Approve inquiry with payment data and create booking
 */
function approveInquiryWithPayment(int $inquiryId, float $downPayment, float $fullPayment, float $totalAmount): bool|string
{
    $connection = getDbConnection();
    if (!$connection) {
        error_log("approveInquiryWithPayment: No database connection");
        return false;
    }
    
    // Auto-add event_time and event_location columns if they don't exist
    static $eventColumnsChecked = false;
    if (!$eventColumnsChecked) {
        $checkTimeColumn = $connection->query("SHOW COLUMNS FROM bookings LIKE 'event_time'");
        if ($checkTimeColumn && $checkTimeColumn->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN event_time TIME NULL AFTER event_date");
            error_log("approveInquiryWithPayment: Added event_time column to bookings table");
        }
        $checkLocationColumn = $connection->query("SHOW COLUMNS FROM bookings LIKE 'event_location'");
        if ($checkLocationColumn && $checkLocationColumn->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN event_location TEXT NULL AFTER event_time");
            error_log("approveInquiryWithPayment: Added event_location column to bookings table");
        }
        $eventColumnsChecked = true;
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
             event_date, event_time, event_location, event_type, guest_count, items_json, total_amount, down_payment, full_payment, 
             payment_status, special_requests, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        
        if (!$bookingStmt) {
            error_log("approveInquiryWithPayment: Prepare failed for booking INSERT: " . $connection->error);
            $connection->rollback();
            return 'Database error: ' . $connection->error;
        }
        
        $bookingStmt->bind_param(
            'issssssisdddsss',
            $inquiryId,
            $inquiry['full_name'],
            $inquiry['email'],
            $inquiry['phone'],
            $inquiry['event_date'],
            $inquiry['event_time'],
            $inquiry['event_location'],
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
            return 'Failed to create booking: ' . $bookingStmt->error;
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
    
    // Auto-add payment columns if they don't exist
    static $paymentColumnsChecked = false;
    if (!$paymentColumnsChecked) {
        $cols = [
            'down_payment' => "ALTER TABLE bookings ADD COLUMN down_payment DECIMAL(10,2) NULL DEFAULT 0 AFTER total_amount",
            'full_payment' => "ALTER TABLE bookings ADD COLUMN full_payment DECIMAL(10,2) NULL DEFAULT 0 AFTER down_payment",
            'payment_status' => "ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(20) NULL DEFAULT 'pending' AFTER full_payment"
        ];
        foreach ($cols as $col => $sql) {
            $check = $connection->query("SHOW COLUMNS FROM bookings LIKE '$col'");
            if ($check && $check->num_rows === 0) {
                $connection->query($sql);
                error_log("updateBookingStatusWithPayment: Added $col column to bookings table");
            }
        }
        $paymentColumnsChecked = true;
    }
    
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
    
    error_log("updateBookingStatusWithPayment: SQL=$sql, types=$types, booking=$bookingId, dp=$downPayment, fp=$fullPayment");
    
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        error_log("updateBookingStatusWithPayment: Prepare failed: " . $connection->error);
        return false;
    }
    
    $stmt->bind_param($types, ...$params);
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("updateBookingStatusWithPayment: Execute failed: " . $stmt->error);
    } else {
        error_log("updateBookingStatusWithPayment: Success - affected rows: " . $stmt->affected_rows);
    }
    
    $stmt->close();
    
    return $result;
}

/**
 * Soft delete a booking with reason tracking
 */
function softDeleteBooking(int $id, int $deletedBy, ?string $reason = null): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    // Get record data for audit log
    $recordData = null;
    $selectStmt = $connection->prepare("SELECT * FROM bookings WHERE id = ? AND (deleted_at IS NULL OR is_active = 1)");
    if ($selectStmt) {
        $selectStmt->bind_param('i', $id);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $record = $result->fetch_assoc();
        if ($record) {
            $recordData = json_encode($record);
        }
        $selectStmt->close();
    }

    $statement = $connection->prepare(
        "UPDATE bookings SET is_active = 0, deleted_at = NOW(), deleted_by = ?, delete_reason = ? WHERE id = ?"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param('isi', $deletedBy, $reason, $id);
    $deleted = $statement->execute();
    $statement->close();

    if ($deleted && $recordData) {
        logDeletion('booking', $id, $recordData, $deletedBy, $reason, 'soft');
    }

    return $deleted;
}

/**
 * Bulk soft delete bookings
 */
function bulkSoftDeleteBookings(array $ids, int $deletedBy, ?string $reason = null): array
{
    $connection = getDbConnection();

    if (!$connection || empty($ids)) {
        return ['success' => false, 'deleted_count' => 0, 'message' => 'Invalid request'];
    }

    $deletedCount = 0;
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $selectSql = "SELECT * FROM bookings WHERE id IN ($placeholders) AND (deleted_at IS NULL OR is_active = 1)";
    $selectStmt = $connection->prepare($selectSql);
    if ($selectStmt) {
        $selectStmt->bind_param($types, ...$ids);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $selectStmt->close();

        $updateSql = "UPDATE bookings SET is_active = 0, deleted_at = NOW(), deleted_by = ?, delete_reason = ? WHERE id IN ($placeholders) AND (deleted_at IS NULL OR is_active = 1)";
        $updateStmt = $connection->prepare($updateSql);
        if ($updateStmt) {
            $params = array_merge([$deletedBy, $reason], $ids);
            $typesWithReason = 'si' . $types;
            $updateStmt->bind_param($typesWithReason, ...$params);
            $updateStmt->execute();
            $deletedCount = $updateStmt->affected_rows;
            $updateStmt->close();

            if ($deletedCount > 0) {
                logDeletion('booking', 0, json_encode($records), $deletedBy, $reason, 'bulk');
            }
        }
    }

    return [
        'success' => $deletedCount > 0,
        'deleted_count' => $deletedCount,
        'message' => $deletedCount > 0 ? "{$deletedCount} record(s) deleted" : 'No records deleted'
    ];
}

/**
 * Restore a soft-deleted booking
 */
function restoreBooking(int $id, int $restoredBy): bool
{
    $connection = getDbConnection();

    if (!$connection) {
        return false;
    }

    $statement = $connection->prepare(
        "UPDATE bookings SET is_active = 1, deleted_at = NULL, deleted_by = NULL, delete_reason = NULL WHERE id = ?"
    );

    if (!$statement) {
        return false;
    }

    $statement->bind_param('i', $id);
    $restored = $statement->execute();
    $statement->close();

    if ($restored) {
        $logStmt = $connection->prepare("UPDATE deleted_records_log SET restored_at = NOW(), restored_by = ? WHERE record_type = 'booking' AND record_id = ? AND restored_at IS NULL");
        if ($logStmt) {
            $logStmt->bind_param('ii', $restoredBy, $id);
            $logStmt->execute();
            $logStmt->close();
        }
    }

    return $restored;
}

/**
 * Permanently delete a booking (for already soft-deleted records)
 */
function permanentlyDeleteBooking(int $id): bool
{
    return hardDeleteBooking($id);
}

function hardDeleteBooking(int $id): bool
{
    $connection = getDbConnection();
    if (!$connection) {
        return false;
    }

    $connection->begin_transaction();

    try {
        if (dbTableExists($connection, 'order_items')) {
            $stmt = $connection->prepare("DELETE FROM order_items WHERE booking_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        if (dbTableExists($connection, 'payments')) {
            $stmt = $connection->prepare("DELETE FROM payments WHERE booking_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $stmt = $connection->prepare("DELETE FROM bookings WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare booking delete: ' . $connection->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$deleted) {
            $connection->rollback();
            return false;
        }

        $connection->commit();
        return true;
    } catch (Throwable $e) {
        $connection->rollback();
        error_log("hardDeleteBooking failed for ID {$id}: " . $e->getMessage());
        return false;
    }
}

function bulkHardDeleteBookings(array $ids): array
{
    $deletedCount = 0;
    foreach ($ids as $id) {
        if (hardDeleteBooking((int)$id)) {
            $deletedCount++;
        }
    }

    return [
        'success' => $deletedCount > 0,
        'deleted_count' => $deletedCount,
        'message' => $deletedCount > 0 ? "{$deletedCount} booking record(s) permanently deleted" : 'No booking records deleted'
    ];
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

// ============================================
// ARC KITCHEN EMAIL NOTIFICATION SYSTEM
// ============================================

require_once __DIR__ . '/mailer_init.php';

/**
 * Main email sending function for Arc Kitchen
 * @param string $to Recipient email address
 * @param string $subject Email subject line
 * @param string $type Email type: 'new_inquiry', 'inquiry_confirmed', 'in_progress', 'completed', 'ready_pickup', 'on_the_way'
 * @param array $data Data array containing relevant information
 * @return array ['success' => bool, 'message' => string]
 */
function sendArcEmail(string $to, string $subject, string $type, array $data): array {
    $mail = initializeArcMailer();
    
    if (!$mail) {
        return ['success' => false, 'message' => 'Failed to initialize mailer'];
    }
    
    try {
        $mail->addAddress($to);
        $mail->Subject = $subject;
        
        // Generate content based on type
        $content = generateEmailContent($type, $data);
        $mail->Body = getArcEmailTemplate($subject, $content);
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $content));
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
        
    } catch (Exception $e) {
        error_log("Arc Kitchen Email Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Mailer Error: ' . $e->getMessage()];
    }
}

/**
 * Generate email content based on notification type
 */
function generateEmailContent(string $type, array $data): string {
    switch ($type) {
        case 'new_inquiry':
            return generateNewInquiryEmail($data);
        case 'inquiry_confirmed':
            return generateInquiryConfirmedEmail($data);
        case 'in_progress':
            return generateInProgressEmail($data);
        case 'completed':
            return generateCompletedEmail($data);
        case 'final_receipt':
            return generateFinalReceiptEmail($data);
        case 'ready_pickup':
            return generateReadyPickupEmail($data);
        case 'on_the_way':
            return generateOnTheWayEmail($data);
        default:
            return '<p>Thank you for choosing Arc Kitchen!</p>';
    }
}

/**
 * Generate New Inquiry email content
 */
function generateNewInquiryEmail(array $data): string {
    $name = escape($data['full_name'] ?? 'Valued Customer');
    $email = escape($data['email'] ?? '');
    $phone = escape($data['phone'] ?? '');
    $eventType = escape($data['event_type'] ?? 'Not specified');
    $eventDate = isset($data['event_date']) ? date('F d, Y', strtotime($data['event_date'])) : 'Not specified';
    $eventTime = escape($data['event_time'] ?? 'Not specified');
    $eventLocation = escape($data['event_location'] ?? 'Not specified');
    $guestCount = (int)($data['guest_count'] ?? 0);
    $message = escape($data['message'] ?? '');
    
    $html = <<<HTML
<h2>Inquiry Received! 📋</h2>
<p>Dear {$name},</p>
<p>Thank you for your interest in Arc Kitchen! We have received your catering inquiry and our team will review your request shortly.</p>

<div class="info-box">
    <strong>📞 Your Contact Information</strong>
    <p style="margin: 0;">Email: {$email}<br>Phone: {$phone}</p>
</div>

<div class="info-box">
    <strong>📅 Event Details</strong>
    <p style="margin: 0;">
        Event Type: {$eventType}<br>
        Event Date: {$eventDate}<br>
        Event Time: {$eventTime}<br>
        Event Location: {$eventLocation}<br>
        Guest Count: {$guestCount} pax
    </p>
</div>
HTML;

    // Add order items if available
    if (!empty($data['items']) && is_array($data['items'])) {
        $html .= generateOrderItemsTable($data['items']);
    }

    // Add payment summary
    $totalAmount = (float)($data['total_amount'] ?? 0);
    $downPayment = (float)($data['down_payment'] ?? 0);
    $fullPayment = (float)($data['full_payment'] ?? 0);
    $html .= generatePaymentSummaryHTML($totalAmount, $downPayment, $fullPayment);

    if ($message) {
        $html .= <<<HTML
<div class="info-box">
    <strong>📝 Special Requests</strong>
    <p style="margin: 0;">{$message}</p>
</div>
HTML;
    }
    
    $inquiryId = $data['inquiry_id'] ?? 'N/A';
    
    $html .= <<<HTML
<div class="divider"></div>
<p>We will contact you within 24-48 hours to discuss your requirements and confirm availability.</p>
<p style="margin-top: 20px;"><strong>Reference Number:</strong> #{$inquiryId}</p>
HTML;

    return $html;
}

/**
 * Generate Inquiry Confirmed email content
 */
function generateInquiryConfirmedEmail(array $data): string {
    $name = escape($data['customer_name'] ?? 'Valued Customer');
    $bookingId = $data['booking_id'] ?? 'N/A';
    $eventDate = isset($data['event_date']) ? date('F d, Y', strtotime($data['event_date'])) : 'Not specified';
    $customerEmail = escape($data['email'] ?? '');
    $customerPhone = escape($data['phone'] ?? '');
    $venue = escape($data['venue'] ?? 'Not specified');

    // Build HTML with order items and payment summary
    $html = <<<HTML
<h2>Your Order is Confirmed! ✅</h2>
<p>Dear {$name},</p>
<p>Great news! Your inquiry has been officially confirmed and upgraded to a booking.</p>

<div class="info-box">
    <strong>📋 Booking Information</strong>
    <p style="margin: 0;">
        <strong>Booking ID:</strong> #{$bookingId}<br>
        <strong>Event Date:</strong> {$eventDate}<br>
        <strong>Status:</strong> <span class="status-badge status-confirmed">Confirmed</span>
    </p>
</div>
HTML;

    // Add order items if available
    if (!empty($data['items']) && is_array($data['items'])) {
        $html .= generateOrderItemsTable($data['items']);
    }

    // Add payment summary
    $totalAmount = (float)($data['total_amount'] ?? 0);
    $downPayment = (float)($data['down_payment'] ?? 0);
    $fullPayment = (float)($data['full_payment'] ?? 0);
    $html .= generatePaymentSummaryHTML($totalAmount, $downPayment, $fullPayment);

    $html .= <<<HTML

<p>Your event is now locked in our calendar. Our team will begin preparing for your special occasion.</p>

<div class="divider"></div>
<p>If you need to make any changes to your booking, please contact us as soon as possible.</p>
HTML;

    return $html;
}

/**
 * Generate In-Progress email content with ETA
 */
function generateInProgressEmail(array $data): string {
    $name = escape($data['customer_name'] ?? 'Valued Customer');
    $eta = escape($data['eta'] ?? '');
    $bookingId = $data['booking_id'] ?? 'N/A';
    
    $etaHtml = '';
    if ($eta) {
        $etaHtml = <<<HTML
<div class="eta-box">
    <h3>⏰ Estimated Completion Time</h3>
    <p class="time">{$eta}</p>
</div>
HTML;
    }
    
    return <<<HTML
<h2>Your Order is Almost Done! 👨‍🍳</h2>
<p>Dear {$name},</p>
<p>Exciting update! Your catering order is now being prepared by our culinary team.</p>

<div class="info-box">
    <strong>📋 Order Status</strong>
    <p style="margin: 0;">
        <strong>Booking ID:</strong> #{$bookingId}<br>
        <strong>Current Status:</strong> <span class="status-badge status-inprogress">In Progress</span>
    </p>
</div>

{$etaHtml}

<div class="divider"></div>
<p>Our chefs are working hard to prepare delicious dishes for your event. We'll notify you when everything is ready!</p>
HTML;
}

/**
 * Generate Completed email content
 */
function generateCompletedEmail(array $data): string {
    $name = escape($data['customer_name'] ?? 'Valued Customer');
    $bookingId = $data['booking_id'] ?? 'N/A';
    
    return <<<HTML
<h2>Order Complete! 🎉</h2>
<p>Dear {$name},</p>
<p>Your catering order has been successfully completed!</p>

<div class="info-box">
    <strong>📋 Final Status</strong>
    <p style="margin: 0;">
        <strong>Booking ID:</strong> #{$bookingId}<br>
        <strong>Status:</strong> <span class="status-badge status-complete">Completed</span>
    </p>
</div>

<p>We hope you enjoyed our catering services. Thank you for choosing Arc Kitchen for your special event!</p>

<div class="divider"></div>
<p style="text-align: center; font-size: 16px;"><strong>Thank you for your trust in Arc Kitchen! 🍽️</strong></p>
HTML;
}

function tableHasColumn(mysqli $connection, string $table, string $column): bool
{
    $stmt = $connection->prepare("
        SELECT COUNT(*) AS column_count
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int)($row['column_count'] ?? 0) > 0;
}

function getBookingPaymentReceiptData(int $bookingId, float $amountPaidNow = 0): ?array
{
    $connection = getDbConnection();
    if (!$connection) {
        return null;
    }

    $bookingEventTime = tableHasColumn($connection, 'bookings', 'event_time') ? "b.event_time" : "NULL";
    $inquiryEventTime = tableHasColumn($connection, 'inquiries', 'event_time') ? "i.event_time" : "NULL";
    $bookingEventLocation = tableHasColumn($connection, 'bookings', 'event_location') ? "NULLIF(b.event_location, '')" : "NULL";
    $inquiryEventLocation = tableHasColumn($connection, 'inquiries', 'event_location') ? "NULLIF(i.event_location, '')" : "NULL";
    $bookingZip = tableHasColumn($connection, 'bookings', 'zip_code') ? "NULLIF(b.zip_code, '')" : "NULL";
    $inquiryZip = tableHasColumn($connection, 'inquiries', 'zip_code') ? "NULLIF(i.zip_code, '')" : "NULL";
    $bookingStreet = tableHasColumn($connection, 'bookings', 'street_address') ? "NULLIF(b.street_address, '')" : "NULL";
    $inquiryStreet = tableHasColumn($connection, 'inquiries', 'street_address') ? "NULLIF(i.street_address, '')" : "NULL";
    $bookingCity = tableHasColumn($connection, 'bookings', 'city') ? "NULLIF(b.city, '')" : "NULL";
    $inquiryCity = tableHasColumn($connection, 'inquiries', 'city') ? "NULLIF(i.city, '')" : "NULL";
    $bookingProvince = tableHasColumn($connection, 'bookings', 'province') ? "NULLIF(b.province, '')" : "NULL";
    $inquiryProvince = tableHasColumn($connection, 'inquiries', 'province') ? "NULLIF(i.province, '')" : "NULL";
    $bookingLandmarks = tableHasColumn($connection, 'bookings', 'landmarks') ? "NULLIF(b.landmarks, '')" : "NULL";
    $inquiryLandmarks = tableHasColumn($connection, 'inquiries', 'landmarks') ? "NULLIF(i.landmarks, '')" : "NULL";
    $bookingDeliveryTime = tableHasColumn($connection, 'bookings', 'delivery_time') ? "b.delivery_time" : "NULL";
    $inquiryDeliveryTime = tableHasColumn($connection, 'inquiries', 'delivery_time') ? "i.delivery_time" : "NULL";

    $sql = "
        SELECT
            b.id AS booking_id,
            b.inquiry_id,
            COALESCE(NULLIF(b.customer_name, ''), i.full_name) AS customer_name,
            b.customer_email,
            COALESCE(b.event_date, i.event_date) AS event_date,
            COALESCE({$bookingEventTime}, {$inquiryEventTime}) AS event_time,
            COALESCE({$bookingDeliveryTime}, {$inquiryDeliveryTime}) AS delivery_time,
            COALESCE({$bookingEventLocation}, {$inquiryEventLocation}) AS event_location,
            COALESCE({$bookingStreet}, {$inquiryStreet}) AS street_address,
            COALESCE({$bookingCity}, {$inquiryCity}) AS city,
            COALESCE({$bookingProvince}, {$inquiryProvince}) AS province,
            COALESCE({$bookingZip}, {$inquiryZip}) AS zip_code,
            COALESCE({$bookingLandmarks}, {$inquiryLandmarks}) AS landmarks,
            COALESCE(NULLIF(b.total_amount, 0), i.total_amount, 0) AS total_price,
            COALESCE(b.down_payment, 0) AS down_payment,
            COALESCE(b.full_payment, 0) AS full_payment,
            COALESCE(NULLIF(b.items_json, ''), i.items_json) AS items_json
        FROM bookings b
        LEFT JOIN inquiries i ON i.id = b.inquiry_id
        WHERE b.id = ?
        LIMIT 1
    ";

    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        error_log("getBookingPaymentReceiptData: Prepare failed: " . $connection->error);
        return null;
    }

    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $receipt = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$receipt) {
        return null;
    }

    $items = [];
    if (!empty($receipt['items_json'])) {
        $decoded = json_decode($receipt['items_json'], true);
        $items = is_array($decoded) ? $decoded : [];
    }

    if (!$items && !empty($receipt['inquiry_id'])) {
        $items = getInquiryItems((int)$receipt['inquiry_id']);
    }

    $totalPrice = (float)($receipt['total_price'] ?? 0);
    $downPayment = (float)($receipt['down_payment'] ?? 0);
    $fullPayment = (float)($receipt['full_payment'] ?? 0);
    $totalPaid = $downPayment + $fullPayment;
    $remainingBalance = max(0, $totalPrice - $totalPaid);

    $receipt['items'] = $items;
    $receipt['total_amount'] = $totalPrice;
    $receipt['total_paid'] = $totalPaid;
    $receipt['amount_paid_now'] = $amountPaidNow > 0 ? $amountPaidNow : $totalPaid;
    $receipt['remaining_balance'] = $remainingBalance;
    $receipt['payment_type'] = $remainingBalance > 0 ? 'Downpayment' : 'Full Payment';

    return $receipt;
}

/**
 * Generate Final Receipt email content
 */
function generateLegacyFinalReceiptEmail(array $data): string {
    $name = escape($data['customer_name'] ?? 'Valued Customer');
    $bookingId = $data['booking_id'] ?? 'N/A';
    $eventDate = isset($data['event_date']) ? date('F d, Y', strtotime($data['event_date'])) : 'N/A';
    $eventTime = escape($data['event_time'] ?? 'N/A');
    $deliveryTime = escape($data['delivery_time'] ?? $eventTime);
    $eventLocation = escape($data['event_location'] ?? 'N/A');
    
    // Build structured address
    $streetAddress = escape($data['street_address'] ?? '');
    $city = escape($data['city'] ?? '');
    $province = escape($data['province'] ?? '');
    $zipCode = escape($data['zip_code'] ?? '');
    $landmarks = escape($data['landmarks'] ?? '');
    
    if ($streetAddress && $city) {
        $eventLocation = $streetAddress . ', ' . $city;
        if ($province) $eventLocation .= ', ' . $province;
        if ($zipCode) $eventLocation .= ' ' . $zipCode;
        if ($landmarks) $eventLocation .= ' (Near: ' . $landmarks . ')';
    }
    
    $totalAmount = number_format((float)($data['total_amount'] ?? 0), 2);
    $totalPaid = number_format((float)($data['total_paid'] ?? 0), 2);
    // Handle items data - could be array or JSON string
    $items = $data['items'] ?? [];
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = [];
        }
    }
    if (!is_array($items)) {
        $items = [];
    }
    
    // Generate items table
    $itemsHtml = '';
    foreach ($items as $item) {
        $itemName = escape($item['name'] ?? $item['product_name'] ?? 'Unknown Item');
        $qty = (int)($item['quantity'] ?? 1);
        $price = number_format((float)($item['unit_price'] ?? $item['product_price'] ?? 0), 2);
        $subtotal = number_format($qty * (float)str_replace(',', '', $price), 2);
        $itemsHtml .= <<<ITEM
        <tr style="border-bottom: 1px solid #e5d5c5;">
            <td style="padding: 12px; text-align: left;">{$itemName}</td>
            <td style="padding: 12px; text-align: center;">{$qty}</td>
            <td style="padding: 12px; text-align: right;">₱{$price}</td>
            <td style="padding: 12px; text-align: right;">₱{$subtotal}</td>
        </tr>
ITEM;
    }
    
    return <<<HTML
<div style="background: #fffdf8; border-radius: 25px; padding: 2rem; max-width: 600px; margin: 0 auto; font-family: 'League Spartan', sans-serif;">
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #4a1414 0%, #6c1d12 100%); border-radius: 20px; padding: 2rem; text-align: center; margin-bottom: 2rem;">
        <h1 style="color: #fff; margin: 0; font-size: 1.8rem; font-weight: 700;">🧾 Your ARC Kitchen Receipt</h1>
        <p style="color: #e5d5c5; margin: 0.5rem 0 0 0; font-size: 1rem;">Order #{$bookingId}</p>
    </div>
    
    <!-- Thank You Message -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="color: #4a1414; margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 600;">Thank you, {$name}!</h2>
        <p style="color: #666; margin: 0; font-size: 1rem; line-height: 1.6; font-style: italic;">
            We hope you enjoyed our artisanal catering. It was our pleasure serving your special event!
        </p>
    </div>
    
    <!-- Event Details -->
    <div style="background: #faf5f0; border-radius: 15px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="color: #4a1414; margin: 0 0 1rem 0; font-size: 1.1rem;">📅 Event Details</h3>
        <p style="margin: 0.25rem 0; color: #333;"><strong>Date:</strong> {$eventDate}</p>
        <p style="margin: 0.25rem 0; color: #333;"><strong>Event Time:</strong> {$eventTime}</p>
        <p style="margin: 0.25rem 0; color: #333;"><strong>Delivery Time:</strong> {$deliveryTime}</p>
        <p style="margin: 0.25rem 0; color: #333;"><strong>Location:</strong> {$eventLocation}</p>
    </div>
    
    <!-- Order Items -->
    <div style="margin-bottom: 2rem;">
        <h3 style="color: #4a1414; margin: 0 0 1rem 0; font-size: 1.1rem;">🍽️ Order Summary</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
            <thead>
                <tr style="background: #4a1414; color: #fff;">
                    <th style="padding: 12px; text-align: left; border-radius: 10px 0 0 0;">Item</th>
                    <th style="padding: 12px; text-align: center;">Qty</th>
                    <th style="padding: 12px; text-align: right;">Price</th>
                    <th style="padding: 12px; text-align: right; border-radius: 0 10px 0 0;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
        </table>
    </div>
    
    <!-- Financial Summary -->
    <div style="background: #4a1414; border-radius: 15px; padding: 1.5rem; color: #fff; margin-bottom: 2rem;">
        <h3 style="color: #fff; margin: 0 0 1rem 0; font-size: 1.1rem;">💰 Payment Summary</h3>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span>Total Amount:</span>
            <span style="font-weight: 600;">₱{$totalAmount}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span>Total Collected:</span>
            <span style="font-weight: 600; color: #90EE90;">₱{$totalPaid}</span>
        </div>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.3);">
            <span style="background: #8a2927; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                ✅ STATUS: FULLY PAID
            </span>
        </div>
    </div>
    
    <!-- Footer -->
    <div style="text-align: center; padding-top: 1rem; border-top: 2px solid #e5d5c5;">
        <p style="color: #4a1414; font-size: 1.1rem; font-weight: 600; margin: 0 0 0.5rem 0; font-family: Georgia, serif; font-style: italic;">
            "Crafted with care, served with love"
        </p>
        <p style="color: #666; margin: 0; font-size: 0.9rem;">
            The ARC Kitchen Family
        </p>
    </div>
</div>
HTML;
}

function generateFinalReceiptEmail(array $data): string {
    $name = escape($data['customer_name'] ?? 'Valued Customer');
    $bookingId = $data['booking_id'] ?? 'N/A';
    $eventDate = isset($data['event_date']) ? date('F d, Y', strtotime($data['event_date'])) : 'N/A';
    $eventTimeRaw = $data['event_time'] ?? '';
    $eventTime = $eventTimeRaw ? date('g:i A', strtotime($eventTimeRaw)) : 'N/A';
    $eventLocation = escape($data['event_location'] ?? 'N/A');

    $streetAddress = escape($data['street_address'] ?? '');
    $city = escape($data['city'] ?? '');
    $province = escape($data['province'] ?? '');
    $zipCode = escape($data['zip_code'] ?? '');
    $landmarks = escape($data['landmarks'] ?? '');

    if ($streetAddress && $city) {
        $eventLocation = $streetAddress . ', ' . $city;
        if ($province) $eventLocation .= ', ' . $province;
        if ($zipCode) $eventLocation .= ' ' . $zipCode;
        if ($landmarks) $eventLocation .= ' (Near: ' . $landmarks . ')';
    } elseif ($zipCode && strpos($eventLocation, $zipCode) === false) {
        $eventLocation .= ' ' . $zipCode;
    }

    $totalAmountValue = (float)($data['total_amount'] ?? $data['total_price'] ?? 0);
    $amountPaidNowValue = (float)($data['amount_paid_now'] ?? $data['total_paid'] ?? 0);
    $remainingBalanceValue = (float)($data['remaining_balance'] ?? max(0, $totalAmountValue - (float)($data['total_paid'] ?? 0)));
    $paymentType = escape($data['payment_type'] ?? ($remainingBalanceValue > 0 ? 'Downpayment' : 'Full Payment'));

    $totalAmount = number_format($totalAmountValue, 2);
    $amountPaidNow = number_format($amountPaidNowValue, 2);
    $remainingBalance = number_format($remainingBalanceValue, 2);
    $remainingStyle = $remainingBalanceValue > 0 ? 'font-weight: 700; color: #8a2927;' : 'font-weight: 600; color: #2f6f3e;';

    $items = $data['items'] ?? [];
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        $items = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
    if (!is_array($items)) {
        $items = [];
    }

    $itemsHtml = '';
    foreach ($items as $item) {
        $itemName = escape($item['name'] ?? $item['product_name'] ?? 'Unknown Item');
        $qty = (int)($item['quantity'] ?? 1);
        $unitPriceValue = (float)($item['unit_price'] ?? $item['product_price'] ?? $item['price'] ?? 0);
        $subtotalValue = (float)($item['subtotal'] ?? $item['total_price'] ?? ($qty * $unitPriceValue));
        $price = number_format($unitPriceValue, 2);
        $subtotal = number_format($subtotalValue, 2);
        $itemsHtml .= <<<ITEM
        <tr>
            <td style="padding: 10px 8px; border-bottom: 1px solid #eeeeee; color: #333333;">{$itemName}</td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #eeeeee; color: #333333; text-align: center;">{$qty}</td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #eeeeee; color: #333333; text-align: right;">&#8369;{$price}</td>
            <td style="padding: 10px 8px; border-bottom: 1px solid #eeeeee; color: #333333; text-align: right;">&#8369;{$subtotal}</td>
        </tr>
ITEM;
    }

    if ($itemsHtml === '') {
        $itemsHtml = '<tr><td colspan="4" style="padding: 10px 8px; color: #666666; text-align: center;">No order items found for this booking.</td></tr>';
    }

    return <<<HTML
<div style="max-width: 620px; margin: 0 auto; background: #ffffff; border: 1px solid #dddddd; border-top: 5px solid #8a2927; font-family: Arial, Helvetica, sans-serif;">
    <div style="padding: 24px 24px 12px 24px;">
        <h1 style="margin: 0; color: #4a1414; font-size: 24px; line-height: 1.3;">Payment Receipt - ARC Kitchen</h1>
        <p style="margin: 8px 0 0 0; color: #555555; font-size: 14px;">Booking #{$bookingId} | {$paymentType}</p>
    </div>

    <div style="padding: 12px 24px;">
        <p style="margin: 0 0 12px 0; color: #333333; font-size: 15px;">Dear {$name},</p>
        <table style="width: 100%; border-collapse: collapse; background: #fafafa; border: 1px solid #eeeeee;">
            <tr>
                <td colspan="2" style="padding: 12px; color: #4a1414; font-weight: 700; border-bottom: 1px solid #eeeeee;">Event Details</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; color: #666666; width: 110px;">Date</td>
                <td style="padding: 8px 12px; color: #333333;">{$eventDate}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; color: #666666;">Time</td>
                <td style="padding: 8px 12px; color: #333333;">{$eventTime}</td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; color: #666666;">Address</td>
                <td style="padding: 8px 12px; color: #333333;">{$eventLocation}</td>
            </tr>
        </table>
    </div>

    <div style="padding: 12px 24px;">
        <h2 style="margin: 0 0 10px 0; color: #4a1414; font-size: 18px;">Order Summary</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; border: 1px solid #eeeeee;">
            <thead>
                <tr style="background: #8a2927;">
                    <th style="padding: 10px 8px; text-align: left; color: #ffffff;">Item</th>
                    <th style="padding: 10px 8px; text-align: center; color: #ffffff;">Qty</th>
                    <th style="padding: 10px 8px; text-align: right; color: #ffffff;">Price</th>
                    <th style="padding: 10px 8px; text-align: right; color: #ffffff;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
        </table>
    </div>

    <div style="padding: 12px 24px 24px 24px;">
        <h2 style="margin: 0 0 10px 0; color: #4a1414; font-size: 18px;">Payment Summary</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 15px; border: 1px solid #eeeeee;">
            <tr>
                <td style="padding: 10px 12px; color: #555555; border-bottom: 1px solid #eeeeee;">Total Order Value</td>
                <td style="padding: 10px 12px; color: #333333; text-align: right; border-bottom: 1px solid #eeeeee;">&#8369;{$totalAmount}</td>
            </tr>
            <tr>
                <td style="padding: 10px 12px; color: #555555; border-bottom: 1px solid #eeeeee;"><strong>Amount Paid Now</strong></td>
                <td style="padding: 10px 12px; color: #333333; text-align: right; border-bottom: 1px solid #eeeeee;"><strong>&#8369;{$amountPaidNow}</strong></td>
            </tr>
            <tr>
                <td style="padding: 10px 12px; color: #555555;">Remaining Balance</td>
                <td style="padding: 10px 12px; text-align: right; {$remainingStyle}">&#8369;{$remainingBalance}</td>
            </tr>
        </table>
    </div>
</div>
HTML;
}

/**
 * Generate Ready for Pickup email content
 */
function generateReadyPickupEmail(array $data): string {
    $name = escape($data['customer_name'] ?? 'Valued Customer');
    $bookingId = $data['booking_id'] ?? 'N/A';
    
    return <<<HTML
<h2>Ready for Pickup! 📦</h2>
<p>Dear {$name},</p>
<p>Your catering order is ready and waiting for you at our kitchen!</p>

<div class="info-box">
    <strong>📋 Pickup Information</strong>
    <p style="margin: 0;">
        <strong>Booking ID:</strong> #{$bookingId}<br>
        <strong>Status:</strong> <span class="status-badge status-ready">Ready for Pickup</span><br>
        <strong>Location:</strong> Arc Kitchen Main Branch
    </p>
</div>

<p>Please come to our location to collect your order. Don't forget to bring your reference number!</p>

<div class="divider"></div>
<p><strong>Pickup Hours:</strong> Monday-Sunday, 8:00 AM - 8:00 PM</p>
HTML;
}

/**
 * Generate On The Way email content
 */
function generateOnTheWayEmail(array $data): string {
    $name = escape($data['customer_name'] ?? 'Valued Customer');
    $bookingId = $data['booking_id'] ?? 'N/A';
    $venue = escape($data['venue'] ?? 'Your venue');
    
    return <<<HTML
<h2>We're On The Way! 🚚</h2>
<p>Dear {$name},</p>
<p>Our catering team has departed and is en route to your venue!</p>

<div class="info-box">
    <strong>📋 Delivery Information</strong>
    <p style="margin: 0;">
        <strong>Booking ID:</strong> #{$bookingId}<br>
        <strong>Status:</strong> <span class="status-badge status-inprogress">On The Way</span><br>
        <strong>Destination:</strong> {$venue}
    </p>
</div>

<p>Our team will arrive shortly to set up everything for your event. Please ensure someone is available to receive the delivery.</p>

<div class="divider"></div>
<p>See you soon! 👋</p>
HTML;
}

/**
 * Generate order items HTML table
 */
function generateOrderItemsTable(array $items): string {
    $html = <<<HTML
<div class="divider"></div>
<h3>🍽️ Your Order Items</h3>
<table class="order-table">
    <thead>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
HTML;

    $total = 0;
    foreach ($items as $item) {
        $itemName = escape($item['name'] ?? $item['product_name'] ?? 'Unknown Item');
        $qty = (int)($item['quantity'] ?? 1);
        $price = (float)($item['unit_price'] ?? $item['product_price'] ?? 0);
        $subtotal = $qty * $price;
        $total += $subtotal;
        
        $html .= <<<HTML
        <tr>
            <td>{$itemName}</td>
            <td>{$qty}</td>
            <td>₱{$price}</td>
            <td>₱{$subtotal}</td>
        </tr>
HTML;
    }
    
    $html .= <<<HTML
        <tr class="total-row">
            <td colspan="3"><strong>Total Amount</strong></td>
            <td><strong>₱{$total}</strong></td>
        </tr>
    </tbody>
</table>
HTML;

    return $html;
}

/**
 * Send notification to customer (wrapper function for admin use)
 * @param string $type Notification type
 * @param int $bookingId Booking ID
 * @param array $extraData Additional data (eta, etc.)
 * @return array Result with success status
 */
function sendCustomerNotification(string $type, int $bookingId, array $extraData = []): array {
    // Get booking details
    $booking = getBookingById($bookingId);
    if (!$booking) {
        return ['success' => false, 'message' => 'Booking not found'];
    }
    
    $customerEmail = $booking['customer_email'] ?? '';
    if (!$customerEmail) {
        return ['success' => false, 'message' => 'Customer email not found'];
    }
    
    $amountPaidNow = (float)($extraData['amount_paid_now'] ?? 0);
    $data = [
        'customer_name' => $booking['customer_name'],
        'booking_id' => $bookingId,
        'event_date' => $booking['event_date'],
        'venue' => $booking['venue'] ?? '',
    ];

    if ($type === 'final_receipt') {
        $receiptData = getBookingPaymentReceiptData($bookingId, $amountPaidNow);
        if (!$receiptData) {
            return ['success' => false, 'message' => 'Unable to fetch receipt details'];
        }
        $data = $receiptData;
    }
    
    // Merge with extra data
    $data = array_merge($data, $extraData);
    
    // Define subject and email type
    $subjects = [
        'new_inquiry' => 'Inquiry Received! - Arc Kitchen',
        'inquiry_confirmed' => 'Your Order is Confirmed! - Arc Kitchen',
        'in_progress' => 'Your Order is Almost Done! - Arc Kitchen',
        'completed' => 'Order Complete! - Arc Kitchen',
        'final_receipt' => 'Payment Receipt - ARC Kitchen',
        'ready_pickup' => 'Ready for Pickup! - Arc Kitchen',
        'on_the_way' => "We're On The Way! - Arc Kitchen",
    ];
    
    $subject = $subjects[$type] ?? 'Arc Kitchen Notification';
    
    return sendArcEmail($customerEmail, $subject, $type, $data);
}



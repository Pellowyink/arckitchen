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
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
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


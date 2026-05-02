<?php
/**
 * API: Archive Item
 * Archives bookings or inquiries by setting archived flag and timestamp
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id']) || !isset($data['type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id = (int)$data['id'];
$type = $data['type']; // 'booking' or 'inquiry'

$connection = getDbConnection();
if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    if ($type === 'booking') {
        // Check if archived_at column exists, if not add it
        $checkColumn = $connection->query("SHOW COLUMNS FROM bookings LIKE 'archived_at'");
        if ($checkColumn->num_rows === 0) {
            $connection->query("ALTER TABLE bookings ADD COLUMN archived_at DATETIME NULL");
        }
        
        // Archive the booking
        $stmt = $connection->prepare("UPDATE bookings SET archived_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Booking archived successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking not found or already archived']);
        }
        $stmt->close();
        
    } elseif ($type === 'inquiry') {
        // Check if archived_at column exists, if not add it
        $checkColumn = $connection->query("SHOW COLUMNS FROM inquiries LIKE 'archived_at'");
        if ($checkColumn->num_rows === 0) {
            $connection->query("ALTER TABLE inquiries ADD COLUMN archived_at DATETIME NULL");
        }
        
        // Archive the inquiry
        $stmt = $connection->prepare("UPDATE inquiries SET archived_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Inquiry archived successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Inquiry not found or already archived']);
        }
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$connection->close();

<?php
/**
 * Test script to verify Limited Availability is working
 */

require_once __DIR__ . '/includes/functions.php';

echo "<h1>Testing Limited Availability Feature</h1>";

// 1. Check database connection
$conn = getDbConnection();
if (!$conn) {
    die("<p style='color:red'>Database connection failed!</p>");
}
echo "<p style='color:green'>✓ Database connected</p>";

// 2. Check if capacity_note column exists
$result = $conn->query("SHOW COLUMNS FROM unavailable_dates LIKE 'capacity_note'");
if ($result->num_rows > 0) {
    echo "<p style='color:green'>✓ capacity_note column exists</p>";
} else {
    echo "<p style='color:red'>✗ capacity_note column NOT found - running setup...</p>";
    $conn->query("ALTER TABLE unavailable_dates ADD COLUMN capacity_note TEXT NULL AFTER status");
    echo "<p style='color:orange'>→ Added capacity_note column</p>";
}

// 3. Check current month unavailable dates
$month = date('m');
$year = date('Y');
$dates = getUnavailableDates($month, $year);

echo "<h2>Unavailable Dates for " . date('F Y') . "</h2>";
if (empty($dates)) {
    echo "<p>No unavailable dates set yet.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Date</th><th>Status</th><th>Reason</th><th>Capacity Note</th></tr>";
    foreach ($dates as $d) {
        echo "<tr>";
        echo "<td>" . $d['date'] . "</td>";
        echo "<td><strong>" . $d['status'] . "</strong></td>";
        echo "<td>" . ($d['reason'] ?? '-') . "</td>";
        echo "<td>" . ($d['capacity_note'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Test form to add a limited date
echo "<h2>Quick Test: Add Limited Date</h2>";
echo "<form method='post'>";
echo "<p>Date: <input type='date' name='date' required min='" . date('Y-m-d') . "'></p>";
echo "<p>Capacity Message: <input type='text' name='capacity_note' placeholder='Only 1 slot left' required></p>";
echo "<p><button type='submit' name='action' value='test_limited'>Set Limited</button></p>";
echo "</form>";

// 5. Handle test form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'test_limited') {
    $date = $_POST['date'];
    $note = $_POST['capacity_note'];
    $status = 'limited';
    
    // Use reason as capacity_note for simplicity
    $stmt = $conn->prepare("INSERT INTO unavailable_dates (date, reason, status, capacity_note) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE reason = ?, status = ?, capacity_note = ?");
    $stmt->bind_param('sssssss', $date, $note, $status, $note, $note, $status, $note);
    
    if ($stmt->execute()) {
        echo "<p style='color:green'>✓ Successfully set $date as LIMITED with message: $note</p>";
        echo "<p><a href='test-limited.php'>Refresh page</a> to see it in the table above</p>";
    } else {
        echo "<p style='color:red'>✗ Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

echo "<h2>Next Steps</h2>";
echo "<p>1. Run this test to add a limited date</p>";
echo "<p>2. Go to <a href='inquiry.php'>inquiry.php</a> to see the honey-gold color</p>";
echo "<p>3. Go to <a href='admin/calendar.php'>admin/calendar.php</a> to manage dates</p>";

$conn->close();

<?php
/**
 * AJAX Endpoint for Filtering Bookings
 * Handles: live search, status filtering, date range, package filter, sorting
 * Returns: JSON with HTML table rows
 * Security: Requires admin session
 */

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Collect filter parameters
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
    'package_id' => trim($_GET['package_id'] ?? ''),
    'sort' => trim($_GET['sort'] ?? 'desc'),
];

// Get filtered bookings
$bookings = getBookings($filters);

// Apply sorting
if ($filters['sort'] === 'asc') {
    usort($bookings, function ($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
}

// Build HTML response
$html = '';

if (empty($bookings)) {
    $html = '<tr><td colspan="8" class="empty-cell">No bookings match your filters.</td></tr>';
} else {
    foreach ($bookings as $booking) {
        $status_class = strtolower(str_replace(' ', '-', $booking['status']));
        $status = strtolower($booking['status']);
        
        // Build action buttons based on status
        $actions = '<button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(' . (int)$booking['id'] . ', \'booking\')">Edit</button>';
        
        // Show appropriate actions based on current status
        if ($status === 'pending') {
            $actions .= ' <button class="btn-admin btn-primary-admin btn-small" onclick="updateBookingStatus(' . (int)$booking['id'] . ', \'confirmed\')">Confirm</button>';
            $actions .= ' <button class="btn-admin btn-danger-admin btn-small" onclick="updateBookingStatus(' . (int)$booking['id'] . ', \'cancelled\')">Cancel</button>';
        } elseif ($status === 'confirmed') {
            $actions .= ' <button class="btn-admin btn-success-admin btn-small" onclick="updateBookingStatus(' . (int)$booking['id'] . ', \'completed\')">Complete</button>';
            $actions .= ' <button class="btn-admin btn-danger-admin btn-small" onclick="updateBookingStatus(' . (int)$booking['id'] . ', \'cancelled\')">Cancel</button>';
        } elseif ($status === 'completed') {
            $actions .= ' <span class="badge badge-success">✓ Done</span>';
        } elseif ($status === 'cancelled') {
            $actions .= ' <span class="badge badge-danger">Cancelled</span>';
        }
        
        $html .= sprintf(
            '<tr id="booking-%d" class="booking-row" data-booking-id="%d">
                <td><strong>%s</strong></td>
                <td>%s</td>
                <td>%d pax</td>
                <td>₱%s</td>
                <td>%s</td>
                <td>%s</td>
                <td><span class="badge badge-%s">%s</span></td>
                <td>
                    <div class="action-buttons">%s</div>
                </td>
            </tr>',
            (int)$booking['id'],
            (int)$booking['id'],
            escape($booking['customer_name']),
            escape($booking['customer_email']),
            (int)$booking['guest_count'],
            number_format((float)$booking['total_amount'], 2),
            date('M d, Y', strtotime($booking['event_date'])),
            escape($booking['special_requests'] ?? '—'),
            $status_class,
            escape($booking['status']),
            $actions
        );
    }
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => count($bookings),
    'html' => $html,
    'filters_applied' => array_filter($filters),
]);

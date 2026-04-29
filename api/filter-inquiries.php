<?php
/**
 * AJAX Endpoint for Filtering Inquiries
 * Handles: live search, status filtering, date range, package filter, sorting
 * Returns: HTML table rows
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

// Get filtered inquiries
$inquiries = getInquiriesFiltered($filters);

// Apply sorting
if ($filters['sort'] === 'asc') {
    usort($inquiries, function ($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
}

// Build HTML response
$html = '';

if (empty($inquiries)) {
    $html = '<tr><td colspan="8" class="empty-cell">No inquiries match your filters.</td></tr>';
} else {
    foreach ($inquiries as $inquiry) {
        $status_class = strtolower(str_replace(' ', '-', $inquiry['status']));
        $status = strtolower($inquiry['status']);
        
        // Build action buttons based on status
        $actions = '<button class="btn-admin btn-secondary-admin btn-small" onclick="openEditModal(' . (int)$inquiry['id'] . ', \'inquiry\')">Edit</button>';
        
        // Only show approve/reject for pending inquiries
        if ($status === 'pending') {
            $actions .= ' <button class="btn-admin btn-primary-admin btn-small" onclick="approveInquiry(' . (int)$inquiry['id'] . ')">Approve</button>';
            $actions .= ' <button class="btn-admin btn-danger-admin btn-small" onclick="rejectInquiry(' . (int)$inquiry['id'] . ')">Reject</button>';
        } elseif ($status === 'approved') {
            $actions .= ' <span class="badge badge-success">Approved → Booking</span>';
        } elseif ($status === 'rejected') {
            $actions .= ' <span class="badge badge-danger">Rejected</span>';
        }
        
        $html .= sprintf(
            '<tr id="inquiry-%d" class="inquiry-row" data-inquiry-id="%d">
                <td><strong>%s</strong></td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%d pax</td>
                <td>%s</td>
                <td><span class="badge badge-%s">%s</span></td>
                <td>
                    <div class="action-buttons">%s</div>
                </td>
            </tr>',
            (int)$inquiry['id'],
            (int)$inquiry['id'],
            escape($inquiry['full_name']),
            escape($inquiry['email']),
            escape($inquiry['phone']),
            date('M d, Y', strtotime($inquiry['event_date'])),
            (int)$inquiry['guest_count'],
            date('M d', strtotime($inquiry['created_at'])),
            $status_class,
            escape($inquiry['status']),
            $actions
        );
    }
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => count($inquiries),
    'html' => $html,
    'filters_applied' => array_filter($filters),
]);

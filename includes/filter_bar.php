<!-- Filter Bar Component for Inquiries/Bookings
     Include this in both inquiries.php and bookings.php for consistent filtering
     Usage: <?php $data_type = 'inquiries'; // or 'bookings' ?>
            <?php require_once __DIR__ . '/../includes/filter_bar.php'; ?>
-->

<?php
// Default status based on data type
$default_status = ($data_type === 'inquiries') ? 'pending' : 'confirmed';
?>

<div class="filter-bar">
    <div class="filter-section">
        <!-- Live Search -->
        <div class="filter-group">
            <label for="search-input" class="filter-label">🔍 Search</label>
            <input 
                type="text" 
                id="search-input" 
                class="filter-input" 
                placeholder="Customer name or email..."
                data-filter-type="search"
            >
        </div>

        <!-- Status Filter -->
        <div class="filter-group">
            <label for="status-filter" class="filter-label">📊 Status</label>
            <select id="status-filter" class="filter-input" data-filter-type="status">
                <option value="">All Statuses</option>
                <?php if ($data_type === 'inquiries'): ?>
                    <option value="pending" <?php echo $default_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $default_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $default_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <?php else: ?>
                    <option value="pending" <?php echo $default_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $default_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo $default_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $default_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="blocked" <?php echo $default_status === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                <?php endif; ?>
            </select>
        </div>

        <!-- Date From -->
        <div class="filter-group">
            <label for="date-from" class="filter-label">📅 From</label>
            <input 
                type="date" 
                id="date-from" 
                class="filter-input"
                data-filter-type="date_from"
            >
        </div>

        <!-- Date To -->
        <div class="filter-group">
            <label for="date-to" class="filter-label">📅 To</label>
            <input 
                type="date" 
                id="date-to" 
                class="filter-input"
                data-filter-type="date_to"
            >
        </div>

        <!-- Package Filter (for inquiries/bookings) -->
        <div class="filter-group">
            <label for="package-filter" class="filter-label">📦 Package</label>
            <select id="package-filter" class="filter-input" data-filter-type="package_id">
                <option value="">All Packages</option>
                <?php
                $packages = getPackages();
                foreach ($packages as $pkg):
                ?>
                <option value="<?php echo (int)$pkg['id']; ?>">
                    <?php echo escape($pkg['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Clear Filters Button -->
        <button class="btn-filter-clear" id="clear-filters">✕ Clear</button>

        <!-- Sort Toggle -->
        <button class="btn-filter-sort" id="toggle-sort" title="Toggle sort order">↑ Newest</button>
    </div>
</div>

<!-- Filter Results Info -->
<div class="filter-info" id="filter-info" style="display: none;">
    <p>Showing <span id="result-count">0</span> results</p>
</div>

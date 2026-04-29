<?php
/**
 * Admin Sidebar Component
 * Include this file at the top of every admin page
 * 
 * Usage: 
 * <?php require_once __DIR__ . '/admin_sidebar.php'; ?>
 */

require_once __DIR__ . '/functions.php';

// Get current page name for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Define admin navigation structure
$admin_nav = [
    'MAIN' => [
        ['page' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => '🏠'],
    ],
    'MANAGEMENT' => [
        ['page' => 'inquiries.php', 'label' => 'Inquiries', 'icon' => '💬'],
        ['page' => 'bookings.php', 'label' => 'Bookings', 'icon' => '📅'],
        ['page' => 'packages.php', 'label' => 'Packages', 'icon' => '📦'],
    ],
    'REPORTS' => [
        ['page' => 'calendar.php', 'label' => 'Calendar', 'icon' => '📆'],
        ['page' => 'sales.php', 'label' => 'Sales', 'icon' => '💰'],
        ['page' => 'archives.php', 'label' => 'Archives', 'icon' => '📁'],
    ],
    'SETTINGS' => [
        ['page' => 'menu-manager.php', 'label' => 'Menu Manager', 'icon' => '🍽️'],
        ['page' => 'setup_admin.php', 'label' => 'Admin Setup', 'icon' => '⚙️'],
        ['page' => 'logout.php', 'label' => 'Logout', 'icon' => '🚪'],
    ],
];
?>
<aside class="admin-sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="brand brand-admin">
            <span class="brand-mark" aria-hidden="true">
                <img src="../assets/images/arc-logo.png" alt="ARC Kitchen logo" class="brand-logo-image">
            </span>
            <div class="brand-text">
                <div class="brand-name">ARC Kitchen</div>
                <div class="brand-subtitle">ADMIN PANEL</div>
            </div>
        </div>
    </div>

    <!-- Admin User Info -->
    <div class="sidebar-user-info">
        <div class="user-avatar">👤</div>
        <div class="user-details">
            <div class="user-name">
                <?php echo escape($_SESSION['admin_username'] ?? 'Admin'); ?>
            </div>
            <div class="user-role">Administrator</div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <?php foreach ($admin_nav as $group_name => $items): ?>
            <div class="nav-group">
                <div class="nav-group-label"><?php echo htmlspecialchars($group_name); ?></div>
                <div class="nav-items">
                    <?php foreach ($items as $item): ?>
                        <?php $is_active = ($current_page === $item['page']); ?>
                        <a 
                            href="<?php echo htmlspecialchars($item['page']); ?>" 
                            class="nav-item <?php echo $is_active ? 'active' : ''; ?>"
                        >
                            <span class="nav-icon"><?php echo $item['icon']; ?></span>
                            <span class="nav-label"><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <p class="sidebar-version">v1.0</p>
    </div>
</aside>
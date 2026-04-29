<?php

require_once __DIR__ . '/../includes/functions.php';
requireAdminCheck();

$packages = getPackages();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packages - ARC Kitchen Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-shell">
        <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">📦 Catering Packages</h1>
                <button class="btn-admin btn-primary-admin">+ New Package</button>
            </div>

            <!-- Packages Grid -->
            <div class="admin-card">
                <?php if (!empty($packages)): ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Package Name</th>
                                <th>Serves</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><strong><?php echo escape($package['name']); ?></strong></td>
                                <td><?php echo escape($package['serves'] ?? 'N/A'); ?></td>
                                <td>₱<?php echo number_format($package['price'], 2); ?></td>
                                <td>
                                    <?php $isActive = !empty($package['is_active']); ?>
                                    <span class="badge badge-<?php echo ($isActive ? 'confirmed' : 'cancelled'); ?>">
                                        <?php echo ($isActive ? 'Active' : 'Inactive'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn-admin btn-secondary-admin btn-small">Edit</button>
                                        <button class="btn-admin btn-secondary-admin btn-small">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No packages found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
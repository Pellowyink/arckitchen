<?php

require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'ARC Kitchen';
$currentPage = currentPageName();
$basePrefix = strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false ? '../' : '';

$navItems = [
    'home.php' => 'Home',
    'menu.php' => 'Menu & Packages',
    'about.php' => 'About',
    'contact.php' => 'Contact Us',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/style.css?v=1">
</head>
<body>
<div class="site-shell">
    <header class="site-header">
        <div class="container nav-wrap">
            <a href="<?php echo $basePrefix; ?>home.php" class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <img src="<?php echo $basePrefix; ?>assets/images/arc-logo.png" alt="ARC Kitchen logo" class="brand-logo-image">
                </span>
                <span>ARC Kitchen</span>
            </a>

            <button class="nav-toggle" type="button" aria-label="Open navigation" data-nav-toggle>
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="main-nav" data-nav-menu>
                <?php foreach ($navItems as $file => $label): ?>
                    <?php $isActive = $currentPage === $file; ?>
                    <a href="<?php echo $basePrefix . $file; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                        <?php echo escape($label); ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?php echo $basePrefix; ?>inquiry.php" class="button button-small">Book Now</a>
            </nav>
        </div>
    </header>
    <main>


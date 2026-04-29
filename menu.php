<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'ARC Kitchen | Menu & Packages';
$menuItems = getMenuItems();
$packages = getPackages();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <div class="page-hero-card reveal">
            <span class="eyebrow">Our Menu &amp; Packages</span>
            <h1>Thoughtfully crafted catering menu and packages.</h1>
            <p>Fixed pax or freestyle headcount—we’re all about serving good food that fits your kind of party.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-card reveal">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Menu Items</span>
                    <h2>Generous portions made for sharing… or not, we won’t judge.</h2>
                </div>
                <a href="booking.php" class="button button-small">Book a Tray</a>
            </div>
            <div class="grid-3">
                <?php foreach ($menuItems as $item): ?>
                    <article class="menu-card">
                        <img src="<?php echo escape($item['image']); ?>" alt="<?php echo escape($item['name']); ?>">
                        <p class="pill"><?php echo escape($item['category']); ?></p>
                        <h3><?php echo escape($item['name']); ?></h3>
                        <p><?php echo escape($item['description']); ?></p>
                        <div class="stack-inline">
                            <span class="price-tag">PHP <?php echo number_format((float) $item['price'], 2); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-card reveal">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Packages</span>
                    <h2>Whatever the occasion, there’s a package ready to get the party started.</h2>
                </div>
            </div>
            <div class="grid-3">
                <?php foreach ($packages as $package): ?>
                    <article class="package-card">
                        <p class="pill"><?php echo escape($package['serves']); ?></p>
                        <h3><?php echo escape($package['name']); ?></h3>
                        <p><?php echo escape($package['description']); ?></p>
                        <div class="stack-inline">
                            <span class="price-tag">PHP <?php echo number_format((float) $package['price'], 2); ?></span>
                            <a href="booking.php" class="button button-small">Inquire</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


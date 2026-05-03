<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'ARC Kitchen | Home';
$menuItems = array_slice(getMenuItems(), 0, 3);
$packages = array_slice(getPackages(), 0, 3);

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-card reveal">
            <div class="hero-copy">
                <h1>Tray Your Way to Flavor: Delicious Eats for Any Occasion</h1>
                <p>Crafting delicious, handcrafted party trays for any occasion, served with a touch of passion and flavor. </p>
                <div class="hero-actions">
                    <a href="booking.php" class="button">Book Now</a>
                    <a href="menu.php" class="button button-outline">View Menu &amp; Packages</a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-carousel" data-hero-carousel>
                    <div class="hero-slide is-active">
                        <img src="assets/images/hero-slide-1.png" alt="ARC Kitchen pasta tray" class="hero-art-primary">
                    </div>
                    <div class="hero-slide">
                        <img src="assets/images/hero-slide-2.png" alt="ARC Kitchen sisig tray" class="hero-art-primary">
                    </div>
                    <div class="hero-slide">
                        <img src="assets/images/hero-slide-3.png" alt="ARC Kitchen salad tray" class="hero-art-primary">
                    </div>
                </div>
                <div class="hero-carousel-dots" aria-hidden="true">
                    <span class="hero-dot is-active"></span>
                    <span class="hero-dot"></span>
                    <span class="hero-dot"></span>
                </div>
                <div class="hero-curve"></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-card reveal">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Our Menu</span>
                    <h2>Big portions for any occasion, with flexible choices to build your perfect spread.</h2>
                    <p>Big crowd, small crew, or somewhere in between—we serve it up just how your party needs it.</p>
                </div>
                <a href="menu.php" class="button button-small">See Full Menu</a>
            </div>
            <div class="grid-3">
                <?php foreach ($menuItems as $item): ?>
                    <article class="menu-card">
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
                    <span class="eyebrow">Our Packages</span>
                    <h2>Thoughtfully crafted catering packages for every occasion and every budget.</h2>
                    <p>Plans changing? Guests adding up? We roll with it so your catering stays spot-on.</p>
                </div>
            </div>
            <div class="grid-3">
                <?php foreach ($packages as $package): ?>
                    <article class="package-card">
                        <p class="pill"><?php echo escape($package['serves']); ?></p>
                        <h3><?php echo escape($package['name']); ?></h3>
                        <p><?php echo escape($package['description']); ?></p>
                        <div class="stack-inline">
                            <span class="price-tag">PHP <?php echo number_format((float) ($package['total_price'] ?? $package['price'] ?? 0), 2); ?></span>
                            <a href="booking.php" class="button button-small">Inquire</a>
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
                    <span class="eyebrow">Why ARC Kitchen</span>
                    <h2>A polished placeholder layout for your final brand story</h2>
                </div>
            </div>
            <div class="grid-3">
                <article class="feature-card">
                    <h3>Feature Placeholder 1</h3>
                    <p>Replace this copy with your final value proposition or service highlight.</p>
                </article>
                <article class="feature-card">
                    <h3>Feature Placeholder 2</h3>
                    <p>Replace this copy with your final value proposition or service highlight.</p>
                </article>
                <article class="feature-card">
                    <h3>Feature Placeholder 3</h3>
                    <p>Replace this copy with your final value proposition or service highlight.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


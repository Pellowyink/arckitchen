<?php

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'ARC Kitchen | Menu & Packages';
$menuItems = getMenuItems();
$packages = getPackages();

// Group menu items by category for display
$groupedMenu = [];
foreach ($menuItems as $item) {
    $category = $item['category'];
    if (!isset($groupedMenu[$category])) {
        $groupedMenu[$category] = [];
    }
    $groupedMenu[$category][] = $item;
}

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
                    <h2>Generous portions made for sharing… or not, we won't judge.</h2>
                </div>
                <a href="booking.php" class="button button-small">Book a Tray</a>
            </div>
            
            <!-- Category Tabs -->
            <div class="category-tabs">
                <?php 
                $first = true;
                foreach ($groupedMenu as $category => $items): 
                ?>
                    <button type="button" class="category-tab <?php echo $first ? 'active' : ''; ?>" data-category="<?php echo escape($category); ?>">
                        <?php echo escape($category); ?> <span class="count">(<?php echo count($items); ?>)</span>
                    </button>
                <?php 
                    $first = false;
                endforeach; 
                ?>
            </div>
            
            <!-- Category Content -->
            <div class="category-content">
                <?php 
                $first = true;
                foreach ($groupedMenu as $category => $items): 
                ?>
                <div class="category-panel <?php echo $first ? 'active' : ''; ?>" data-category="<?php echo escape($category); ?>">
                    <div class="menu-items-row">
                        <?php foreach ($items as $item): ?>
                            <article class="menu-card" onclick="openSidebar(<?php echo (int)$item['id']; ?>, 'item')" style="cursor: pointer;">
                                <img src="<?php echo escape($item['image']); ?>" alt="<?php echo escape($item['name']); ?>">
                                <p class="pill"><?php echo escape($item['category']); ?></p>
                                <h3><?php echo escape($item['name']); ?></h3>
                                <p><?php echo escape($item['description']); ?></p>
                                <div class="stack-inline">
                                    <span class="price-tag">₱<?php echo number_format((float) $item['price'], 2); ?></span>
                                    <button type="button" class="button button-small">Add to Order</button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php 
                    $first = false;
                endforeach; 
                ?>
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
                    <article class="package-card" onclick="openSidebar(<?php echo (int)$package['id']; ?>, 'package')" style="cursor: pointer;">
                        <p class="pill">PACKAGE</p>
                        <h3><?php echo escape($package['name']); ?></h3>
                        <p><?php echo escape($package['description']); ?></p>
                        <div class="stack-inline">
                            <span class="price-tag">₱<?php echo number_format((float) $package['total_price'], 2); ?></span>
                            <button type="button" class="button button-small">Select Package</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/sidebar.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

<style>
/* Category Tabs */
.category-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(138, 41, 39, 0.1);
    overflow-x: auto;
    flex-wrap: nowrap;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #8a2927 #f5f5f5;
}

.category-tabs::-webkit-scrollbar {
    height: 6px;
}

.category-tabs::-webkit-scrollbar-track {
    background: #f5f5f5;
    border-radius: 3px;
}

.category-tabs::-webkit-scrollbar-thumb {
    background: #8a2927;
    border-radius: 3px;
}

.category-tab {
    padding: 0.75rem 1.25rem;
    border: none;
    background: transparent;
    color: #666;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    border-radius: 8px;
    white-space: nowrap;
    transition: all 0.2s;
}

.category-tab:hover {
    background: rgba(138, 41, 39, 0.05);
    color: #8a2927;
}

.category-tab.active {
    background: #8a2927;
    color: white;
}

.category-tab .count {
    font-size: 0.8rem;
    opacity: 0.7;
    margin-left: 0.25rem;
}

/* Category Panels */
.category-panel {
    display: none;
}

.category-panel.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Horizontal Menu Items Row */
.menu-items-row {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding-bottom: 1rem;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    scrollbar-color: #8a2927 #f5f5f5;
}

.menu-items-row::-webkit-scrollbar {
    height: 8px;
}

.menu-items-row::-webkit-scrollbar-track {
    background: #f5f5f5;
    border-radius: 4px;
}

.menu-items-row::-webkit-scrollbar-thumb {
    background: #8a2927;
    border-radius: 4px;
}

.menu-items-row .menu-card {
    flex: 0 0 280px;
    min-width: 280px;
    max-width: 280px;
}

.menu-card:hover, .package-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.category-tab');
    const panels = document.querySelectorAll('.category-panel');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Update active panel
            panels.forEach(p => p.classList.remove('active'));
            document.querySelector(`.category-panel[data-category="${category}"]`).classList.add('active');
        });
    });
});
</script>

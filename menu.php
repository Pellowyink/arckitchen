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
                <a href="inquiry.php" class="button button-small">View Cart</a>
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
                            <article class="menu-card" data-item-id="<?php echo (int)$item['id']; ?>" data-item-type="item" data-item-name="<?php echo addslashes($item['name']); ?>" data-item-price="<?php echo $item['price']; ?>">
                                <p class="pill"><?php echo escape($item['category']); ?></p>
                                <h3 onclick="openSidebar(<?php echo (int)$item['id']; ?>, 'item')" style="cursor: pointer;"><?php echo escape($item['name']); ?></h3>
                                <p onclick="openSidebar(<?php echo (int)$item['id']; ?>, 'item')" style="cursor: pointer;"><?php echo escape($item['description']); ?></p>
                                <div class="stack-inline menu-card-actions">
                                    <span class="price-tag">₱<?php echo number_format((float) $item['price'], 2); ?></span>
                                    <div class="quick-add-group">
                                        <button type="button" class="btn-quick-add" onclick="event.stopPropagation(); quickAddItem(<?php echo $item['id']; ?>, 'item', '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)"}>+</button>
                                        <button type="button" class="button button-small" onclick="openSidebar(<?php echo (int)$item['id']; ?>, 'item')">Customize</button>
                                    </div>
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
                    <article class="package-card" data-package-id="<?php echo (int)$package['id']; ?>">
                        <p class="pill">PACKAGE</p>
                        <h3 onclick="openSidebar(<?php echo (int)$package['id']; ?>, 'package')" style="cursor: pointer;"><?php echo escape($package['name']); ?></h3>
                        <p onclick="openSidebar(<?php echo (int)$package['id']; ?>, 'package')" style="cursor: pointer;"><?php echo escape($package['description']); ?></p>
                        <div class="stack-inline package-card-actions">
                            <span class="price-tag">₱<?php echo number_format((float) $package['total_price'], 2); ?></span>
                            <div class="quick-add-group">
                                <button type="button" class="btn-quick-add" onclick="event.stopPropagation(); quickAddItem(<?php echo $package['id']; ?>, 'package', '<?php echo addslashes($package['name']); ?>', <?php echo $package['total_price']; ?>)">+</button>
                                <button type="button" class="button button-small" onclick="openSidebar(<?php echo (int)$package['id']; ?>, 'package')">View Details</button>
                            </div>
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
    flex: 0 0 260px;
    min-width: 260px;
    max-width: 260px;
    padding: 1.25rem;
}

.menu-card {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.menu-card .pill {
    align-self: flex-start;
    margin-bottom: 0.25rem;
}

.menu-card h3 {
    font-size: 1.1rem;
    line-height: 1.3;
    margin: 0;
}

.menu-card p {
    font-size: 0.9rem;
    line-height: 1.5;
    color: #666;
    margin: 0;
    flex: 1;
}

.menu-card:hover, .package-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}

/* Quick Add Buttons */
.menu-card-actions, .package-card-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
}

.quick-add-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-quick-add {
    width: 36px;
    height: 36px;
    border: none;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
    border-radius: 10px;
    font-size: 1.4rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.btn-quick-add:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(138, 41, 39, 0.3);
}

/* Toast notification for quick add */
.add-toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    z-index: 1000;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.add-toast.fade-out {
    animation: fadeOut 0.3s ease forwards;
}

@keyframes fadeOut {
    to {
        transform: translateY(100%);
        opacity: 0;
    }
}
</style>

<script>
// Quick Add Item Function
function quickAddItem(id, type, name, price) {
    const data = {
        product_id: id,
        product_name: name,
        product_price: price,
        quantity: 1,
        type: type,
        special_instructions: ''
    };
    
    fetch('includes/sidebar.php?action=add_to_cart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            showAddToast(name);
            // Update cart count in sidebar if visible
            const cartCountBadge = document.querySelector('.cart-badge');
            if (cartCountBadge) {
                cartCountBadge.textContent = result.cart_count;
            }
        } else {
            alert('Failed to add item');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to add item');
    });
}

// Show toast notification
function showAddToast(itemName) {
    // Remove existing toast
    const existingToast = document.querySelector('.add-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = 'add-toast';
    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 1.2rem;">✓</span>
            <div>
                <strong style="display: block;">Added to cart!</strong>
                <small style="opacity: 0.9;">${itemName}</small>
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Remove after 2 seconds
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

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

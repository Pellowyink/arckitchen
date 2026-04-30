<?php
/**
 * Customer Customization Sidebar
 * Dynamic ordering sidebar for product and package customization
 * Included in menu.php and inquiry.php
 */
require_once __DIR__ . '/functions.php';

// Initialize session cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add_to_cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && !empty($data['product_id'])) {
        $item = [
            'id' => time(), // unique cart item id
            'product_id' => (int)$data['product_id'],
            'product_name' => $data['product_name'] ?? 'Unknown Item',
            'product_price' => (float)$data['product_price'] ?? 0,
            'quantity' => (int)($data['quantity'] ?? 1),
            'notes' => $data['special_instructions'] ?? '',
            'variant' => $data['variant'] ?? '',
            'type' => $data['type'] ?? 'item' // 'item' or 'package'
        ];
        
        $_SESSION['cart'][] = $item;
        
        echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
    }
    exit;
}

if ($action === 'update_cart_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && isset($data['item_id']) && isset($data['quantity'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $data['item_id']) {
                if ($data['quantity'] <= 0) {
                    unset($_SESSION['cart'][$key]);
                } else {
                    $_SESSION['cart'][$key]['quantity'] = (int)$data['quantity'];
                }
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // reindex
        echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($action === 'remove_cart_item' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && isset($data['item_id'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $data['item_id']) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($action === 'clear_cart') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_cart') {
    echo json_encode(['items' => $_SESSION['cart'], 'total' => calculateCartTotal()]);
    exit;
}

function calculateCartTotal(): float {
    $total = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $total += ($item['product_price'] * $item['quantity']);
    }
    return $total;
}

$cartItems = $_SESSION['cart'] ?? [];
$cartCount = count($cartItems);
$cartTotal = calculateCartTotal();
?>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Customization Sidebar -->
<aside class="custom-sidebar" id="customSidebar">
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <h2>🛒 Your Order</h2>
            <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Close sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Dynamic Content Container -->
        <div id="sidebarDynamicContent">
            <div class="sidebar-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <p>Your cart is empty</p>
                <p class="sidebar-empty-subtitle">Browse the menu and select items or packages</p>
            </div>
        </div>

        <!-- Cart Summary (always visible when items exist) -->
        <?php if ($cartCount > 0): ?>
        <div class="cart-section" id="cartSummarySection">
            <h3 class="cart-title">📦 Order Summary (<?php echo $cartCount; ?> items)</h3>
            
            <div class="cart-items" id="cartItemsList">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                    <div class="cart-item-info">
                        <span class="cart-item-name"><?php echo escape($item['product_name']); ?></span>
                        <?php if ($item['notes']): ?>
                        <span class="cart-item-note"><?php echo escape($item['notes']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-controls">
                        <div class="qty-control">
                            <button type="button" onclick="updateCartItem(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)" class="qty-btn-sm">−</button>
                            <span class="qty-display"><?php echo $item['quantity']; ?></span>
                            <button type="button" onclick="updateCartItem(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)" class="qty-btn-sm">+</button>
                        </div>
                        <span class="cart-item-price">₱<?php echo number_format($item['product_price'] * $item['quantity'], 2); ?></span>
                        <button type="button" onclick="removeCartItem(<?php echo $item['id']; ?>)" class="remove-btn" title="Remove">×</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-total-section">
                <div class="cart-total-row">
                    <span>Total</span>
                    <span class="cart-total-amount">₱<?php echo number_format($cartTotal, 2); ?></span>
                </div>
            </div>

            <div class="sidebar-actions">
                <button type="button" class="btn-secondary" onclick="clearCart()">Clear Cart</button>
                <button type="button" class="btn-primary" onclick="proceedToInquiry()">📩 Go to Booking</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</aside>

<style>
/* Sidebar Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.sidebar-overlay.is-visible {
    opacity: 1;
    visibility: visible;
}

/* Custom Sidebar */
.custom-sidebar {
    position: fixed;
    top: 0;
    right: -480px;
    width: 480px;
    max-width: 95vw;
    height: 100vh;
    background: #fffdf8;
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.custom-sidebar.is-open {
    right: 0;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    padding-bottom: 2rem;
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid rgba(138, 41, 39, 0.1);
}

.sidebar-header h2 {
    margin: 0;
    font-size: 1.4rem;
    color: #4a1414;
    font-family: 'League Spartan', sans-serif;
}

.sidebar-close {
    width: 40px;
    height: 40px;
    border: 2px solid rgba(138, 41, 39, 0.2);
    border-radius: 12px;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c1d12;
    transition: all 0.2s;
}

.sidebar-close:hover {
    border-color: #8a2927;
    background: rgba(138, 41, 39, 0.1);
}

/* Empty State */
.sidebar-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: #666;
}

.sidebar-empty svg {
    margin-bottom: 1rem;
    opacity: 0.4;
    color: #8a2927;
}

.sidebar-empty-subtitle {
    font-size: 0.9rem;
    color: #999;
    margin-top: 0.5rem;
}

/* Cart Section */
.cart-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 2px solid rgba(138, 41, 39, 0.1);
}

.cart-title {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
    color: #4a1414;
}

.cart-items {
    margin-bottom: 1rem;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.75rem;
    background: rgba(247, 241, 231, 0.5);
    border-radius: 12px;
    margin-bottom: 0.5rem;
}

.cart-item-info {
    flex: 1;
}

.cart-item-name {
    display: block;
    font-weight: 600;
    color: #4a1414;
    font-size: 0.95rem;
}

.cart-item-note {
    display: block;
    font-size: 0.8rem;
    color: #888;
    margin-top: 0.25rem;
}

.cart-item-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.qty-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn-sm {
    width: 28px;
    height: 28px;
    border: 1px solid #d5a437;
    border-radius: 6px;
    background: transparent;
    color: #8a2927;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-display {
    min-width: 24px;
    text-align: center;
    font-weight: 600;
    color: #4a1414;
}

.cart-item-price {
    font-weight: 600;
    color: #8a2927;
    min-width: 80px;
    text-align: right;
}

.remove-btn {
    width: 24px;
    height: 24px;
    border: none;
    background: rgba(138, 41, 39, 0.1);
    color: #8a2927;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.2rem;
    line-height: 1;
}

/* Total Section */
.cart-total-section {
    padding: 1rem;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    border-radius: 16px;
    margin-bottom: 1rem;
}

.cart-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}

.cart-total-row span:first-child {
    font-size: 1rem;
}

.cart-total-amount {
    font-size: 1.3rem;
    font-weight: 700;
    font-family: 'League Spartan', sans-serif;
}

/* Action Buttons */
.sidebar-actions {
    display: flex;
    gap: 0.75rem;
}

.sidebar-actions .btn-primary,
.sidebar-actions .btn-secondary {
    flex: 1;
    padding: 1rem;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 29, 18, 0.3);
}

.btn-secondary {
    background: transparent;
    color: #6c1d12;
    border: 2px solid rgba(138, 41, 39, 0.3);
}

.btn-secondary:hover {
    background: rgba(138, 41, 39, 0.1);
}

/* Product/Package Detail View */
.product-detail {
    padding: 1rem 0;
}

.product-image-lg {
    width: 100%;
    aspect-ratio: 16/10;
    border-radius: 16px;
    overflow: hidden;
    background: #f7efe2;
    margin-bottom: 1rem;
}

.product-image-lg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-meta {
    margin-bottom: 1rem;
}

.product-category {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    background: rgba(138, 41, 39, 0.1);
    color: #8a2927;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.product-name-lg {
    font-size: 1.3rem;
    color: #4a1414;
    margin: 0 0 0.5rem 0;
    font-family: 'League Spartan', sans-serif;
}

.product-desc {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.product-price-lg {
    font-size: 1.5rem;
    color: #8a2927;
    font-weight: 700;
}

/* Form Sections */
.form-section {
    margin-bottom: 1.25rem;
    padding: 1rem;
    background: rgba(247, 241, 231, 0.7);
    border-radius: 14px;
}

.form-section label {
    display: block;
    font-weight: 600;
    color: #4a1414;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.quantity-selector-lg {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.qty-btn-lg {
    width: 48px;
    height: 48px;
    border: 2px solid #d5a437;
    border-radius: 12px;
    background: transparent;
    color: #8a2927;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.qty-btn-lg:hover {
    background: #d5a437;
    color: white;
}

.qty-input-lg {
    flex: 1;
    text-align: center;
    font-size: 1.2rem;
    font-weight: 600;
    border: 2px solid rgba(138, 41, 39, 0.2);
    border-radius: 12px;
    padding: 0.75rem;
    color: #4a1414;
}

.special-instructions {
    width: 100%;
    border: 2px solid rgba(138, 41, 39, 0.2);
    border-radius: 12px;
    padding: 0.85rem;
    font-family: inherit;
    font-size: 0.95rem;
    resize: vertical;
    min-height: 80px;
}

.special-instructions:focus {
    outline: none;
    border-color: #d5a437;
}

/* Package Items List */
.package-items-list {
    margin: 1rem 0;
}

.package-item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(138, 41, 39, 0.1);
    font-size: 0.9rem;
}

.package-item-name {
    color: #4a1414;
}

.package-item-price {
    color: #8a2927;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 600px) {
    .custom-sidebar {
        width: 100%;
        right: -100%;
    }
    
    .sidebar-content {
        padding: 1rem;
    }
}
</style>

<script>
// Cart Management Functions

function openSidebar(productId, type = 'item') {
    if (!productId) return;
    
    // Fetch product/package data
    const endpoint = type === 'package' ? 'api/get-package.php?id=' + productId : 'api/get-product.php?id=' + productId;
    
    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateSidebar(data.data, type);
                showSidebar();
            } else {
                alert('Failed to load item details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load item details');
        });
}

function populateSidebar(item, type) {
    const container = document.getElementById('sidebarDynamicContent');
    
    if (type === 'package') {
        container.innerHTML = generatePackageHTML(item);
    } else {
        container.innerHTML = generateProductHTML(item);
    }
}

function generateProductHTML(product) {
    return `
        <div class="product-detail">
            <div class="product-image-lg">
                <img src="${product.image || 'assets/images/food-placeholder.svg'}" alt="${product.name}">
            </div>
            <div class="product-meta">
                <span class="product-category">${product.category}</span>
                <h3 class="product-name-lg">${product.name}</h3>
                <p class="product-desc">${product.description || ''}</p>
                <div class="product-price-lg">₱${parseFloat(product.price).toFixed(2)}</div>
            </div>
            
            <form id="addToCartForm" onsubmit="addToCart(event)">
                <input type="hidden" name="product_id" value="${product.id}">
                <input type="hidden" name="product_name" value="${product.name}">
                <input type="hidden" name="product_price" value="${product.price}">
                <input type="hidden" name="type" value="item">
                
                <div class="form-section">
                    <label>Quantity</label>
                    <div class="quantity-selector-lg">
                        <button type="button" class="qty-btn-lg" onclick="updateQty(-1)">−</button>
                        <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="99" class="qty-input-lg" readonly>
                        <button type="button" class="qty-btn-lg" onclick="updateQty(1)">+</button>
                    </div>
                </div>
                
                <div class="form-section">
                    <label>Special Instructions</label>
                    <textarea name="special_instructions" class="special-instructions" placeholder="Any allergies, preferences, or notes?"></textarea>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem;">
                    Add to Order - ₱${parseFloat(product.price).toFixed(2)}
                </button>
            </form>
        </div>
    `;
}

function generatePackageHTML(pkg) {
    let itemsHtml = '';
    if (pkg.items && pkg.items.length > 0) {
        itemsHtml = '<div class="package-items-list">';
        itemsHtml += '<h4 style="margin: 0 0 0.5rem 0; color: #4a1414;">Includes:</h4>';
        pkg.items.forEach(item => {
            itemsHtml += `
                <div class="package-item-row">
                    <span class="package-item-name">${item.name}</span>
                    <span class="package-item-price">₱${parseFloat(item.price).toFixed(2)}</span>
                </div>
            `;
        });
        itemsHtml += '</div>';
    }
    
    return `
        <div class="product-detail">
            <div class="product-meta">
                <span class="product-category">PACKAGE</span>
                <h3 class="product-name-lg">${pkg.name}</h3>
                <p class="product-desc">${pkg.description || ''}</p>
                ${itemsHtml}
                <div class="product-price-lg" style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid rgba(138,41,39,0.1);">
                    Package Price: ₱${parseFloat(pkg.total_price).toFixed(2)}
                </div>
            </div>
            
            <form id="addToCartForm" onsubmit="addToCart(event)">
                <input type="hidden" name="product_id" value="${pkg.id}">
                <input type="hidden" name="product_name" value="${pkg.name}">
                <input type="hidden" name="product_price" value="${pkg.total_price}">
                <input type="hidden" name="type" value="package">
                
                <div class="form-section">
                    <label>Quantity</label>
                    <div class="quantity-selector-lg">
                        <button type="button" class="qty-btn-lg" onclick="updateQty(-1)">−</button>
                        <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="99" class="qty-input-lg" readonly>
                        <button type="button" class="qty-btn-lg" onclick="updateQty(1)">+</button>
                    </div>
                </div>
                
                <div class="form-section">
                    <label>Special Instructions</label>
                    <textarea name="special_instructions" class="special-instructions" placeholder="Any modifications or special requests?"></textarea>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem;">
                    Add Package to Order
                </button>
            </form>
        </div>
    `;
}

function showSidebar() {
    document.getElementById('customSidebar').classList.add('is-open');
    document.getElementById('sidebarOverlay').classList.add('is-visible');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    document.getElementById('customSidebar').classList.remove('is-open');
    document.getElementById('sidebarOverlay').classList.remove('is-visible');
    document.body.style.overflow = '';
}

function updateQty(delta) {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    input.value = val;
}

function addToCart(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    fetch('includes/sidebar.php?action=add_to_cart', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            closeSidebar();
            location.reload();
        } else {
            alert(result.message || 'Failed to add item');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        alert('Failed to add item');
    });
}

function updateCartItem(itemId, quantity) {
    fetch('includes/sidebar.php?action=update_cart_item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ item_id: itemId, quantity: quantity })
    })
    .then(r => r.json())
    .then(() => location.reload());
}

function removeCartItem(itemId) {
    if (!confirm('Remove this item?')) return;
    
    fetch('includes/sidebar.php?action=remove_cart_item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ item_id: itemId })
    })
    .then(r => r.json())
    .then(() => location.reload());
}

function clearCart() {
    if (!confirm('Clear all items from your cart?')) return;
    
    fetch('includes/sidebar.php?action=clear_cart')
        .then(() => location.reload());
}

function proceedToInquiry() {
    // Save cart to session and redirect to inquiry page
    window.location.href = 'inquiry.php?step=order';
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSidebar();
    }
});
</script>

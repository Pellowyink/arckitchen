<?php
/**
 * Customer Customization Sidebar
 * Dynamic ordering sidebar for product customization
 * Included in menu.php and inquiry.php
 */
require_once __DIR__ . '/functions.php';

// Handle AJAX actions
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if ($action === 'add_to_cart') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data) {
            addToCart($data);
            echo json_encode(['success' => true, 'cart_count' => count(getCartItems())]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
        }
        exit;
    }
    
    if ($action === 'clear_cart') {
        clearCart();
        echo json_encode(['success' => true]);
        exit;
    }
}

// Handle cart actions via POST
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'clear_cart') {
    clearCart();
    redirect('menu.php');
}

// Fetch product details if ID provided
$sidebar_product = null;
if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    $product = fetchAllRecords(
        "SELECT id, name, description, price, image, category FROM menu_items WHERE id = {$product_id} AND is_active = 1 LIMIT 1"
    );
    if (!empty($product)) {
        $sidebar_product = $product[0];
    }
}
?>

<!-- Customization Sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay" style="display: none;"></div>

<aside class="custom-sidebar" id="customSidebar">
    <div class="sidebar-content">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <h2>Customize Your Order</h2>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close sidebar">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <?php if ($sidebar_product): ?>
            <!-- Product Details -->
            <div class="sidebar-product">
                <div class="product-image">
                    <img src="<?php echo escape($sidebar_product['image']); ?>" 
                         alt="<?php echo escape($sidebar_product['name']); ?>">
                </div>
                <div class="product-info">
                    <span class="pill" style="background: rgba(108, 29, 18, 0.1); color: var(--surface-dark);">
                        <?php echo escape($sidebar_product['category']); ?>
                    </span>
                    <h3><?php echo escape($sidebar_product['name']); ?></h3>
                    <p class="product-description"><?php echo escape($sidebar_product['description']); ?></p>
                    <div class="product-price">
                        <span class="price-tag">₱<?php echo number_format((float)$sidebar_product['price'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Customization Form -->
            <form id="customizationForm">
                <input type="hidden" name="product_id" value="<?php echo (int)$sidebar_product['id']; ?>">
                <input type="hidden" name="product_name" value="<?php echo escape($sidebar_product['name']); ?>">
                <input type="hidden" name="product_price" value="<?php echo (float)$sidebar_product['price']; ?>">
                
                <!-- Quantity Selector -->
                <div class="form-section">
                    <label for="quantity">Quantity</label>
                    <div class="quantity-selector">
                        <button type="button" class="qty-btn" onclick="updateQuantity(-1)" aria-label="Decrease quantity">−</button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="99" required>
                        <button type="button" class="qty-btn" onclick="updateQuantity(1)" aria-label="Increase quantity">+</button>
                    </div>
                </div>

                <!-- Variant Options (shown for certain products) -->
                <div class="form-section" id="variantSection" style="display: none;">
                    <label>Sauce / Variant</label>
                    <div class="variant-options">
                        <label class="variant-option">
                            <input type="radio" name="variant" value="original">
                            <span>Original</span>
                        </label>
                        <label class="variant-option">
                            <input type="radio" name="variant" value="spicy">
                            <span>Spicy</span>
                        </label>
                        <label class="variant-option">
                            <input type="radio" name="variant" value="garlic">
                            <span>Garlic Butter</span>
                        </label>
                    </div>
                </div>

                <!-- Special Instructions -->
                <div class="form-section">
                    <label for="special_instructions">Special Instructions</label>
                    <textarea id="special_instructions" name="special_instructions" 
                              rows="3" placeholder="Any allergies, preferences, or special requests?"></textarea>
                </div>

                <!-- Total -->
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">₱<?php echo number_format((float)$sidebar_product['price'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Estimated Tax</span>
                        <span id="tax">₱0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="orderTotal">₱<?php echo number_format((float)$sidebar_product['price'], 2); ?></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="sidebar-actions">
                    <button type="button" class="btn-secondary" id="continueShopping">
                        Continue Shopping
                    </button>
                    <button type="submit" class="btn-primary">
                        Add to Cart & Proceed
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- No Product Selected -->
            <div class="sidebar-empty">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--surface-dark)" stroke-width="1.5">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <p>Select a product to customize</p>
                <p style="font-size: 0.85rem; color: var(--text-soft); margin-top: 0.5rem;">
                    Browse the menu and click a product to see customization options
                </p>
            </div>
        <?php endif; ?>

        <?php
        $cartItems = getCartItems();
        $cartItemCount = count($cartItems);
        ?>

        <?php if ($cartItemCount > 0): ?>
        <div class="cart-preview">
            <h4 style="margin: 0 0 0.75rem 0; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(53, 21, 18, 0.15);">
                🛒 Cart (<?php echo $cartItemCount; ?>)
            </h4>
            <div style="max-height: 150px; overflow-y: auto; margin-bottom: 0.75rem;">
                <?php foreach ($cartItems as $idx => $cartItem): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: rgba(108, 29, 18, 0.03); border-radius: 8px; margin-bottom: 0.5rem; font-size: 0.9rem;">
                    <span style="flex: 1;"><?php echo escape($cartItem['name'] ?? 'Item'); ?> x<?php echo (int)($cartItem['quantity'] ?? 1); ?></span>
                    <span style="font-weight: 600; color: var(--surface-dark);">₱<?php echo number_format((float)($cartItem['price'] ?? 0) * (int)($cartItem['quantity'] ?? 1), 2); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-secondary" style="flex: 1;" onclick="clearCartAndRefresh()">Clear</button>
                <button type="button" class="btn-primary" style="flex: 1;" onclick="proceedToBooking()">Checkout</button>
            </div>
        </div>
        <hr style="border: 0; border-top: 1px solid rgba(53, 21, 15, 0.1); margin: 1rem 0;">
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="sidebar-actions">
            <button type="button" class="btn-secondary" id="continueShopping">
                Continue Shopping
            </button>
            <button type="submit" form="customizationForm" class="btn-primary">
                Add to Cart
            </button>
        </div>
    </div>
</aside>

<style>
.custom-sidebar {
    position: fixed;
    top: 0;
    right: -420px;
    width: 420px;
    max-width: 100vw;
    height: 100vh;
    background: var(--surface);
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    transition: right 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.custom-sidebar.is-open {
    right: 0;
}

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
    border-bottom: 1px solid rgba(53, 21, 15, 0.1);
}

.sidebar-header h2 {
    margin: 0;
    font-size: 1.4rem;
    color: var(--surface-dark);
    font-family: 'League Spartan', sans-serif;
}

.sidebar-close {
    width: 40px;
    height: 40px;
    border: 1px solid rgba(53, 21, 15, 0.15);
    border-radius: 12px;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-soft);
    transition: all 0.2s;
}

.sidebar-close:hover {
    border-color: var(--primary);
    color: var(--surface-dark);
    background: rgba(213, 164, 55, 0.1);
}

.sidebar-product {
    margin-bottom: 1.5rem;
}

.product-image {
    width: 100%;
    aspect-ratio: 4 / 3;
    border-radius: 20px;
    overflow: hidden;
    background: #f7efe2;
    margin-bottom: 1rem;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info h3 {
    font-size: 1.3rem;
    color: var(--surface-dark);
    margin: 0.5rem 0;
    font-family: 'League Spartan', sans-serif;
}

.product-description {
    color: var(--text-soft);
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.product-price {
    margin-top: 0.5rem;
}

.form-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(247, 241, 231, 0.5);
    border-radius: 16px;
}

.form-section label {
    display: block;
    font-weight: 600;
    color: var(--surface-dark);
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.qty-btn {
    width: 44px;
    height: 44px;
    border: 2px solid var(--primary);
    border-radius: 14px;
    background: transparent;
    color: var(--primary);
    font-size: 1.5rem;
    font-weight: 300;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: var(--primary);
    color: white;
}

#quantity {
    flex: 1;
    text-align: center;
    font-size: 1.1rem;
    font-weight: 600;
    border: 2px solid rgba(53, 21, 15, 0.15);
    border-radius: 12px;
    padding: 0.75rem;
}

.variant-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.variant-option {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid rgba(53, 21, 15, 0.1);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.variant-option:hover {
    border-color: var(--primary);
    background: rgba(213, 164, 55, 0.05);
}

.variant-option input:checked + span {
    color: var(--surface-dark);
    font-weight: 600;
}

.variant-option input:checked ~ .variant-option {
    border-color: var(--primary);
}

.variant-option input {
    width: 20px;
    height: 20px;
    accent-color: var(--primary);
}

#special_instructions {
    width: 100%;
    border: 2px solid rgba(53, 21, 15, 0.15);
    border-radius: 14px;
    padding: 0.85rem;
    font-family: inherit;
    font-size: 0.95rem;
    resize: vertical;
    transition: border-color 0.2s;
}

#special_instructions:focus {
    outline: none;
    border-color: var(--primary);
}

.order-summary {
    margin: 1.5rem 0;
    padding: 1.25rem;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    border-radius: 18px;
    color: white;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.summary-row.total {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 1.2rem;
    font-weight: 700;
    font-family: 'League Spartan', sans-serif;
}

.sidebar-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 16px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(108, 29, 18, 0.3);
}

.btn-secondary {
    background: transparent;
    color: var(--surface-dark);
    border: 2px solid rgba(53, 21, 15, 0.2);
    padding: 0.85rem 1.25rem;
    border-radius: 16px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.btn-secondary:hover {
    background: rgba(53, 21, 15, 0.05);
    border-color: var(--surface-dark);
}

.sidebar-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-soft);
}

.sidebar-empty svg {
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .custom-sidebar {
        width: 100%;
        right: -100%;
    }
    
    .sidebar-content {
        padding: 1rem;
    }
    
    .form-section {
        padding: 0.85rem;
    }
}
</style>

<script>
function openSidebar(productId) {
    if (!productId) return;

    // Fetch product data via AJAX
    fetch('get_product.php?id=' + productId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(product => {
        if (product.error) {
            alert(product.error);
            return;
        }

        // Populate sidebar with product data
        populateSidebar(product);

        // Show sidebar
        document.getElementById('customSidebar').classList.add('is-open');
        document.getElementById('sidebarOverlay').classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    })
    .catch(error => {
        console.error('Error fetching product:', error);
        alert('Failed to load product details');
    });
}

function populateSidebar(product) {
    // Update product details in sidebar
    const productImage = document.querySelector('.product-image img');
    const productCategory = document.querySelector('.product-info .pill');
    const productName = document.querySelector('.product-info h3');
    const productDescription = document.querySelector('.product-description');
    const productPrice = document.querySelector('.product-price .price-tag');

    if (productImage) productImage.src = product.image;
    if (productImage) productImage.alt = product.name;
    if (productCategory) productCategory.textContent = product.category;
    if (productName) productName.textContent = product.name;
    if (productDescription) productDescription.textContent = product.description;
    if (productPrice) productPrice.textContent = '₱' + parseFloat(product.price).toFixed(2);

    // Update hidden inputs
    const form = document.getElementById('customizationForm');
    if (form) {
        form.querySelector('input[name="product_id"]').value = product.id;
        form.querySelector('input[name="product_name"]').value = product.name;
        form.querySelector('input[name="product_price"]').value = product.price;
    }

    // Show variant section for fish products
    const variantSection = document.getElementById('variantSection');
    if (variantSection && product.name.toLowerCase().includes('fish')) {
        variantSection.style.display = 'block';
    } else if (variantSection) {
        variantSection.style.display = 'none';
    }

    // Reset quantity and update total
    const qtyInput = document.getElementById('quantity');
    if (qtyInput) qtyInput.value = 1;
    updateTotal();
}

function closeSidebar() {
    document.getElementById('customSidebar').classList.remove('is-open');
    document.getElementById('sidebarOverlay').classList.remove('is-visible');
            document.body.style.overflow = '';
            
            // Remove query param
            window.history.pushState({}, '', window.location.pathname);
        }

        function updateQuantity(delta) {
            const qtyInput = document.getElementById('quantity');
            let newQty = parseInt(qtyInput.value) + delta;
            if (newQty < 1) newQty = 1;
            if (newQty > 99) newQty = 99;
            qtyInput.value = newQty;
            updateTotal();
        }

        function updateTotal() {
            const productPriceEl = document.querySelector('.product-price .price-tag');
            const qtyInput = document.getElementById('quantity');
            const priceText = productPriceEl ? productPriceEl.textContent.replace('₱', '') : '0';
            const unitPrice = parseFloat(priceText) || 0;
            const qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
            const subtotal = qty * unitPrice;
            const tax = subtotal * 0.12; // 12% estimated tax
            const total = subtotal + tax;
            
            const subtotalEl = document.getElementById('subtotal');
            const taxEl = document.getElementById('tax');
            const totalEl = document.getElementById('orderTotal');
            
            if (subtotalEl) subtotalEl.textContent = '₱' + subtotal.toFixed(2);
            if (taxEl) taxEl.textContent = '₱' + tax.toFixed(2);
            if (totalEl) totalEl.textContent = '₱' + total.toFixed(2);
        }

        function clearCartAndRefresh() {
            fetch('?action=clear_cart', { method: 'POST' })
                .then(() => location.reload());
        }

        function proceedToBooking() {
            window.location.href = 'booking.php';
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarClose = document.getElementById('sidebarClose');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const continueShopping = document.getElementById('continueShopping');
            const qtyInput = document.getElementById('quantity');
            
            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }
            
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', closeSidebar);
            }
            
            if (continueShopping) {
                continueShopping.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeSidebar();
                });
            }
            
            if (qtyInput) {
                qtyInput.addEventListener('change', updateTotal);
                qtyInput.addEventListener('input', updateTotal);
            }
            
            // Show variant section for Fish Fillet and similar items
            const productName = document.querySelector('.product-info h3')?.textContent || '';
            const variantSection = document.getElementById('variantSection');
            if (variantSection && productName.toLowerCase().includes('fish')) {
                variantSection.style.display = 'block';
            }
            
            // Handle form submission via AJAX to add to cart
            const form = document.getElementById('customizationForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const data = {};
                    formData.forEach((value, key) => data[key] = value);
                    
                    fetch('?action=add_to_cart', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) {
                            alert('Item added to cart!');
                            closeSidebar();
                            location.reload();
                        } else {
                            alert(result.message || 'Failed to add item');
                        }
                    })
                    .catch(err => {
                        // Fallback: submit normally
                        form.submit();
                    });
                });
            }
            
            // Close sidebar on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
            
            // Check if sidebar should be open (product_id in URL)
            const urlParams = new URLSearchParams(window.location.search);
            const productId = urlParams.get('product_id');
            
            if (productId) {
                const sidebar = document.getElementById('customSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar) sidebar.classList.add('is-open');
                if (overlay) overlay.classList.add('is-visible');
                document.body.style.overflow = 'hidden';
            }
            
            // Update total on load
            updateTotal();
        });
    </script>

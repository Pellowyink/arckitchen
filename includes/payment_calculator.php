<?php
/**
 * Payment Calculator Sidebar Component
 * Shows when admin clicks "Approve" (inquiry) or "Confirm" (booking)
 * Allows entering down payment and calculates remaining balance
 */
?>

<!-- Payment Calculator Sidebar -->
<div class="payment-sidebar" id="payment-sidebar">
    <div class="payment-overlay" id="payment-overlay" onclick="closePaymentCalculator()"></div>
    
    <div class="payment-content">
        <!-- Sidebar Header -->
        <div class="payment-header">
            <h2>💰 Payment Calculator</h2>
            <button class="btn-close-payment" onclick="closePaymentCalculator()" title="Close">✕</button>
        </div>

        <!-- Loading State -->
        <div class="payment-loading" id="payment-loading" style="display: none;">
            <div class="spinner"></div>
            <p>Loading order details...</p>
        </div>

        <!-- Main Content -->
        <div class="payment-body" id="payment-body" style="display: none;">
            
            <!-- Customer Info Summary -->
            <section class="payment-section customer-summary">
                <h3>👤 Customer</h3>
                <div class="customer-info">
                    <strong id="payment-customer-name">--</strong>
                    <span id="payment-event-date">--</span>
                </div>
            </section>

            <!-- Order Summary -->
            <section class="payment-section">
                <h3>📦 Order Summary</h3>
                <div class="order-items-summary" id="payment-order-items">
                    <!-- Items populated here -->
                </div>
                <div class="total-package-cost">
                    <span>Total Package Cost:</span>
                    <strong id="payment-total-cost">₱0.00</strong>
                </div>
            </section>

            <!-- Payment Input Section -->
            <section class="payment-section payment-input-section">
                <h3>💳 Payment Entry</h3>
                
                <!-- Down Payment Input -->
                <div class="form-group">
                    <label for="down-payment-input">Down Payment Received</label>
                    <div class="input-with-prefix">
                        <span class="prefix">₱</span>
                        <input type="number" id="down-payment-input" class="form-input payment-input"
                               min="0" step="0.01" placeholder="0.00" oninput="calculatePayments()">
                    </div>
                    <small class="input-hint">Amount paid during inquiry/approval</small>
                    <div id="downpayment-requirement" class="requirement-notice" style="display: none;">
                        <span class="requirement-icon">⚠️</span>
                        <span class="requirement-text">Minimum 50% downpayment required: <strong id="required-downpayment">₱0.00</strong></span>
                    </div>
                </div>

                <!-- Full Payment Input -->
                <div class="form-group">
                    <label for="full-payment-input">Full Payment Received</label>
                    <div class="input-with-prefix">
                        <span class="prefix">₱</span>
                        <input type="number" id="full-payment-input" class="form-input payment-input" 
                               min="0" step="0.01" placeholder="0.00" oninput="calculatePayments()">
                    </div>
                    <small class="input-hint">Final payment for booking confirmation</small>
                </div>
            </section>

            <!-- Payment Summary / Calculator Results -->
            <section class="payment-section payment-summary">
                <h3>📊 Payment Verification</h3>
                
                <div class="calculation-rows">
                    <div class="calc-row">
                        <span>Total Package Cost:</span>
                        <span id="calc-total-cost">₱0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>Down Payment:</span>
                        <span id="calc-down-payment" class="payment-amount">₱0.00</span>
                    </div>
                    <div class="calc-row">
                        <span>Full Payment:</span>
                        <span id="calc-full-payment" class="payment-amount">₱0.00</span>
                    </div>
                    <div class="calc-row total-paid-row">
                        <span>Total Paid:</span>
                        <span id="calc-total-paid" class="total-paid">₱0.00</span>
                    </div>
                    <div class="calc-row balance-row" id="balance-row">
                        <span>Remaining Balance:</span>
                        <span id="calc-balance" class="balance-amount">₱0.00</span>
                    </div>
                </div>

                <!-- Payment Status Indicator -->
                <div class="payment-status-box" id="payment-status-box">
                    <span class="status-icon" id="payment-status-icon">⏳</span>
                    <div class="status-text">
                        <strong id="payment-status-text">Pending Payment</strong>
                        <small id="payment-status-desc">Enter payment amounts above</small>
                    </div>
                </div>
            </section>

            <!-- Action Buttons -->
            <div class="payment-actions">
                <button class="btn-admin btn-secondary-admin" onclick="closePaymentCalculator()">Cancel</button>
                <button class="btn-admin btn-primary-admin" id="btn-confirm-payment" onclick="confirmWithPayment()">
                    Confirm & Save Payment
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Payment Sidebar Styles */
.payment-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    height: 100vh;
    width: 100%;
    display: none;
    z-index: 1100;
}

.payment-sidebar.active {
    display: flex;
}

.payment-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
}

.payment-content {
    position: relative;
    width: 100%;
    max-width: 450px;
    height: 100%;
    background: #fffdf8;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    animation: slideInRight 0.3s ease-out;
    margin-left: auto;
}

.payment-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 2px solid #4a1414;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #8a2927 0%, #6c1d12 100%);
    color: white;
}

.payment-header h2 {
    margin: 0;
    font-size: 1.2rem;
    color: white;
}

.btn-close-payment {
    background: rgba(255,255,255,0.2);
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
    color: white;
    padding: 0;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.btn-close-payment:hover {
    background: rgba(255,255,255,0.3);
}

.payment-body {
    flex: 1;
    overflow-y: auto;
    padding: 1.25rem;
}

.payment-section {
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #e0d5c7;
}

.payment-section:last-of-type {
    border-bottom: none;
}

.payment-section h3 {
    margin: 0 0 0.75rem 0;
    color: #4a1414;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.customer-summary .customer-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.customer-summary strong {
    color: #4a1414;
    font-size: 1.1rem;
}

.customer-summary span {
    color: #888;
    font-size: 0.9rem;
}

.order-items-summary {
    background: rgba(247, 241, 231, 0.5);
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    max-height: 150px;
    overflow-y: auto;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.4rem 0;
    border-bottom: 1px dashed #ddd;
    font-size: 0.9rem;
}

.summary-item:last-child {
    border-bottom: none;
}

.total-package-cost {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #4a1414;
    color: white;
    border-radius: 8px;
    font-size: 1rem;
}

.total-package-cost strong {
    font-size: 1.2rem;
}

/* Payment Input Styles */
.payment-input-section .form-group {
    margin-bottom: 1rem;
}

.payment-input-section label {
    display: block;
    margin-bottom: 0.4rem;
    font-weight: 500;
    color: #4a1414;
    font-size: 0.9rem;
}

.input-with-prefix {
    display: flex;
    align-items: center;
    background: white;
    border: 2px solid #d5a437;
    border-radius: 8px;
    overflow: hidden;
}

.input-with-prefix .prefix {
    padding: 0.6rem 0.75rem;
    background: #f7efe2;
    color: #4a1414;
    font-weight: 600;
    border-right: 1px solid #d5a437;
}

.input-with-prefix input {
    flex: 1;
    border: none;
    padding: 0.6rem;
    font-size: 1rem;
    font-family: inherit;
}

.input-with-prefix input:focus {
    outline: none;
}

.input-hint {
    color: #888;
    font-size: 0.8rem;
    margin-top: 0.25rem;
    display: block;
}

/* Calculation Summary */
.calculation-rows {
    background: rgba(247, 241, 231, 0.5);
    border-radius: 8px;
    padding: 0.75rem;
}

.calc-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px dashed #ddd;
    font-size: 0.95rem;
}

.calc-row:last-child {
    border-bottom: none;
}

.payment-amount {
    color: #4CAF50;
    font-weight: 500;
}

.total-paid-row {
    font-weight: 600;
    color: #4a1414;
    border-top: 1px solid #d5a437;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
}

.balance-row {
    font-weight: 700;
    color: #8a2927;
}

.balance-amount {
    color: #f44336;
}

.balance-amount.paid {
    color: #4CAF50;
}

/* Payment Status Box */
.payment-status-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    margin-top: 1rem;
    border-radius: 8px;
    background: #fff3e0;
    border: 1px solid #ffcc80;
}

.payment-status-box.fully-paid {
    background: #e8f5e9;
    border-color: #81c784;
}

.payment-status-box.pending {
    background: #ffebee;
    border-color: #ef9a9a;
}

.status-icon {
    font-size: 1.5rem;
}

.status-text {
    display: flex;
    flex-direction: column;
}

.status-text strong {
    color: #4a1414;
}

.status-text small {
    color: #888;
}

/* Action Buttons */
.payment-actions {
    padding: 1.25rem;
    border-top: 1px solid #e0d5c7;
    display: flex;
    gap: 0.75rem;
    background: #f9f7f4;
}

.payment-actions .btn-admin {
    flex: 1;
}

/* Loading State */
.payment-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #4a1414;
}

.payment-loading .spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e0d5c7;
    border-top-color: #4a1414;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}

/* Downpayment Requirement Notice */
.requirement-notice {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 1px solid #d4a574;
    border-radius: 8px;
    color: #856404;
    font-size: 0.9rem;
}

.requirement-notice .requirement-icon {
    font-size: 1.1rem;
}

.requirement-notice .requirement-text strong {
    color: #4a1414;
}

/* Mobile */
@media (max-width: 768px) {
    .payment-content {
        max-width: 100%;
    }
}
</style>

<script>
let currentPaymentId = null;
let currentPaymentType = null;
let currentPaymentAction = null;
let currentTotal = 0;

/**
 * Open Payment Calculator Sidebar
 * @param {number} id - Inquiry or Booking ID
 * @param {string} type - 'inquiry' or 'booking'
 * @param {string} action - 'approve' or 'confirm'
 */
function openPaymentCalculator(id, type, action) {
    currentPaymentId = id;
    currentPaymentType = type;
    currentPaymentAction = action;
    
    const sidebar = document.getElementById('payment-sidebar');
    const loading = document.getElementById('payment-loading');
    const body = document.getElementById('payment-body');
    const confirmBtn = document.getElementById('btn-confirm-payment');
    
    // Update button text based on action
    if (action === 'approve') {
        confirmBtn.textContent = 'Approve & Save Payment';
    } else if (action === 'confirm') {
        confirmBtn.textContent = 'Confirm & Save Payment';
    } else if (action === 'completed') {
        confirmBtn.textContent = 'Complete & Record Payment';
    }
    
    // Show loading state
    loading.style.display = 'flex';
    body.style.display = 'none';
    sidebar.classList.add('active');
    
    // Reset inputs
    document.getElementById('down-payment-input').value = '';
    document.getElementById('full-payment-input').value = '';
    
    // Fetch data
    fetch(`../api/get-${type}.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populatePaymentCalculator(data, type);
                loading.style.display = 'none';
                body.style.display = 'block';
            } else {
                if (typeof showArcError === 'function') {
                    showArcError('Failed to load order details. Please try again.');
                } else {
                    alert('Failed to load order details');
                }
                closePaymentCalculator();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showArcError === 'function') {
                showArcError('Failed to load order details. Please check your connection and try again.');
            } else {
                alert('Failed to load order details');
            }
            closePaymentCalculator();
        });
}

/**
 * Populate payment calculator with data
 */
function populatePaymentCalculator(data, type) {
    const record = data.record;
    currentTotal = data.total || 0;
    
    // Customer info
    document.getElementById('payment-customer-name').textContent = 
        record.customer_name || record.full_name || '--';
    document.getElementById('payment-event-date').textContent = 
        record.event_date ? new Date(record.event_date).toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        }) : '--';
    
    // Order items
    const itemsContainer = document.getElementById('payment-order-items');
    itemsContainer.innerHTML = '';
    
    if (data.items && data.items.length > 0) {
        data.items.forEach(item => {
            const isPackage = item.is_package == 1 || item.type === 'package';
            const icon = isPackage ? '📦' : '🍽️';
            // Ensure proper numeric conversion
            const unitPrice = parseFloat(item.unit_price) || parseFloat(item.price) || 0;
            const quantity = parseInt(item.quantity) || 1;
            const subtotal = parseFloat(item.subtotal) || (unitPrice * quantity) || 0;
            
            itemsContainer.innerHTML += `
                <div class="summary-item">
                    <span>${icon} ${item.name} x${quantity} @ ₱${unitPrice.toFixed(2)}</span>
                    <span>₱${subtotal.toFixed(2)}</span>
                </div>
            `;
        });
    } else {
        itemsContainer.innerHTML = '<p style="color: #888; text-align: center;">No items found</p>';
    }
    
    // Total cost
    document.getElementById('payment-total-cost').textContent = '₱' + currentTotal.toFixed(2);
    document.getElementById('calc-total-cost').textContent = '₱' + currentTotal.toFixed(2);

    // Show downpayment requirement for inquiries
    const requirementDiv = document.getElementById('downpayment-requirement');
    const requiredAmountEl = document.getElementById('required-downpayment');

    if (type === 'inquiry') {
        const requiredDownpayment = currentTotal * 0.5;
        requiredAmountEl.textContent = '₱' + requiredDownpayment.toFixed(2);
        requirementDiv.style.display = 'flex';
    } else {
        requirementDiv.style.display = 'none';
    }
    
    // Pre-fill existing payments if any
    if (record.down_payment > 0) {
        document.getElementById('down-payment-input').value = record.down_payment;
    }
    if (record.full_payment > 0) {
        document.getElementById('full-payment-input').value = record.full_payment;
    }
    
    // AUTO-FILL: If completing booking and full payment is empty, fill with remaining balance
    if (currentPaymentAction === 'completed' && !record.full_payment) {
        const downPayment = parseFloat(record.down_payment) || 0;
        const remainingBalance = currentTotal - downPayment;
        if (remainingBalance > 0) {
            document.getElementById('full-payment-input').value = remainingBalance.toFixed(2);
        }
    }
    
    // Calculate initial state
    calculatePayments();
}

/**
 * Calculate and display payment breakdown
 */
function calculatePayments() {
    const downPayment = parseFloat(document.getElementById('down-payment-input').value) || 0;
    const fullPayment = parseFloat(document.getElementById('full-payment-input').value) || 0;
    const totalPaid = downPayment + fullPayment;
    const balance = currentTotal - totalPaid;
    
    // Update display
    document.getElementById('calc-down-payment').textContent = '₱' + downPayment.toFixed(2);
    document.getElementById('calc-full-payment').textContent = '₱' + fullPayment.toFixed(2);
    document.getElementById('calc-total-paid').textContent = '₱' + totalPaid.toFixed(2);
    
    const balanceEl = document.getElementById('calc-balance');
    balanceEl.textContent = balance > 0 ? '₱' + balance.toFixed(2) : '₱0.00 (PAID)';
    balanceEl.className = balance <= 0 ? 'balance-amount paid' : 'balance-amount';
    
    // Update status box
    const statusBox = document.getElementById('payment-status-box');
    const statusIcon = document.getElementById('payment-status-icon');
    const statusText = document.getElementById('payment-status-text');
    const statusDesc = document.getElementById('payment-status-desc');
    
    if (balance <= 0) {
        statusBox.className = 'payment-status-box fully-paid';
        statusIcon.textContent = '✅';
        statusText.textContent = 'Fully Paid';
        statusDesc.textContent = 'Payment is complete!';
    } else if (totalPaid > 0) {
        statusBox.className = 'payment-status-box';
        statusIcon.textContent = '💳';
        statusText.textContent = 'Partial Payment';
        statusDesc.textContent = `Remaining: ₱${balance.toFixed(2)}`;
    } else {
        statusBox.className = 'payment-status-box pending';
        statusIcon.textContent = '⏳';
        statusText.textContent = 'Pending Payment';
        statusDesc.textContent = 'Enter payment amounts above';
    }
}

/**
 * Confirm action with payment data
 */
function confirmWithPayment() {
    if (!currentPaymentId || !currentPaymentType) return;

    const downPayment = parseFloat(document.getElementById('down-payment-input').value) || 0;
    const fullPayment = parseFloat(document.getElementById('full-payment-input').value) || 0;

    // For inquiry approval, require 50% downpayment
    if (currentPaymentType === 'inquiry') {
        const requiredDownpayment = currentTotal * 0.5;
        if (downPayment < requiredDownpayment) {
            if (typeof showArcError === 'function') {
                showArcError(`A minimum downpayment of ₱${requiredDownpayment.toFixed(2)} (50%) is required to confirm this inquiry.`);
            } else {
                alert(`A minimum downpayment of ₱${requiredDownpayment.toFixed(2)} (50%) is required to confirm this inquiry.`);
            }
            return;
        }
    }
    
    // Determine action based on type and stored action
    let action;
    if (currentPaymentType === 'inquiry') {
        action = 'approve';
    } else if (currentPaymentAction === 'completed') {
        action = 'completed';
    } else {
        action = 'confirm';
    }
    
    // Prepare data
    const updateData = {
        id: currentPaymentId,
        type: currentPaymentType,
        action: action,
        down_payment: downPayment,
        full_payment: fullPayment,
        total_amount: currentTotal
    };
    
    // Show loading animation
    if (typeof showArcLoading === 'function') {
        showArcLoading('Processing payment...');
    }
    
    // Disable the confirm button to prevent double submission
    const confirmBtn = document.getElementById('btn-confirm-payment');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.style.opacity = '0.6';
        confirmBtn.style.cursor = 'not-allowed';
    }
    
    // Send to appropriate endpoint
    const endpoint = currentPaymentType === 'inquiry' 
        ? '../api/update-inquiry-status.php'
        : '../api/update-booking-status.php';
    
    fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updateData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let actionText;
            if (action === 'approve') actionText = 'approved';
            else if (action === 'completed') actionText = 'completed';
            else actionText = 'confirmed';
            
            if (typeof showArcSuccess === 'function') {
                showArcSuccess(`Order ${actionText} successfully with payment recorded!`, function() {
                    closePaymentCalculator();
                    location.reload();
                });
            } else {
                alert(`✅ Order ${actionText} successfully with payment recorded!`);
                closePaymentCalculator();
                location.reload();
            }
        } else {
            console.error('Server error:', data);
            const errorMsg = 'Error: ' + (data.message || 'Failed to save payment.');
            if (typeof showArcError === 'function') {
                showArcError(errorMsg);
            } else {
                alert('❌ ' + errorMsg + '\n\nCheck XAMPP php_error.log for detailed error message.');
            }
        }
    })
    .catch(error => {
        console.error('Network/Error:', error);
        const errorMsg = 'Network error. Please check your connection and try again.';
        if (typeof showArcError === 'function') {
            showArcError(errorMsg);
        } else {
            alert('❌ ' + errorMsg + '\n\nCheck:\n1. XAMPP Apache is running\n2. Check XAMPP php_error.log\n3. Open F12 console for details');
        }
    });
}

/**
 * Close payment calculator
 */
function closePaymentCalculator() {
    document.getElementById('payment-sidebar').classList.remove('active');
    currentPaymentId = null;
    currentPaymentType = null;
    currentPaymentAction = null;
    currentTotal = 0;
}
</script>

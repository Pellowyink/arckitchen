/**
 * ARC Kitchen Unified Notification System
 * Replaces native alert() and confirm() with custom-styled modals
 * 
 * Design DNA:
 * - Modal: White/Cream background, 25px rounded corners
 * - Header: Deep Maroon (#4a1414) with White/Gold text
 * - Primary Button: Maroon (#8a2927), 10px rounded corners
 * - Secondary Button: Outline Maroon/Grey
 * - Position: Fixed center
 */

(function() {
    'use strict';

    // Create modal container if it doesn't exist
    function ensureModalContainer() {
        let container = document.getElementById('arc-notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'arc-notification-container';
            container.innerHTML = `
                <div id="arc-modal-overlay" class="arc-modal-overlay"></div>
                <div id="arc-modal" class="arc-modal">
                    <div class="arc-modal-header">
                        <h3 id="arc-modal-title">Notification</h3>
                    </div>
                    <div class="arc-modal-body">
                        <p id="arc-modal-message"></p>
                        <div id="arc-modal-loading" class="arc-loading-spinner" style="display: none;">
                            <div class="arc-spinner"></div>
                            <span>Loading...</span>
                        </div>
                    </div>
                    <div class="arc-modal-footer" id="arc-modal-footer">
                        <button id="arc-btn-secondary" class="arc-btn arc-btn-secondary">Cancel</button>
                        <button id="arc-btn-primary" class="arc-btn arc-btn-primary">OK</button>
                    </div>
                </div>
            `;
            document.body.appendChild(container);
            
            // Add styles
            addModalStyles();
        }
        return container;
    }

    // Add CSS styles for the modal
    function addModalStyles() {
        if (document.getElementById('arc-modal-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'arc-modal-styles';
        styles.textContent = `
            /* ARC Modal Overlay */
            .arc-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(2px);
                z-index: 9998;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }
            
            .arc-modal-overlay.active {
                opacity: 1;
                visibility: visible;
            }
            
            /* ARC Modal Container */
            .arc-modal {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0.9);
                background: #fffbf5;
                border-radius: 25px;
                box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
                z-index: 9999;
                min-width: 400px;
                max-width: 500px;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                overflow: hidden;
            }
            
            .arc-modal.active {
                opacity: 1;
                visibility: visible;
                transform: translate(-50%, -50%) scale(1);
            }
            
            /* ARC Modal Header */
            .arc-modal-header {
                background: #4a1414;
                padding: 1.25rem 1.5rem;
                text-align: center;
            }
            
            .arc-modal-header h3 {
                margin: 0;
                color: #fff;
                font-size: 1.1rem;
                font-weight: 600;
            }
            
            .arc-modal-header.warning {
                background: #8a2927;
            }
            
            .arc-modal-header.success {
                background: #4CAF50;
            }
            
            .arc-modal-header.error {
                background: #d32f2f;
            }
            
            .arc-modal-header.loading {
                background: #d5a437;
            }
            
            /* ARC Modal Body */
            .arc-modal-body {
                padding: 1.5rem;
                text-align: center;
            }
            
            .arc-modal-body p {
                margin: 0;
                color: #333;
                font-size: 1rem;
                line-height: 1.5;
            }
            
            /* ARC Modal Footer */
            .arc-modal-footer {
                padding: 1rem 1.5rem 1.5rem;
                display: flex;
                justify-content: center;
                gap: 1rem;
            }
            
            .arc-modal-footer.hidden {
                display: none;
            }
            
            /* ARC Buttons */
            .arc-btn {
                padding: 0.75rem 1.5rem;
                border-radius: 10px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                border: 2px solid transparent;
                font-size: 0.95rem;
                min-width: 100px;
            }
            
            .arc-btn-primary {
                background: #8a2927;
                color: white;
                border-color: #8a2927;
            }
            
            .arc-btn-primary:hover {
                background: #6c1d12;
                border-color: #6c1d12;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(138, 41, 39, 0.3);
            }
            
            .arc-btn-secondary {
                background: transparent;
                color: #666;
                border-color: #ccc;
            }
            
            .arc-btn-secondary:hover {
                border-color: #8a2927;
                color: #8a2927;
            }
            
            .arc-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
            }
            
            /* Loading Spinner */
            .arc-loading-spinner {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1rem;
                padding: 1rem 0;
            }
            
            .arc-spinner {
                width: 50px;
                height: 50px;
                border: 4px solid #e5d5c5;
                border-top-color: #8a2927;
                border-radius: 50%;
                animation: arc-spin 0.8s linear infinite;
            }
            
            @keyframes arc-spin {
                to { transform: rotate(360deg); }
            }
            
            .arc-loading-spinner span {
                color: #666;
                font-size: 0.9rem;
            }
            
            /* Mobile Responsive */
            @media (max-width: 600px) {
                .arc-modal {
                    min-width: 90%;
                    max-width: 90%;
                    margin: 0 5%;
                }
                
                .arc-modal-footer {
                    flex-direction: column;
                }
                
                .arc-btn {
                    width: 100%;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    // Current callback reference
    let currentCallback = null;
    let currentResolve = null;

    /**
     * Show ARC Modal
     * @param {string} type - 'alert', 'confirm', 'loading', 'success', 'error', 'warning'
     * @param {string} message - Message to display
     * @param {Function} callback - Callback function (for confirm)
     * @param {Object} options - Additional options
     */
    function showArcModal(type, message, callback, options = {}) {
        const container = ensureModalContainer();
        const overlay = document.getElementById('arc-modal-overlay');
        const modal = document.getElementById('arc-modal');
        const title = document.getElementById('arc-modal-title');
        const messageEl = document.getElementById('arc-modal-message');
        const loadingEl = document.getElementById('arc-modal-loading');
        const footer = document.getElementById('arc-modal-footer');
        const primaryBtn = document.getElementById('arc-btn-primary');
        const secondaryBtn = document.getElementById('arc-btn-secondary');
        const header = modal.querySelector('.arc-modal-header');

        // Reset classes
        header.className = 'arc-modal-header';
        footer.classList.remove('hidden');
        loadingEl.style.display = 'none';
        messageEl.style.display = 'block';

        // Set content based on type
        const titles = {
            alert: 'Notice',
            confirm: 'Confirm Action',
            loading: 'Please Wait',
            success: 'Success!',
            error: 'Error',
            warning: 'Warning'
        };

        title.textContent = options.title || titles[type] || 'Notification';
        messageEl.textContent = message;

        // Apply header styling
        if (['warning', 'error', 'success', 'loading'].includes(type)) {
            header.classList.add(type);
        }

        // Configure buttons based on type
        if (type === 'loading') {
            footer.classList.add('hidden');
            loadingEl.style.display = 'flex';
            messageEl.style.display = 'none';
        } else if (type === 'alert' || type === 'success' || type === 'error') {
            secondaryBtn.style.display = 'none';
            primaryBtn.textContent = options.buttonText || 'OK';
        } else if (type === 'confirm' || type === 'warning') {
            secondaryBtn.style.display = 'inline-block';
            secondaryBtn.textContent = options.cancelText || 'Cancel';
            primaryBtn.textContent = options.confirmText || 'Confirm';
        }

        // Store callbacks
        currentCallback = callback;

        // Button handlers
        const handlePrimary = () => {
            hideArcModal();
            if (typeof callback === 'function') {
                callback(true);
            }
            if (currentResolve) {
                currentResolve(true);
                currentResolve = null;
            }
        };

        const handleSecondary = () => {
            hideArcModal();
            if (typeof callback === 'function') {
                callback(false);
            }
            if (currentResolve) {
                currentResolve(false);
                currentResolve = null;
            }
        };

        const handleOverlay = (e) => {
            if (e.target === overlay && type !== 'loading') {
                handleSecondary();
            }
        };

        // Remove old listeners
        primaryBtn.replaceWith(primaryBtn.cloneNode(true));
        secondaryBtn.replaceWith(secondaryBtn.cloneNode(true));
        overlay.replaceWith(overlay.cloneNode(true));

        // Get fresh references
        const newPrimaryBtn = document.getElementById('arc-btn-primary');
        const newSecondaryBtn = document.getElementById('arc-btn-secondary');
        const newOverlay = document.getElementById('arc-modal-overlay');

        // Add new listeners
        newPrimaryBtn.addEventListener('click', handlePrimary);
        newSecondaryBtn.addEventListener('click', handleSecondary);
        newOverlay.addEventListener('click', handleOverlay);

        // Show modal
        requestAnimationFrame(() => {
            newOverlay.classList.add('active');
            modal.classList.add('active');
        });

        // Return promise for async/await support
        if (type === 'confirm' || type === 'warning') {
            return new Promise((resolve) => {
                currentResolve = resolve;
            });
        }
    }

    /**
     * Hide ARC Modal
     */
    function hideArcModal() {
        const overlay = document.getElementById('arc-modal-overlay');
        const modal = document.getElementById('arc-modal');
        
        if (overlay && modal) {
            overlay.classList.remove('active');
            modal.classList.remove('active');
        }
    }

    /**
     * Show loading modal
     * @param {string} message - Loading message
     */
    function showArcLoading(message = 'Loading...') {
        return showArcModal('loading', message);
    }

    /**
     * Show success modal
     * @param {string} message - Success message
     * @param {Function} callback - Callback after OK
     */
    function showArcSuccess(message, callback) {
        return showArcModal('success', message, callback);
    }

    /**
     * Show error modal
     * @param {string} message - Error message
     * @param {Function} callback - Callback after OK
     */
    function showArcError(message, callback) {
        return showArcModal('error', message, callback);
    }

    /**
     * Show warning/confirm modal
     * @param {string} message - Warning message
     * @param {Function} callback - Callback with boolean result
     */
    function showArcConfirm(message, callback) {
        return showArcModal('warning', message, callback, {
            title: 'Confirm Action',
            confirmText: 'Continue',
            cancelText: 'Cancel'
        });
    }

    /**
     * Show destructive action confirm
     * @param {string} itemName - Name of item being deleted
     * @param {Function} callback - Callback with boolean result
     */
    function showArcDeleteConfirm(itemName, callback) {
        const message = `Warning: This will permanently remove "${itemName}". This action cannot be undone. Continue?`;
        return showArcModal('warning', message, callback, {
            title: 'Delete Confirmation',
            confirmText: 'Delete',
            cancelText: 'Keep'
        });
    }

    /**
     * Show sync error with reload option
     * @param {string} context - Context of the error (e.g., "update status")
     */
    function showArcSyncError(context = 'perform action') {
        const message = `System Sync Error: Could not ${context}. Would you like to reload the page to sync?`;
        return showArcModal('error', message, (confirmed) => {
            if (confirmed) {
                window.location.reload();
            }
        }, {
            title: 'Sync Error',
            confirmText: 'Reload Page',
            cancelText: 'Dismiss'
        });
    }

    /**
     * Show wait/initialization modal
     * @param {string} message - Wait message
     */
    function showArcWait(message = 'System initializing, please wait...') {
        return showArcModal('loading', message);
    }

    // Expose global functions
    window.showArcModal = showArcModal;
    window.showArcLoading = showArcLoading;
    window.showArcSuccess = showArcSuccess;
    window.showArcError = showArcError;
    window.showArcConfirm = showArcConfirm;
    window.showArcDeleteConfirm = showArcDeleteConfirm;
    window.showArcSyncError = showArcSyncError;
    window.showArcWait = showArcWait;
    window.hideArcModal = hideArcModal;

    // Auto-initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureModalContainer);
    } else {
        ensureModalContainer();
    }

})();

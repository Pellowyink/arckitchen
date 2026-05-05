/**
 * ARC Kitchen Print Engine
 * Handles bulk selection, printing, and export functionality for Sales Report
 */

class PrintEngine {
    constructor() {
        this.selectedIds = new Set();
        this.bookingsData = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateSelectAllCheckbox();
    }

    setBookingsData(data) {
        this.bookingsData = data;
    }

    bindEvents() {
        // Select All checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });
        }

        // Individual checkboxes (delegated)
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('row-checkbox')) {
                const id = e.target.dataset.id;
                if (e.target.checked) {
                    this.selectedIds.add(id);
                } else {
                    this.selectedIds.delete(id);
                }
                this.updateSelectAllCheckbox();
                this.updateBulkActionButtons();
            }
        });

        // Bulk print button
        const bulkPrintBtn = document.getElementById('bulkPrintBtn');
        if (bulkPrintBtn) {
            bulkPrintBtn.addEventListener('click', () => this.bulkPrint());
        }

        // Export CSV button
        const exportCsvBtn = document.getElementById('exportCsvBtn');
        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', () => this.exportToCSV());
        }
    }

    toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checked;
            const id = cb.dataset.id;
            if (checked) {
                this.selectedIds.add(id);
            } else {
                this.selectedIds.delete(id);
            }
        });
        this.updateBulkActionButtons();
    }

    updateSelectAllCheckbox() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        
        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = checkedBoxes.length === checkboxes.length;
            selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
        }
    }

    updateBulkActionButtons() {
        const count = this.selectedIds.size;
        const bulkPrintBtn = document.getElementById('bulkPrintBtn');
        const selectedCountEl = document.getElementById('selectedCount');
        
        if (bulkPrintBtn) {
            bulkPrintBtn.disabled = count === 0;
            bulkPrintBtn.textContent = count > 0 
                ? `🖨️ Print Selected (${count})` 
                : '🖨️ Print Selected';
        }
        
        if (selectedCountEl) {
            selectedCountEl.textContent = count > 0 
                ? `${count} selected` 
                : '';
        }
    }

    bulkPrint() {
        if (this.selectedIds.size === 0) {
            if (typeof showArcError === 'function') {
                showArcError('Please select at least one booking to print');
            } else {
                alert('Please select at least one booking to print');
            }
            return;
        }

        const selectedBookings = this.bookingsData.filter(b => 
            this.selectedIds.has(b.id.toString())
        );

        this.openPrintWindow(selectedBookings);
    }

    openPrintWindow(bookings) {
        const printWindow = window.open('', '_blank');
        const currentDate = new Date().toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });

        let content = '';
        
        bookings.forEach((booking, index) => {
            const total = parseFloat(booking.total_amount || 0).toFixed(2);
            const downPayment = parseFloat(booking.down_payment || 0);
            const fullPayment = parseFloat(booking.full_payment || 0);
            const paid = (downPayment + fullPayment).toFixed(2);
            const balance = Math.max(0, parseFloat(booking.total_amount || 0) - parseFloat(paid)).toFixed(2);
            
            // Show payment breakdown only if both exist, otherwise just show total paid
            let paymentDetails = '';
            if (downPayment > 0 && fullPayment > 0) {
                // Both payments exist - show breakdown
                paymentDetails = `
                        <div class="receipt-row">
                            <span>Down Payment:</span>
                            <span>₱${downPayment.toFixed(2)}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Full Payment:</span>
                            <span>₱${fullPayment.toFixed(2)}</span>
                        </div>`;
            }
            
            content += `
                <div class="receipt-page">
                    <div class="receipt-header">
                        <img src="../assets/images/arc-logo.png" alt="ARC Kitchen" class="logo">
                        <h2>ARC KITCHEN</h2>
                        <p>Catering Services</p>
                        <p class="receipt-date">${currentDate}</p>
                    </div>
                    
                    <div class="receipt-body">
                        <div class="receipt-row">
                            <span>Receipt #:</span>
                            <span>ARC-${booking.id.toString().padStart(4, '0')}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Customer:</span>
                            <span>${(booking.customer_name || booking.full_name || 'N/A')}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Email:</span>
                            <span>${(booking.customer_email || booking.email || 'N/A')}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Event Date:</span>
                            <span>${new Date(booking.event_date).toLocaleDateString()}</span>
                        </div>
                        <div class="receipt-row">
                            <span>Guests:</span>
                            <span>${booking.guest_count || 0} pax</span>
                        </div>
                        
                        <div class="divider"></div>
                        
                        <div class="receipt-row">
                            <span>Total Cost:</span>
                            <span>₱${total}</span>
                        </div>
                        ${paymentDetails}
                        <div class="receipt-row" style="font-weight: bold;">
                            <span>Amount Paid:</span>
                            <span>₱${paid}</span>
                        </div>
                        ${balance > 0 ? `
                        <div class="receipt-row balance-due">
                            <span>Balance Due:</span>
                            <span>₱${balance}</span>
                        </div>
                        ` : `
                        <div class="receipt-row paid">
                            <span>PAID</span>
                            <span>✓</span>
                        </div>
                        `}
                        
                        <div class="divider"></div>
                        
                        <div class="receipt-row">
                            <span>Payment Status:</span>
                            <span>${(booking.payment_status || 'pending').toUpperCase()}</span>
                        </div>
                    </div>
                    
                    <div class="receipt-footer">
                        <p>Thank you for choosing ARC Kitchen!</p>
                        <p>For inquiries: info@arckitchen.com</p>
                    </div>
                </div>
            `;
            
            if (index < bookings.length - 1) {
                content += '<div class="page-break"></div>';
            }
        });

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>ARC Kitchen - Sales Receipts</title>
                <style>
                    @page { size: A4; margin: 20mm; }
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: 'Courier New', monospace;
                        background: white;
                        color: #333;
                        line-height: 1.6;
                    }
                    
                    .receipt-page {
                        max-width: 400px;
                        margin: 0 auto;
                        padding: 30px 20px;
                    }
                    
                    .receipt-header {
                        text-align: center;
                        border-bottom: 2px dashed #333;
                        padding-bottom: 15px;
                        margin-bottom: 20px;
                    }
                    
                    .receipt-header .logo {
                        width: 60px;
                        height: auto;
                        margin-bottom: 10px;
                    }
                    
                    .receipt-header h2 {
                        font-size: 1.5rem;
                        margin-bottom: 5px;
                        color: #4a1414;
                    }
                    
                    .receipt-header p {
                        font-size: 0.85rem;
                        color: #666;
                    }
                    
                    .receipt-date {
                        margin-top: 10px;
                        font-weight: bold;
                    }
                    
                    .receipt-body {
                        margin: 20px 0;
                    }
                    
                    .receipt-row {
                        display: flex;
                        justify-content: space-between;
                        padding: 8px 0;
                        font-size: 0.9rem;
                    }
                    
                    .receipt-row span:first-child {
                        color: #666;
                    }
                    
                    .receipt-row span:last-child {
                        font-weight: bold;
                        text-align: right;
                    }
                    
                    .divider {
                        border-top: 1px dashed #ccc;
                        margin: 15px 0;
                    }
                    
                    .balance-due {
                        color: #c62828;
                        font-size: 1.1rem;
                        font-weight: bold;
                        border-top: 2px dashed #333;
                        padding-top: 10px;
                        margin-top: 10px;
                    }
                    
                    .paid {
                        color: #2e7d32;
                        font-size: 1.1rem;
                        font-weight: bold;
                        border-top: 2px dashed #333;
                        padding-top: 10px;
                        margin-top: 10px;
                    }
                    
                    .receipt-footer {
                        text-align: center;
                        border-top: 2px dashed #333;
                        padding-top: 20px;
                        margin-top: 30px;
                        font-size: 0.8rem;
                        color: #666;
                    }
                    
                    .receipt-footer p {
                        margin: 5px 0;
                    }
                    
                    .page-break {
                        page-break-after: always;
                        border-top: 3px double #999;
                        margin: 40px 0;
                        padding-top: 40px;
                    }
                    
                    @media print {
                        .page-break {
                            page-break-after: always;
                        }
                        body {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                </style>
            </head>
            <body>
                ${content}
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        if (typeof showArcSuccess === 'function') {
            showArcSuccess('Opening print preview...');
        }
    }

    exportToCSV() {
        if (this.bookingsData.length === 0) {
            if (typeof showArcError === 'function') {
                showArcError('No data to export');
            } else {
                alert('No data to export');
            }
            return;
        }

        const headers = [
            'ID', 'Customer Name', 'Email', 'Phone', 'Event Date', 'Guests',
            'Total Amount', 'Down Payment', 'Full Payment', 'Total Paid',
            'Balance', 'Payment Status', 'Status', 'Archived Date'
        ];

        let csvContent = headers.join(',') + '\n';

        this.bookingsData.forEach(booking => {
            const total = parseFloat(booking.total_amount || 0);
            const downPayment = parseFloat(booking.down_payment || 0);
            const fullPayment = parseFloat(booking.full_payment || 0);
            const paid = downPayment + fullPayment;
            const balance = Math.max(0, total - paid);
            
            // Use full_name/email/phone as fallback for customer fields
            const customerName = booking.customer_name || booking.full_name || '';
            const customerEmail = booking.customer_email || booking.email || '';
            const customerPhone = booking.customer_phone || booking.phone || '';
            
            const row = [
                booking.id,
                `"${customerName.replace(/"/g, '""')}"`,
                `"${customerEmail.replace(/"/g, '""')}"`,
                `"${customerPhone.replace(/"/g, '""')}"`,
                booking.event_date,
                booking.guest_count || 0,
                total.toFixed(2),
                downPayment.toFixed(2),
                fullPayment.toFixed(2),
                paid.toFixed(2),
                balance.toFixed(2),
                booking.payment_status || 'pending',
                booking.status || '',
                booking.archived_at || ''
            ];
            
            csvContent += row.join(',') + '\n';
        });

        // Add summary
        csvContent += '\n';
        csvContent += '"SUMMARY",,,,,,\n';
        
        const totals = this.calculateTotals();
        csvContent += `"Total Revenue",,,,,,"${totals.revenue.toFixed(2)}"\n`;
        csvContent += `"Total Collected",,,,,,"${totals.collected.toFixed(2)}"\n`;
        csvContent += `"Total Balance",,,,,,"${totals.balance.toFixed(2)}"\n`;
        csvContent += `"Fully Paid Count",,,,,,"${totals.fullyPaid}"\n`;
        csvContent += `"Partial Count",,,,,,"${totals.partial}"\n`;
        csvContent += `"Pending Count",,,,,,"${totals.pending}"\n`;

        // Download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `ARC_Kitchen_Sales_Report_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        if (typeof showArcSuccess === 'function') {
            showArcSuccess('CSV file downloaded successfully!');
        }
    }

    calculateTotals() {
        let revenue = 0;
        let collected = 0;
        let balance = 0;
        let fullyPaid = 0;
        let partial = 0;
        let pending = 0;

        this.bookingsData.forEach(booking => {
            const total = parseFloat(booking.total_amount || 0);
            const paid = parseFloat(booking.down_payment || 0) + parseFloat(booking.full_payment || 0);
            
            revenue += total;
            collected += paid;
            balance += Math.max(0, total - paid);
            
            const status = booking.payment_status || 'pending';
            if (status === 'fully_paid') fullyPaid++;
            else if (status === 'partial') partial++;
            else pending++;
        });

        return { revenue, collected, balance, fullyPaid, partial, pending };
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    window.printEngine = new PrintEngine();
});

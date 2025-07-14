// File: admin/js/orders.js - Debug Version

class OrdersManager {
    constructor() {
        this.initializeEventListeners();
        this.loadOrders(); // Load initial data
    }

    initializeEventListeners() {
        // Filter form submission
        const filterForm = document.querySelector('.filters form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.loadOrders();
            });
        }

        // Real-time search
        const searchInput = document.getElementById('search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.loadOrders();
                }, 500); // Debounce search
            });
        }

        // Filter dropdowns
        const statusSelect = document.getElementById('status');
        const paymentSelect = document.getElementById('payment');
        
        if (statusSelect) {
            statusSelect.addEventListener('change', () => this.loadOrders());
        }
        
        if (paymentSelect) {
            paymentSelect.addEventListener('change', () => this.loadOrders());
        }
    }

    loadOrders(page = 1) {
        const formData = new FormData(document.querySelector('.filters form'));
        const params = new URLSearchParams(formData);
        params.append('page', page);

        // Debug: Log the current page URL and the fetch URL
        console.log('Current page URL:', window.location.href);
        console.log('Current page pathname:', window.location.pathname);
        console.log('Current page directory:', window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')));
        
      

        // Show loading indicator
        this.showLoading();

        // Try the original path first, but with better debugging
        const fetchUrl = `orders_data_controller.php?${params.toString()}`;
        

        fetch(fetchUrl)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                console.log('Response url:', response.url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status} - URL: ${response.url}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Success! Data received:', data);
                if (data.success) {
                    this.renderOrders(data.orders);
                    this.renderStats(data.stats);
                    this.renderPagination(data.pagination);
                } else {
                    this.showError(data.error || 'Failed to load orders');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                console.log('Error details:', error.message);
                
                // Try alternative paths if the first one fails
                this.tryAlternativePaths(params, 0);
            })
            .finally(() => {
                this.hideLoading();
            });
    }

   

    renderOrders(orders) {
        const tbody = document.querySelector('.orders-table tbody');
        if (!tbody) return;

        if (orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No orders found</td></tr>';
            return;
        }

        tbody.innerHTML = orders.map(order => `
            <tr>
                <td><strong>${this.escapeHtml(order.order_number)}</strong></td>
                <td>
                    <div>
                        <strong>${this.escapeHtml(order.shipping_first_name)} ${this.escapeHtml(order.shipping_last_name)}</strong><br>
                        <small>${this.escapeHtml(order.shipping_email)}</small>
                    </div>
                </td>
                <td>
                    ${this.formatDate(order.created_at)}<br>
                    <small>${this.formatTime(order.created_at)}</small>
                </td>
                <td><span class="status-badge">${order.item_count} items</span></td>
                <td><strong>$${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                <td><span class="status-badge status-${order.order_status}">${this.capitalize(order.order_status)}</span></td>
                <td><span class="status-badge payment-${order.payment_status}">${this.capitalize(order.payment_status)}</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewOrder(${order.id})">View</button>
                    <button class="btn btn-sm btn-warning" onclick="updateOrder(this)" 
                            data-order-id="${order.id}" 
                            data-order-status="${order.order_status}">Update</button>
                </td>
            </tr>
        `).join('');
    }

    renderStats(stats) {
        // Update stats cards if they exist
        const statsElements = {
            'total_orders': stats.total_orders,
            'pending_orders': stats.pending_orders,
            'processing_orders': stats.processing_orders,
            'shipped_orders': stats.shipped_orders,
            'delivered_orders': stats.delivered_orders,
            'pending_payments': stats.pending_payments,
            'total_revenue': '$' + parseFloat(stats.total_revenue || 0).toFixed(2)
        };

        Object.keys(statsElements).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.textContent = statsElements[key];
            }
        });
    }

    renderPagination(pagination) {
        const paginationContainer = document.querySelector('.pagination');
        if (!paginationContainer || pagination.total_pages <= 1) {
            if (paginationContainer) paginationContainer.innerHTML = '';
            return;
        }

        let paginationHTML = '';
        
        // Previous button
        if (pagination.current_page > 1) {
            paginationHTML += `<button onclick="ordersManager.loadOrders(${pagination.current_page - 1})" class="btn btn-sm">Previous</button>`;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === pagination.current_page ? 'btn-primary' : 'btn-secondary';
            paginationHTML += `<button onclick="ordersManager.loadOrders(${i})" class="btn btn-sm ${activeClass}">${i}</button>`;
        }

        // Next button
        if (pagination.current_page < pagination.total_pages) {
            paginationHTML += `<button onclick="ordersManager.loadOrders(${pagination.current_page + 1})" class="btn btn-sm">Next</button>`;
        }

        paginationContainer.innerHTML = paginationHTML;
    }

    showLoading() {
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.display = 'block';
        }
    }

    hideLoading() {
        const loadingElement = document.getElementById('loading');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }

    showError(message) {
        console.error('Error:', message);
        alert('Error: ' + message);
        
        // Also show error in the table
        const tbody = document.querySelector('.orders-table tbody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center" style="color: red;">Error: ${message}</td></tr>`;
        }
    }

    // Utility functions
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true
        });
    }

    capitalize(text) {
        if (!text) return '';
        return text.charAt(0).toUpperCase() + text.slice(1);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.ordersManager = new OrdersManager();
});

// Enhanced updateOrder function with dropdown selection
function updateOrder(button) {
    const orderId = button.getAttribute('data-order-id');
    const currentStatus = button.getAttribute('data-order-status');
    
    console.log('Update order:', orderId, 'Current status:', currentStatus);
    
    // Create and show status update modal
    const modal = createStatusUpdateModal(orderId, currentStatus);
    document.body.appendChild(modal);
    
    // Show modal
    modal.style.display = 'block';
    document.body.classList.add('modal-open');
}

function createStatusUpdateModal(orderId, currentStatus) {
    const modal = document.createElement('div');
    modal.className = 'status-update-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeStatusModal()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h3>Update Order Status</h3>
                <button class="modal-close" onclick="closeStatusModal()">&times;</button>
            </div>
            <div class="modal-content">
                <form id="statusUpdateForm">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="${orderId}">
                    
                    <div class="form-group">
                        <label for="new_status">Order Status</label>
                        <select name="new_status" id="new_status" required>
                            <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="processing" ${currentStatus === 'processing' ? 'selected' : ''}>Processing</option>
                            <option value="shipped" ${currentStatus === 'shipped' ? 'selected' : ''}>Shipped</option>
                            <option value="delivered" ${currentStatus === 'delivered' ? 'selected' : ''}>Delivered</option>
                            <option value="cancelled" ${currentStatus === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tracking_number">Tracking Number (Optional)</label>
                        <input type="text" name="tracking_number" id="tracking_number" placeholder="Enter tracking number">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Add any additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="submitStatusUpdate()" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    `;
    
    // Add styles if they don't exist
    if (!document.getElementById('status-modal-styles')) {
        const style = document.createElement('style');
        style.id = 'status-modal-styles';
        style.textContent = `
            .status-update-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1000;
            }
            
            .status-update-modal .modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
            }
            
            .status-update-modal .modal-container {
                position: relative;
                background: white;
                margin: 10% auto;
                width: 90%;
                max-width: 500px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                display: flex;
                flex-direction: column;
            }
            
            .status-update-modal .modal-header {
                padding: 20px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .status-update-modal .modal-header h3 {
                margin: 0;
                color: #333;
            }
            
            .status-update-modal .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .status-update-modal .modal-close:hover {
                color: #333;
            }
            
            .status-update-modal .modal-content {
                padding: 20px;
                flex: 1;
            }
            
            .status-update-modal .form-group {
                margin-bottom: 20px;
            }
            
            .status-update-modal .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #333;
            }
            
            .status-update-modal .form-group select,
            .status-update-modal .form-group input,
            .status-update-modal .form-group textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                font-family: inherit;
            }
            
            .status-update-modal .form-group select:focus,
            .status-update-modal .form-group input:focus,
            .status-update-modal .form-group textarea:focus {
                outline: none;
                border-color: #007bff;
                box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            }
            
            .status-update-modal .modal-footer {
                padding: 20px;
                border-top: 1px solid #eee;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            
            .status-update-modal .btn {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: bold;
                text-decoration: none;
                display: inline-block;
                transition: all 0.2s;
            }
            
            .status-update-modal .btn-primary {
                background: #007bff;
                color: white;
            }
            
            .status-update-modal .btn-primary:hover {
                background: #0056b3;
            }
            
            .status-update-modal .btn-secondary {
                background: #6c757d;
                color: white;
            }
            
            .status-update-modal .btn-secondary:hover {
                background: #545b62;
            }
            
            .status-update-modal .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            @media (max-width: 768px) {
                .status-update-modal .modal-container {
                    width: 95%;
                    margin: 5% auto;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    return modal;
}

function closeStatusModal() {
    const modal = document.querySelector('.status-update-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.removeChild(modal);
    }
}

function submitStatusUpdate() {
    const form = document.getElementById('statusUpdateForm');
    const formData = new FormData(form);
    const submitButton = document.querySelector('.status-update-modal .btn-primary');
    
    // Disable submit button and show loading
    submitButton.disabled = true;
    submitButton.textContent = 'Updating...';
    
    fetch('orders_controller.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(result => {
        // Close modal
        closeStatusModal();
        
        // Show success message
        showNotification('Order status updated successfully!', 'success');
        
        // Reload orders to reflect changes
        if (window.ordersManager) {
            window.ordersManager.loadOrders();
        }
    })
    .catch(error => {
        console.error('Error updating order status:', error);
        showNotification('Error updating order status: ' + error.message, 'error');
        
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.textContent = 'Update Status';
    });
}

function showNotification(message, type = 'info') {
    // Remove existing notification if any
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add notification styles if they don't exist
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 8px 12px;
                border-radius: 6px;
                color: white;
                font-weight: 500;
                font-size: 13px;
                z-index: 1001;
                max-width: 200px;
                min-width: 150px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideIn 0.2s ease-out;
                text-align: center;
            }
            
            .notification-success {
                background: #28a745;
            }
            
            .notification-error {
                background: #dc3545;
            }
            
            .notification-info {
                background: #17a2b8;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Auto-remove notification after 1 second
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, 1000);
}


function viewOrder(orderId) {
    console.log('View order:', orderId);
    
    // Create and show modal
    const modal = createOrderModal();
    document.body.appendChild(modal);
    
    // Show loading state
    const modalContent = modal.querySelector('.modal-content');
    modalContent.innerHTML = '<div class="loading-spinner">Loading order details...</div>';
    
    // Show modal
    modal.style.display = 'block';
    document.body.classList.add('modal-open');
    
    // Fetch order details
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            modalContent.innerHTML = html;
        })
        .catch(error => {
            console.error('Error fetching order details:', error);
            modalContent.innerHTML = `
                <div class="error-message">
                    <h3>Error Loading Order</h3>
                    <p>Failed to load order details: ${error.message}</p>
                    <button onclick="closeOrderModal()" class="btn btn-primary">Close</button>
                </div>
            `;
        });
}

function createOrderModal() {
    const modal = document.createElement('div');
    modal.className = 'order-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeOrderModal()"></div>
        <div class="modal-container">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button class="modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div class="modal-content">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button onclick="closeOrderModal()" class="btn btn-secondary">Close</button>
                <button onclick="printOrder()" class="btn btn-primary">Print</button>
            </div>
        </div>
    `;
    
    // Add styles if they don't exist
    if (!document.getElementById('modal-styles')) {
        const style = document.createElement('style');
        style.id = 'modal-styles';
        style.textContent = `
            .order-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 1000;
            }
            
            .modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
            }
            
            .modal-container {
                position: relative;
                background: white;
                margin: 2% auto;
                width: 90%;
                max-width: 1000px;
                max-height: 90vh;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                display: flex;
                flex-direction: column;
            }
            
            .modal-header {
                padding: 20px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .modal-header h2 {
                margin: 0;
                color: #333;
            }
            
            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .modal-close:hover {
                color: #333;
            }
            
            .modal-content {
                padding: 20px;
                flex: 1;
                overflow-y: auto;
            }
            
            .modal-footer {
                padding: 20px;
                border-top: 1px solid #eee;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            
            .loading-spinner {
                text-align: center;
                padding: 40px;
                color: #666;
            }
            
            .error-message {
                text-align: center;
                padding: 40px;
                color: #d32f2f;
            }
            
            .error-message h3 {
                margin-top: 0;
            }
            
            /* Order details styles */
            .order-details {
                font-family: Arial, sans-serif;
            }
            
            .order-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid #eee;
            }
            
            .order-header h3 {
                margin: 0;
                color: #333;
            }
            
            .order-meta {
                display: flex;
                gap: 10px;
            }
            
            .status-badge {
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .status-pending { background: #fff3cd; color: #856404; }
            .status-processing { background: #cce5ff; color: #004085; }
            .status-shipped { background: #d4edda; color: #155724; }
            .status-delivered { background: #d1ecf1; color: #0c5460; }
            .status-cancelled { background: #f8d7da; color: #721c24; }
            
            .payment-pending { background: #fff3cd; color: #856404; }
            .payment-completed { background: #d4edda; color: #155724; }
            .payment-failed { background: #f8d7da; color: #721c24; }
            
            .order-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 25px;
            }
            
            .info-section {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
            }
            
            .info-section h4 {
                margin: 0 0 10px 0;
                color: #333;
                font-size: 16px;
            }
            
            .info-item {
                margin-bottom: 8px;
                font-size: 14px;
            }
            
            .address {
                font-size: 14px;
                line-height: 1.4;
            }
            
            .order-items-section {
                margin-bottom: 25px;
            }
            
            .order-items-section h4 {
                margin: 0 0 15px 0;
                color: #333;
                font-size: 16px;
            }
            
            .items-list {
                border: 1px solid #eee;
                border-radius: 6px;
                overflow: hidden;
            }
            
            .order-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px;
                border-bottom: 1px solid #eee;
            }
            
            .order-item:last-child {
                border-bottom: none;
            }
            
            .item-info {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .item-image {
                width: 60px;
                height: 60px;
                overflow: hidden;
                border-radius: 4px;
                border: 1px solid #eee;
            }
            
            .item-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .item-name {
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .item-details {
                font-size: 14px;
                color: #666;
            }
            
            .item-total {
                font-weight: bold;
                color: #333;
            }
            
            .tracking-section {
                margin-bottom: 25px;
            }
            
            .tracking-section h4 {
                margin: 0 0 15px 0;
                color: #333;
                font-size: 16px;
            }
            
            .tracking-timeline {
                border-left: 2px solid #eee;
                padding-left: 20px;
            }
            
            .tracking-item {
                margin-bottom: 20px;
                position: relative;
            }
            
            .tracking-item:before {
                content: '';
                position: absolute;
                left: -26px;
                top: 5px;
                width: 10px;
                height: 10px;
                background: #007bff;
                border-radius: 50%;
            }
            
            .tracking-date {
                font-size: 12px;
                color: #666;
                margin-bottom: 5px;
            }
            
            .tracking-status {
                font-weight: bold;
                margin-bottom: 3px;
            }
            
            .tracking-message {
                font-size: 14px;
                color: #333;
                margin-bottom: 3px;
            }
            
            .tracking-number {
                font-size: 12px;
                color: #666;
            }
            
            .notes-section {
                margin-bottom: 25px;
            }
            
            .notes-section h4 {
                margin: 0 0 15px 0;
                color: #333;
                font-size: 16px;
            }
            
            .notes-content {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
                font-size: 14px;
                line-height: 1.4;
            }
            
            body.modal-open {
                overflow: hidden;
            }
            
            @media (max-width: 768px) {
                .modal-container {
                    width: 95%;
                    margin: 5% auto;
                }
                
                .order-info-grid {
                    grid-template-columns: 1fr;
                }
                
                .item-info {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }
                
                .order-item {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    return modal;
}

function closeOrderModal() {
    const modal = document.querySelector('.order-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.body.removeChild(modal);
    }
}

function printOrder() {
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Order Details</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .order-header { border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
                        .info-section { margin-bottom: 20px; }
                        .info-section h4 { margin: 0 0 10px 0; font-size: 14px; font-weight: bold; }
                        .order-item { border-bottom: 1px solid #eee; padding: 10px 0; }
                        .item-image { display: none; }
                        .status-badge { padding: 2px 6px; border: 1px solid #ccc; }
                        @media print { .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    ${modalContent.innerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
}
// Fetch and display orders, handle actions (view, update status, delete)
// Placeholder: Replace with AJAX calls to admin_api.php

document.addEventListener('DOMContentLoaded', function() {
    loadOrdersTable();

    function loadOrdersTable() {
        const tbody = document.getElementById('orders-table-body');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="8">Loading...</td></tr>';
        fetch('admin_api.php?action=get_orders')
            .then(res => res.json())
            .then(data => {
                // Debug: Show raw data in console
                console.log('Orders API response:', data);
                // Set table headers
                const table = tbody.closest('table');
                if (table) {
                    table.querySelector('thead').innerHTML = `
                        <tr>
                            <th>ID</th>
                            <th>Customer Name</th>
                            <th>Provider</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    `;
                }
                if (data.orders && data.orders.length > 0) {
                    tbody.innerHTML = data.orders.map(o => `
                        <tr>
                            <td>${o.id}</td>
                            <td>${o.customer_name}</td>
                            <td>${getProviderName(o.provider_id)}</td>
                            <td>${getServiceName(o.service_id)}</td>
                            <td>${o.status}</td>
                            <td>${o.request_date || ''}</td>
                            <td>
                                <button class="action-btn view-order" data-id="${o.id}">View</button>
                                <button class="action-btn delete-order" data-id="${o.id}">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    // Add event listeners for actions
                    document.querySelectorAll('.view-order').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            showOrderDetails(id);
                        });
                    });
                    document.querySelectorAll('.delete-order').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            if (confirm('Are you sure you want to delete this order?')) {
                                fetch('admin_api.php?action=delete_order&id=' + id)
                                    .then(res => res.json())
                                    .then(r => {
                                        if (r.success) {
                                            this.closest('tr').remove();
                                        } else {
                                            alert(r.message || 'Delete failed');
                                        }
                                    });
                            }
                        });
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="8">No orders found.</td></tr>';
                }
            })
            .catch((err) => {
                console.error('Orders API error:', err);
                tbody.innerHTML = '<tr><td colspan="8">Failed to load orders.</td></tr>';
            });
    }

    // Helper: Get provider name by id (sync, using cached data)
    const providerCache = {};
    function getProviderName(providerId) {
        if (!providerId) return '';
        if (providerCache[providerId]) return providerCache[providerId];
        // Synchronous XHR (not recommended for production, but works for admin panel)
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'admin_api.php?action=get_provider_details&id=' + providerId, false);
        xhr.send(null);
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success && data.provider && data.provider.name) {
                    providerCache[providerId] = data.provider.name;
                    return data.provider.name;
                }
            } catch (e) {}
        }
        return providerId;
    }

    // Helper: Get service name by id (sync, using cached data)
    const serviceCache = {};
    function getServiceName(serviceId) {
        if (!serviceId) return '';
        if (serviceCache[serviceId]) return serviceCache[serviceId];
        // Synchronous XHR (not recommended for production, but works for admin panel)
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_services.php?id=' + serviceId, false);
        xhr.send(null);
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success && data.service && data.service.name) {
                    serviceCache[serviceId] = data.service.name;
                    return data.service.name;
                }
            } catch (e) {}
        }
        return serviceId;
    }

    function showOrderDetails(id) {
        // Remove any existing modal
        let oldModal = document.getElementById('order-details-modal');
        if (oldModal) oldModal.remove();
        // Create modal
        let modal = document.createElement('div');
        modal.id = 'order-details-modal';
        modal.className = 'modal';
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.left = '0';
        modal.style.top = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.background = 'rgba(0,0,0,0.5)';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.zIndex = '9999';
        modal.innerHTML = `
            <div class="modal-content" style="background:#fff;max-width:600px;width:95vw;max-height:90vh;overflow-y:auto;padding:24px;position:relative;border-radius:10px;">
                <button id="close-order-details" style="position:absolute;top:10px;right:10px;font-size:1.2rem;background:none;border:none;cursor:pointer;">&times;</button>
                <h2 style="margin-top:0;">Order Details</h2>
                <div id="order-details-content">Loading...</div>
            </div>
        `;
        document.body.appendChild(modal);
        // Close logic
        modal.querySelector('#close-order-details').onclick = function() {
            modal.remove();
        };
        modal.onclick = function(e) {
            if (e.target === modal) modal.remove();
        };
        // Fetch order details
        fetch('admin_api.php?action=get_order_details&id=' + id)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    document.getElementById('order-details-content').innerHTML = '<div style="color:red">' + (data.message || 'Failed to load details') + '</div>';
                    return;
                }
                const o = data.order;
                const provider = data.provider;
                const service = data.service;
                const reviews = data.reviews;
                document.getElementById('order-details-content').innerHTML = `
                    <div style="margin-bottom:1rem;">
                        <div><b>Order ID:</b> ${o.id}</div>
                        <div><b>Customer Name:</b> ${o.customer_name}</div>
                        <div><b>Customer Phone:</b> ${o.customer_phone}</div>
                        <div><b>Address:</b> ${o.customer_address}</div>
                        <div><b>Status:</b> ${o.status}</div>
                        <div><b>Date:</b> ${o.request_date || ''}</div>
                    </div>
                    <hr/>
                    <h3>Provider</h3>
                    <div>${provider ? `<b>${provider.name}</b> (${provider.email}, ${provider.phone})` : 'N/A'}</div>
                    <hr/>
                    <h3>Service</h3>
                    <div>${service ? `<b>${service.name}</b> (${service.category}) - ₹${service.price}<br>${service.description || ''}` : 'N/A'}</div>
                    <hr/>
                    <h3>Reviews</h3>
                    <ul>${reviews.length ? reviews.map(r => `<li><b>${r.reviewer_name}</b>: ${r.rating}★<br>${r.comment || ''}</li>`).join('') : '<li>No reviews</li>'}</ul>
                `;
            });
    }
});
// Fetch and display users, handle actions (edit, delete)
// Placeholder: Replace with AJAX calls to admin_api.php

document.addEventListener('DOMContentLoaded', function() {
    loadUsersTable();

    function loadUsersTable() {
        const tbody = document.getElementById('users-table-body');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="7">Loading...</td></tr>';
        fetch('admin_api.php?action=get_users')
            .then(res => res.json())
            .then(data => {
                // Set table headers
                const table = tbody.closest('table');
                if (table) {
                    table.querySelector('thead').innerHTML = `
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    `;
                }
                if (data.users && data.users.length > 0) {
                    tbody.innerHTML = data.users.map(u => `
                        <tr>
                            <td>${u.id}</td>
                            <td>${u.name}</td>
                            <td>${u.email}</td>
                            <td>${u.phone || ''}</td>
                            <td>${u.created_at || ''}</td>
                            <td>
                                <button class="action-btn view-user" data-id="${u.id}">View</button>
                                <button class="action-btn delete-user" data-id="${u.id}">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    // Add event listeners for actions
                    document.querySelectorAll('.view-user').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            showUserDetails(id);
                        });
                    });
                    document.querySelectorAll('.delete-user').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const id = this.getAttribute('data-id');
                            if (confirm('Are you sure you want to delete this user?')) {
                                fetch('admin_api.php?action=delete_user&id=' + id)
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
                    tbody.innerHTML = '<tr><td colspan="7">No users found.</td></tr>';
                }
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="7">Failed to load users.</td></tr>';
            });
    }

    function showUserDetails(id) {
        // Remove any existing modal
        let oldModal = document.getElementById('user-details-modal');
        if (oldModal) oldModal.remove();
        // Create modal
        let modal = document.createElement('div');
        modal.id = 'user-details-modal';
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
                <button id="close-user-details" style="position:absolute;top:10px;right:10px;font-size:1.2rem;background:none;border:none;cursor:pointer;">&times;</button>
                <h2 style="margin-top:0;">User Details</h2>
                <div id="user-details-content">Loading...</div>
            </div>
        `;
        document.body.appendChild(modal);
        // Close logic
        modal.querySelector('#close-user-details').onclick = function() {
            modal.remove();
        };
        modal.onclick = function(e) {
            if (e.target === modal) modal.remove();
        };
        // Fetch user details
        fetch('admin_api.php?action=get_user_details&id=' + id)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    document.getElementById('user-details-content').innerHTML = '<div style="color:red">' + (data.message || 'Failed to load details') + '</div>';
                    return;
                }
                const u = data.user;
                const orders = data.orders;
                const reviews = data.reviews;
                document.getElementById('user-details-content').innerHTML = `
                    <div style="margin-bottom:1rem;">
                        <div><b>Name:</b> ${u.name}</div>
                        <div><b>Email:</b> ${u.email}</div>
                        <div><b>Phone:</b> ${u.phone || ''}</div>
                        <div><b>Created At:</b> ${u.created_at || ''}</div>
                    </div>
                    <hr/>
                    <h3>Orders</h3>
                    <ul>${orders.length ? orders.map(o => `<li><b>Order ID:</b> ${o.id}, <b>Status:</b> ${o.status}, <b>Date:</b> ${o.request_date || ''}</li>`).join('') : '<li>No orders</li>'}</ul>
                    <hr/>
                    <h3>Reviews</h3>
                    <ul>${reviews.length ? reviews.map(r => `<li><b>Rating:</b> ${r.rating}★<br>${r.comment || ''}</li>`).join('') : '<li>No reviews</li>'}</ul>
                `;
            });
    }
});
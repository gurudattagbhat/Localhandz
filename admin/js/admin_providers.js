// Fetch and display providers, handle actions (approve, edit, delete, view, delete)
// Placeholder: Replace with AJAX calls to admin_api.php

document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('providers-table').querySelector('tbody');
    fetch('admin_api.php?action=get_providers')
        .then(res => res.json())
        .then(data => {
            if (data.providers && data.providers.length > 0) {
                tbody.innerHTML = data.providers.map(p => `
                    <tr>
                        <td>${p.id}</td>
                        <td>${p.name}</td>
                        <td>${p.email}</td>
                        <td>${p.status || ''}</td>
                        <td>
                            <button class="action-btn view-provider" data-id="${p.id}">View</button>
                            <button class="action-btn delete-provider" data-id="${p.id}">Delete</button>
                        </td>
                    </tr>
                `).join('');
                // Add event listeners for view and delete
                document.querySelectorAll('.view-provider').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        showProviderDetails(id);
                    });
                });
                document.querySelectorAll('.delete-provider').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.getAttribute('data-id');
                        if (confirm('Are you sure you want to delete this provider?')) {
                            fetch('admin_api.php?action=delete_provider&id=' + id)
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
                tbody.innerHTML = '<tr><td colspan="5">No providers found.</td></tr>';
            }
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="5">Failed to load providers.</td></tr>';
        });

    // Provider details modal logic
    const modal = document.getElementById('provider-details-modal');
    const modalContent = document.getElementById('provider-details-content');
    const closeBtn = document.getElementById('close-provider-details');
    closeBtn.addEventListener('click', () => { modal.style.display = 'none'; });
    window.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
    });

    function showProviderDetails(id) {
        modalContent.innerHTML = 'Loading...';
        modal.style.display = 'flex';
        fetch('admin_api.php?action=get_provider_details&id=' + id)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    modalContent.innerHTML = '<div style="color:red">' + (data.message || 'Failed to load details') + '</div>';
                    return;
                }
                const p = data.provider;
                const services = data.services;
                const reviews = data.reviews;
                modalContent.innerHTML = `
                    <h3>Info</h3>
                    <div><b>Name:</b> ${p.name}</div>
                    <div><b>Email:</b> ${p.email}</div>
                    <div><b>Phone:</b> ${p.phone}</div>
                    <div><b>Service:</b> ${p.service}</div>
                    <div><b>Area:</b> ${p.area}</div>
                    <div><b>Status:</b> ${p.status || ''}</div>
                    <hr/>
                    <h3>Services</h3>
                    <ul>${services.length ? services.map(s => `<li><b>${s.name}</b> (${s.category}) - ₹${s.price}<br>${s.description || ''}</li>`).join('') : '<li>No services</li>'}</ul>
                    <hr/>
                    <h3>Reviews</h3>
                    <ul>${reviews.length ? reviews.map(r => `<li><b>${r.reviewer_name}</b>: ${r.rating}★<br>${r.comment || ''}</li>`).join('') : '<li>No reviews</li>'}</ul>
                `;
            });
    }
});
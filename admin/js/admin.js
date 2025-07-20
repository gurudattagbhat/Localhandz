document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const toggleBtn = document.getElementById('toggle-btn');
    const closeBtn = document.getElementById('close-sidebar-btn');

    function openSidebar() {
        sidebar.classList.remove('closed');
        sidebar.classList.add('open');
        mainContent.classList.remove('sidebar-closed');
    }
    function closeSidebar() {
        sidebar.classList.add('closed');
        sidebar.classList.remove('open');
        mainContent.classList.add('sidebar-closed');
    }

    toggleBtn.addEventListener('click', function() {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    closeBtn.addEventListener('click', closeSidebar);

    // Set initial sidebar state
    if (window.innerWidth <= 900) {
        closeSidebar();
    } else {
        openSidebar();
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 900) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    const menuItems = document.querySelectorAll('.sidebar-item');
    const logoutMenu = document.getElementById('logout-menu');

    // Logout
    if (logoutMenu) {
        logoutMenu.addEventListener('click', function() {
            fetch('admin_api.php?action=logout')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'admin_login.html';
                    }
                });
        });
    }

    // Fetch and update dashboard stats
    function updateDashboardStats() {
        fetch('admin_api.php?action=get_stats')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.stats) {
                    document.getElementById('total-users').textContent = data.stats.users;
                    document.getElementById('total-providers').textContent = data.stats.providers;
                    document.getElementById('total-orders').textContent = data.stats.orders;
                    document.getElementById('total-feedback').textContent = data.stats.feedback;
                } else {
                    showDashboardError(data.message || 'No data returned from API.');
                }
            })
            .catch(error => {
                showDashboardError('API error: ' + error);
            });
    }
    function showDashboardError(msg) {
        let err = document.getElementById('dashboard-error');
        if (!err) {
            err = document.createElement('div');
            err.id = 'dashboard-error';
            err.style.color = 'red';
            err.style.fontWeight = 'bold';
            err.style.margin = '16px 0';
            document.querySelector('.dashboard-grid').prepend(err);
        }
        err.textContent = 'Dashboard Error: ' + msg;    }
    updateDashboardStats();

    // Add click functionality to dashboard stat cards
    function addClickableCardListeners() {
        const clickableCards = document.querySelectorAll('.clickable-card[data-section]');
        clickableCards.forEach(card => {
            card.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                showSectionFromCard(section);
            });
        });
    }

    // Function to show a section from a card click
    function showSectionFromCard(sectionName) {
        const sections = document.querySelectorAll('.main-content > div, .main-content > section');
        
        // Hide all sections and remove active-section class
        sections.forEach(sec => {
            sec.style.display = 'none';
            sec.classList.remove('active-section');
        });
        
        // Show selected section with correct display style and add active-section class
        const showSection = document.getElementById(sectionName);
        if (showSection) {
            if (sectionName === 'dashboard') {
                showSection.style.display = 'grid';
            } else {
                showSection.style.display = 'block';
            }
            showSection.classList.add('active-section');
            
            // Load data for the specific section
            if (sectionName === 'providers') {
                loadProvidersTable();
            } else if (sectionName === 'users') {
                loadUsersTable();
            } else if (sectionName === 'orders') {
                loadOrdersTable();
            } else if (sectionName === 'feedback') {
                loadFeedback();
            }
        }
        
        // Update sidebar active state
        document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
        const sidebarItem = document.querySelector(`.sidebar-item[data-section="${sectionName}"]`);
        if (sidebarItem) {
            sidebarItem.classList.add('active');
        }
    }

    // Initialize clickable cards
    addClickableCardListeners();

    // Theme mode toggle logic
    const modeToggleBtn = document.getElementById('mode-toggle-btn');
    const modeIcon = document.getElementById('mode-icon');
    const modeLabel = document.getElementById('mode-label');

    function setTheme(mode) {
        if (mode === 'dark') {
            document.body.classList.add('dark-mode');
            modeIcon.classList.remove('fa-sun');
            modeIcon.classList.add('fa-moon');
            modeLabel.textContent = 'Dark Mode';
        } else {
            document.body.classList.remove('dark-mode');
            modeIcon.classList.remove('fa-moon');
            modeIcon.classList.add('fa-sun');
            modeLabel.textContent = 'Light Mode';
        }
        localStorage.setItem('theme', mode);
    }

    modeToggleBtn.addEventListener('click', function() {
        const isDark = document.body.classList.contains('dark-mode');
        setTheme(isDark ? 'light' : 'dark');
    });

    // On load, set theme from localStorage
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    // Profile dropdown logic
    const profileTrigger = document.getElementById('profile-trigger');
    const profileDropdown = document.getElementById('profile-dropdown');

    document.addEventListener('click', function(event) {
        // Toggle dropdown if profile icon is clicked
        if (profileTrigger.contains(event.target)) {
            profileDropdown.classList.toggle('active');
        } else {
            // Close dropdown if clicked outside
            profileDropdown.classList.remove('active');
        }
    });

    // Edit Profile Modal Logic
    const editProfileLink = document.getElementById('edit-profile-link');
    const editProfileModal = document.getElementById('edit-profile-modal');
    const closeEditProfileBtn = document.getElementById('close-edit-profile');
    const editProfileForm = document.getElementById('edit-profile-form');
    const editProfileMsg = document.getElementById('edit-profile-msg');
    const editAdminUsername = document.getElementById('edit-admin-username');
    const editAdminPassword = document.getElementById('edit-admin-password');

    editProfileLink.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Edit Profile link clicked'); // Debug log
        // Fetch current profile
        fetch('admin_api.php?action=get_admin_profile')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.profile) {
                    editAdminUsername.value = data.profile.username;
                    editAdminPassword.value = '';
                    editProfileMsg.textContent = '';
                    editProfileModal.style.display = 'flex';
                } else {
                    editProfileMsg.textContent = data.message || 'Could not load profile.';
                    editProfileModal.style.display = 'flex';
                }
            });
    });
    closeEditProfileBtn.addEventListener('click', function() {
        editProfileModal.style.display = 'none';
    });
    editProfileModal.addEventListener('click', function(e) {
        if (e.target === editProfileModal) editProfileModal.style.display = 'none';
    });
    editProfileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        editProfileMsg.textContent = 'Saving...';
        const formData = new FormData(editProfileForm);
        fetch('admin_api.php?action=update_admin_profile', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                editProfileMsg.style.color = 'green';
                editProfileMsg.textContent = 'Profile updated!';
                setTimeout(() => {
                    editProfileModal.style.display = 'none';
                    location.reload();
                }, 1000);
            } else {
                editProfileMsg.style.color = 'red';
                editProfileMsg.textContent = data.message || 'Update failed.';
            }
        })
        .catch(() => {
            editProfileMsg.style.color = 'red';
            editProfileMsg.textContent = 'Network error.';
        });
    });

    // Section switching logic for static dashboard (event delegation for robustness)
    const sidebarNav = document.querySelector('.sidebar nav ul');
    const sections = document.querySelectorAll('.main-content > div, .main-content > section');
    sidebarNav.addEventListener('click', function(e) {
        const target = e.target.closest('.sidebar-item[data-section]');
        if (target) {
            e.preventDefault();
            const section = target.getAttribute('data-section');
            // Hide all sections and remove active-section class
            sections.forEach(sec => {
                sec.style.display = 'none';
                sec.classList.remove('active-section');
            });
            // Show selected section with correct display style and add active-section class
            const showSection = document.getElementById(section);
            if (showSection) {
                if (section === 'dashboard') {
                    showSection.style.display = 'grid';
                } else {
                    showSection.style.display = 'block';
                }
                showSection.classList.add('active-section');
                // Load providers data if providers section is shown
                if (section === 'providers') {
                    loadProvidersTable();
                }
                // Load users data if users section is shown
                if (section === 'users') {
                    loadUsersTable();
                }
                // Load orders data if orders section is shown
                if (section === 'orders') {
                    loadOrdersTable();
                }
                // Load feedback data if feedback section is shown
                if (section === 'feedback') {
                    loadFeedback();
                }
            }
            // Set active class
            document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
            target.classList.add('active');
        }
    });
    // Show dashboard by default
    sections.forEach(sec => {
        sec.style.display = 'none';
        sec.classList.remove('active-section');
    });
    const dashboard = document.getElementById('dashboard');
    if (dashboard) {
        dashboard.style.display = 'grid';
        dashboard.classList.add('active-section');
    }

    // Load providers data into the table
    function loadProvidersTable() {
        const tbody = document.getElementById('providers-table-body');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="8">Loading...</td></tr>';
        fetch('admin_api.php?action=get_providers')
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
                            <th>Service</th>
                            <th>Area</th>
                            <th>Photo</th>
                            <th>Actions</th>
                        </tr>
                    `;
                }
                if (data.providers && data.providers.length > 0) {
                    tbody.innerHTML = data.providers.map(p => `
                        <tr>
                            <td>${p.id}</td>
                            <td>${p.name}</td>
                            <td>${p.email}</td>
                            <td>${p.phone}</td>
                            <td>${p.service}</td>
                            <td>${p.area}</td>
                            <td><img src="${p.photo && p.photo !== '' ? '/code/uploads/' + p.photo.replace(/^uploads[\\/]/, '') : '/code/admin/images/default-profile.png'}" alt="Photo" style="width:40px;height:40px;border-radius:50%;object-fit:cover;"></td>
                            <td>
                                <button class="action-btn view-provider" data-id="${p.id}">View</button>
                                <button class="action-btn delete-provider" data-id="${p.id}">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                    // Add event listeners for actions
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
                    tbody.innerHTML = '<tr><td colspan="8">No providers found.</td></tr>';
                }
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="8">Failed to load providers.</td></tr>';
            });
    }

    // Load users data into the table
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
                            <td>${u.phone}</td>
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

    // Load orders data into the table (for dashboard Orders section)
    function loadOrdersTable() {
        const tbody = document.getElementById('orders-table-body');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="8">Loading...</td></tr>';
        fetch('admin_api.php?action=get_orders')
            .then(res => res.json())
            .then(data => {
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
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="8">Failed to load orders.</td></tr>';
            });
    }

    // Helper: Get provider name by id (sync, using cached data)
    const providerCache = {};
    function getProviderName(providerId) {
        if (!providerId) return '';
        if (providerCache[providerId]) return providerCache[providerId];
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
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/code/get_services.php?id=' + serviceId, false);
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

    // Show user details modal (full details, using a popup)
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
                        <div><b>Phone:</b> ${u.phone}</div>
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

    // Show provider details modal (full details, using a popup)
    function showProviderDetails(id) {
        // Remove any existing modal
        let oldModal = document.getElementById('provider-details-modal');
        if (oldModal) oldModal.remove();
        // Create modal
        let modal = document.createElement('div');
        modal.id = 'provider-details-modal';
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
                <button id="close-provider-details" style="position:absolute;top:10px;right:10px;font-size:1.2rem;background:none;border:none;cursor:pointer;">&times;</button>
                <h2 style="margin-top:0;">Provider Details</h2>
                <div id="provider-details-content">Loading...</div>
            </div>
        `;
        document.body.appendChild(modal);
        // Close logic
        modal.querySelector('#close-provider-details').onclick = function() {
            modal.remove();
        };
        modal.onclick = function(e) {
            if (e.target === modal) modal.remove();
        };
        // Fetch provider details
        fetch('admin_api.php?action=get_provider_details&id=' + id)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    document.getElementById('provider-details-content').innerHTML = '<div style="color:red">' + (data.message || 'Failed to load details') + '</div>';
                    return;
                }
                const p = data.provider;
                const services = data.services;
                const reviews = data.reviews;
                document.getElementById('provider-details-content').innerHTML = `
                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
                        <img src="${p.photo && p.photo !== '' ? '/code/uploads/' + p.photo.replace(/^uploads[\\/]/, '') : '/code/admin/images/default-profile.png'}" alt="Photo" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">
                        <div style="min-width:200px;">
                            <div><b>Name:</b> ${p.name}</div>
                            <div><b>Email:</b> ${p.email}</div>
                            <div><b>Phone:</b> ${p.phone}</div>
                            <div><b>Service:</b> ${p.service}</div>
                            <div><b>Area:</b> ${p.area}</div>
                            <div><b>Created At:</b> ${p.created_at}</div>
                        </div>
                    </div>
                    <hr/>
                    <h3>Services Offered</h3>
                    <ul>${services.length ? services.map(s => `<li><b>${s.name}</b> (${s.category}) - ₹${s.price}<br>${s.description || ''}</li>`).join('') : '<li>No services</li>'}</ul>
                    <hr/>
                    <h3>Reviews & Ratings</h3>
                    <ul>${reviews.length ? reviews.map(r => `<li><b>${r.reviewer_name}</b>: ${r.rating}★<br>${r.comment || ''}</li>`).join('') : '<li>No reviews</li>'}</ul>
                `;
            });
    }

    // Show order details modal (reuse from admin_orders.js if needed)
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
                document.getElementById('order-details-content').innerHTML = `
                    <div style="margin-bottom:1rem;">
                        <div><b>Order ID:</b> ${o.id}</div>
                        <div><b>Customer Name:</b> ${o.customer_name}</div>
                        <div><b>Provider:</b> ${getProviderName(o.provider_id)}</div>
                        <div><b>Service:</b> ${getServiceName(o.service_id)}</div>
                        <div><b>Status:</b> ${o.status}</div>
                        <div><b>Date:</b> ${o.request_date || ''}</div>
                    </div>
                `;
            });
    }

    // === FEEDBACK SECTION ===
    async function loadFeedback() {
        try {
            const res = await fetch('admin_api.php?action=get_feedback');
            const data = await res.json();
            const tbody = document.getElementById('feedback-table-body');
            tbody.innerHTML = '';
            if (data.success && Array.isArray(data.feedback)) {
                data.feedback.forEach(fb => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${fb.id}</td>
                        <td>${fb.name}</td>
                        <td>${fb.email}</td>
                        <td style="max-width:300px;white-space:pre-line;word-break:break-word;">${fb.message}</td>
                        <td>${fb.submitted_at || fb.created_at || ''}</td>
                        <td><button class="delete-feedback-btn" data-id="${fb.id}">Delete</button></td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No feedback found.</td></tr>';
            }
        } catch (e) {
            document.getElementById('feedback-table-body').innerHTML = '<tr><td colspan="6" style="text-align:center;">Error loading feedback.</td></tr>';
        }
    }

    // Delete feedback
    if (document.getElementById('feedback-table-body')) {
        document.getElementById('feedback-table-body').addEventListener('click', async function(e) {
            if (e.target.classList.contains('delete-feedback-btn')) {
                const id = e.target.getAttribute('data-id');
                if (confirm('Delete this feedback?')) {
                    const res = await fetch('admin_api.php?action=delete_feedback', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    });
                    const data = await res.json();
                    if (data.success) loadFeedback();
                    else alert('Failed to delete feedback.');
                }
            }
        });
    }

    // Show feedback section when sidebar clicked
    const feedbackSidebar = document.querySelector('[data-section="feedback"]');
    if (feedbackSidebar) {
        feedbackSidebar.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.main-content > div').forEach(div => div.style.display = 'none');
            document.getElementById('feedback').style.display = 'block';
            loadFeedback();
        });
    }
});
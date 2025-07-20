// Show admin username in navbar and handle logout/session check
fetch('admin_api.php?action=check_admin_session')
    .then(res => res.json())
    .then(data => {
        if (data.logged_in) {
            document.getElementById('navbar-admin-user').textContent = data.username;
        } else {
            window.location.href = 'admin_login.html';
        }
    });

document.getElementById('admin-logout-btn').onclick = function() {
    fetch('admin_api.php?action=admin_logout')
        .then(() => {
            window.location.href = 'admin_login.html';
        });
}; 
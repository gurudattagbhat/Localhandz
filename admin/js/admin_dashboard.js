// Fetch and display dashboard stats and recent activity
// Placeholder: Replace with AJAX calls to admin_api.php

document.addEventListener('DOMContentLoaded', function() {
    // Fetch stats
    fetch('admin_api.php?action=get_stats')
        .then(res => res.json())
        .then(stats => {
            document.getElementById('stat-users').querySelector('p').textContent = stats.users ?? '0';
            document.getElementById('stat-providers').querySelector('p').textContent = stats.providers ?? '0';
            document.getElementById('stat-orders').querySelector('p').textContent = stats.orders ?? '0';
            document.getElementById('stat-feedback').querySelector('p').textContent = stats.feedback ?? '0';
        });
    // Fetch recent activity
    fetch('admin_api.php?action=get_recent_activity')
        .then(res => res.json())
        .then(data => {
            if (data.activity && data.activity.length > 0) {
                document.getElementById('recent-activity-list').innerHTML = '<ul>' + data.activity.map(a => `<li>${a}</li>`).join('') + '</ul>';
            } else {
                document.getElementById('recent-activity-list').innerHTML = '<div>No recent activity.</div>';
            }
        });
}); 
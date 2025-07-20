// Fetch and display feedback, handle actions (view, delete)
// Placeholder: Replace with AJAX calls to admin_api.php

document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('feedback-table').querySelector('tbody');
    fetch('admin_api.php?action=get_feedback')
        .then(res => res.json())
        .then(data => {
            if (data.feedback && data.feedback.length > 0) {
                tbody.innerHTML = data.feedback.map(f => `
                    <tr>
                        <td>${f.id}</td>
                        <td>${f.user}</td>
                        <td>${f.provider}</td>
                        <td>${f.rating}</td>
                        <td>${f.comment}</td>
                        <td>${f.date}</td>
                        <td>
                            <button class=\"action-btn\" data-id=\"${f.id}\" data-action=\"view\">View</button>
                            <button class=\"action-btn\" data-id=\"${f.id}\" data-action=\"delete\">Delete</button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="7">No feedback found.</td></tr>';
            }
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="7">Failed to load feedback.</td></tr>';
        });
}); 
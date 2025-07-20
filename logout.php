<?php
session_start();
header('Content-Type: application/json');

// Destroy the session
session_unset();
session_destroy();

// Return a success response
echo json_encode(['success' => true]);
exit();
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    checkLoginStatus();

    // Dropdown toggle functionality
    const profileIcon = document.getElementById('welcome-message');
    const dropdownMenu = document.getElementById('dropdown-menu');

    profileIcon.addEventListener('click', function () {
        dropdownMenu.classList.toggle('show');
    });

    // Close the dropdown if clicked outside
    document.addEventListener('click', function (event) {
        if (!profileIcon.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove('show');
        }
    });

    // Logout functionality
    const logoutButton = document.getElementById('logout');
    logoutButton.addEventListener('click', async function (e) {
        e.preventDefault();
        try {
            const response = await fetch('logout.php');
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    // Reload the page to reflect the logged-out state
                    window.location.reload();
                } else {
                    console.error('Logout failed');
                }
            } else {
                console.error('Logout request failed with status:', response.status);
            }
        } catch (error) {
            console.error('Error during logout:', error);
        }
    });
});
</script>
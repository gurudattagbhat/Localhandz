<?php
session_start();
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Handz - Service Provider Platform</title>
    
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js" defer></script>
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="navbar">
        <div class="navbar-left">
            <button class="toggle-btn" id="toggle-btn"><i class="fas fa-bars"></i></button>
            <div class="logo">
                <img src="/assets/images/Logomain.jpg" alt="Local Handz Logo">
            </div>
        </div>
        <div class="navbar-right">
            <label class="theme-switch" for="theme-checkbox" title="Toggle theme">
                <input type="checkbox" id="theme-checkbox" />
                <div class="slider round">
                    <i class="fas fa-sun slider-icon sun"></i>
                    <i class="fas fa-moon slider-icon moon"></i>
                </div>
            </label>
            <div class="profile-trigger" id="profile-trigger">
                <img id="navbar-profile-img" src="<?php echo $_SESSION['profile_picture'] ?? '/assets/images/default-profile.png'; ?>" 
                     alt="Profile" class="navbar-profile-pic">
                <span id="navbar-profile-name" class="navbar-text"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
            </div>
            <div class="profile-dropdown" id="profile-dropdown">
                <a href="/templates/dashboard/profile.php">View Profile</a>
                <a href="/templates/dashboard/edit-profile.php">Edit Profile</a>
                <a href="/templates/dashboard/settings.php">Settings</a>
                <a href="/api/logout.php">Log out</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html> 
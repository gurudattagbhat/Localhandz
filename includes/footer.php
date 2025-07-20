    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="sidebar" id="sidebar">
        <nav>
            <ul>
                <li><a href="/templates/dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="/templates/dashboard/services.php"><i class="fas fa-tools"></i> Manage Services</a></li>
                <li><a href="/templates/dashboard/orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="/templates/dashboard/analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="/templates/dashboard/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    
    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('theme-checkbox');
        const currentTheme = localStorage.getItem('theme');

        if (currentTheme === 'dark') {
            document.body.classList.add('dark-mode');
            if (themeToggle) themeToggle.checked = true;
        }

        if (themeToggle) {
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
            });
        }

        // Sidebar toggle functionality
        const toggleBtn = document.getElementById('toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.querySelector('.main-content');
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('collapsed');
            });
        }

        // Profile dropdown functionality
        const profileTrigger = document.getElementById('profile-trigger');
        const profileDropdown = document.getElementById('profile-dropdown');

        if (profileTrigger && profileDropdown) {
            profileTrigger.addEventListener('click', function() {
                profileDropdown.classList.toggle('show');
            });

            window.addEventListener('click', function(event) {
                if (!profileTrigger.contains(event.target) && !profileDropdown.contains(event.target)) {
                    profileDropdown.classList.remove('show');
                }
            });
        }
    </script>
</body>
</html> 
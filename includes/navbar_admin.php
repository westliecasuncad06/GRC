<!-- Navbar -->
<nav class="navbar">
    <div class="navbar-brand">
        <button type="button" class="hamburger-menu" id="sidebarToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <span class="navbar-title">Global Reciprocal Colleges</span>
        <span class="navbar-title-mobile">GRC</span>
    </div>
    <div class="navbar-user">
        <span>Welcome, <?php echo $_SESSION['first_name']; ?></span>
        <div class="user-dropdown">
            <button type="button" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-cog" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu">
                <a href="settings.php" class="dropdown-item">Settings</a>
                <a href="../php/logout.php" class="dropdown-item">Logout</a>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[navbar_admin] DOMContentLoaded');
    // Dropdown functionality for multiple dropdowns
    document.querySelectorAll('.user-dropdown').forEach(function(dropdown) {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');

        if (toggle && menu) {
            toggle.addEventListener('click', function(event) {
                console.log('[navbar_admin] dropdown toggle clicked');
                event.stopPropagation();
                // Close other dropdowns first
                document.querySelectorAll('.dropdown-menu.show').forEach(function(otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.remove('show');
                        const otherToggle = otherMenu.closest('.user-dropdown')?.querySelector('.dropdown-toggle');
                        if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
                    }
                });
                const isOpen = menu.classList.toggle('show');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
            if (!event.target.closest('.user-dropdown')) {
                menu.classList.remove('show');
            }
        });
    });

    // Sidebar toggle functionality
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            document.body.classList.toggle('sidebar-collapsed');
        });
    }

    // Close sidebar when clicking outside (for mobile)
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        const isMobile = window.innerWidth <= 900;

        if (isMobile && !isCollapsed && sidebar && !sidebar.contains(event.target) && toggle && !toggle.contains(event.target)) {
            document.body.classList.add('sidebar-collapsed');
        }
    });

    // Prevent sidebar click from closing it
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>

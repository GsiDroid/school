document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('toggled');
        });
    }

    // Sidebar submenu toggle
    const submenu_links = document.querySelectorAll('.sidebar-nav .has-submenu > a');
    submenu_links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            if (parent.classList.contains('active')) {
                parent.classList.remove('active');
            } else {
                // Close other open submenus
                document.querySelectorAll('.sidebar-nav .has-submenu.active').forEach(open_submenu => {
                    open_submenu.classList.remove('active');
                });
                parent.classList.add('active');
            }
        });
    });
});

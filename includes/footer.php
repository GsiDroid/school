</div> <!-- End of main-content -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('sidebar-collapsed');
            document.getElementById('main-content').classList.toggle('main-content-expanded');
        });
        
        // Theme selector functionality
        document.querySelectorAll('.theme-btn').forEach(button => {
            button.addEventListener('click', function() {
                const theme = this.getAttribute('data-theme');
                let primaryColor, secondaryColor;
                
                switch(theme) {
                    case 'blue':
                        primaryColor = '#4e73df';
                        secondaryColor = '#2e59d9';
                        break;
                    case 'green':
                        primaryColor = '#1cc88a';
                        secondaryColor = '#169b6b';
                        break;
                    case 'dark':
                        primaryColor = '#5a5c69';
                        secondaryColor = '#484a54';
                        break;
                    case 'orange':
                        primaryColor = '#f6c23e';
                        secondaryColor = '#dda20a';
                        break;
                    case 'purple':
                        primaryColor = '#6f42c1';
                        secondaryColor = '#5a32a3';
                        break;
                }
                
                document.documentElement.style.setProperty('--primary-color', primaryColor);
                document.documentElement.style.setProperty('--secondary-color', secondaryColor);
                
                // Update welcome banner background
                const welcomeBanner = document.querySelector('.welcome-banner');
                if (welcomeBanner) {
                    welcomeBanner.style.background = `linear-gradient(135deg, ${primaryColor} 0%, ${secondaryColor} 100%)`;
                }
                
                // Store theme preference
                localStorage.setItem('theme', theme);
            });
        });
        
        // Load saved theme preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.querySelector(`.theme-${savedTheme}`).click();
            }
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Initialize popovers
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
        
        // Global search functionality
        document.querySelector('.navbar-search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const searchQuery = this.value.trim();
                if (searchQuery) {
                    window.location.href = `search.php?q=${encodeURIComponent(searchQuery)}`;
                }
            }
        });
    </script>
</body>
</html>
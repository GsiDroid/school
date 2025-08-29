document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        new DataTable('#usersTable');
        
        // Handle edit user button clicks
        const editButtons = document.querySelectorAll('.edit-user');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const userName = this.getAttribute('data-name');
                const userEmail = this.getAttribute('data-email');
                const userRole = this.getAttribute('data-role');
                
                document.getElementById('userModalLabel').textContent = 'Edit User';
                document.getElementById('user_id').value = userId;
                document.getElementById('name').value = userName;
                document.getElementById('email').value = userEmail;
                document.getElementById('role').value = userRole;
                document.getElementById('password-label').textContent = 'Password (Change)';
                document.getElementById('password-help').style.display = 'inline';
                document.getElementById('password').required = false;
            });
        });
        
        // Handle add new user button
        const addUserButton = document.querySelector('[data-bs-target="#userModal"]:not(.edit-user)');
        addUserButton.addEventListener('click', function() {
            document.getElementById('userModalLabel').textContent = 'Add New User';
            document.getElementById('user_id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('role').value = 'staff';
            document.getElementById('password-label').textContent = 'Password';
            document.getElementById('password-help').style.display = 'none';
            document.getElementById('password').required = true;
        });
    });
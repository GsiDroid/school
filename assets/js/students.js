document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const table = new DataTable('#studentsTable', {
            paging: false,  // Disable DataTables pagination as we're using custom pagination
            info: false,     // Hide DataTables info as we're using custom pagination
            searching: false // Disable DataTables search as we're using custom search
        });
        
        // Handle delete student button click
        const deleteButtons = document.querySelectorAll('.delete-student');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const studentId = this.getAttribute('data-id');
                const studentName = this.getAttribute('data-name');
                
                document.getElementById('studentId').value = studentId;
                document.getElementById('studentName').textContent = studentName;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));
                deleteModal.show();
            });
        });
    });
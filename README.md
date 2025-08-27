# School Management System

A comprehensive web-based school management system built with PHP, MySQL, and modern web technologies. This system provides complete management capabilities for schools including student management, fee management, attendance tracking, exam management, and more.

## Features

### 🎓 Student Management
- Complete student registration and profile management
- Student document upload and management
- Guardian information management
- Student transfer and graduation tracking
- Bulk student operations

### 💰 Fee Management
- Fee structure management by class and category
- Fee invoice generation
- Payment collection and tracking
- Fee concessions and scholarships
- Payment receipts and reports
- Bulk invoice generation

### 📊 Attendance Management
- Daily attendance tracking
- Attendance reports and analytics
- Multiple attendance statuses (Present, Absent, Late, Excused)
- Class-wise attendance management

### 📝 Exam Management
- Exam scheduling and management
- Result entry and management
- Grade calculation and reports
- Subject-wise exam tracking
- Result export and printing

### 💸 Expense Management
- Expense tracking and categorization
- Budget management
- Expense approval workflow
- Receipt management
- Financial reports

### 📸 Gallery Management
- Photo gallery with categories
- Event photo management
- Featured images
- Bulk image upload

### 📧 Communication
- Email and SMS templates
- Communication logs
- Bulk messaging capabilities

### 🔧 System Administration
- User management with role-based access
- System settings management
- Activity logging
- Database backup and restore
- Theme customization

## System Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.2 or higher
- **Web Server**: Apache 2.4+ or Nginx
- **PHP Extensions**:
  - PDO and PDO_MySQL
  - JSON
  - MBString
  - FileInfo
  - GD (for image processing)
  - OpenSSL (for security)

### Client Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Minimum screen resolution: 1024x768

## Installation

### Method 1: Automatic Installation (Recommended)

1. **Download and Extract**
   ```bash
   # Download the system files to your web server directory
   # Extract to your web server root (e.g., /var/www/html/ or htdocs/)
   ```

2. **Set Permissions**
   ```bash
   # Make sure the following directories are writable
   chmod 755 config/
   chmod 755 uploads/
   chmod 755 assets/img/
   ```

3. **Run Installation Wizard**
   - Open your web browser
   - Navigate to: `http://your-domain.com/install.php`
   - Follow the installation wizard steps:
     - System requirements check
     - Database configuration
     - Administrator account creation
     - System installation

4. **Complete Installation**
   - The system will automatically create all necessary database tables
   - Default data will be inserted
   - Configuration files will be generated
   - Installation lock file will be created

### Method 2: Manual Installation

1. **Database Setup**
   ```sql
   -- Create database
   CREATE DATABASE school_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
   -- Import the database schema
   mysql -u username -p school_management < database/school_management_fixed.sql
   ```

2. **Configuration**
   - Copy `config/database.php.example` to `config/database.php`
   - Update database credentials in `config/database.php`

3. **Directory Setup**
   ```bash
   mkdir -p uploads/documents uploads/images uploads/receipts
   mkdir -p assets/img/events
   chmod 755 uploads/ assets/img/
   ```

4. **Create Admin User**
   ```sql
   INSERT INTO users (name, email, password, role) 
   VALUES ('Admin', 'admin@school.com', '$2y$10$...', 'admin');
   ```

## Default Login Credentials

After installation, you can login with:
- **Email**: The email you provided during installation
- **Password**: The password you set during installation

## Directory Structure

```
school-management/
├── api/                    # API endpoints
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   └── img/               # Images and icons
├── attendance/            # Attendance management
├── backup/                # Database backup tools
├── communication/         # Communication module
├── config/                # Configuration files
├── database/              # Database schema files
├── exams/                 # Exam management
├── expenses/              # Expense management
├── fees/                  # Fee management
├── gallery/               # Photo gallery
├── includes/              # Common includes
├── students/              # Student management
├── uploads/               # File uploads
│   ├── documents/         # Student documents
│   ├── images/            # Profile images
│   └── receipts/          # Payment receipts
├── users/                 # User management
├── index.php              # Main dashboard
├── login.php              # Login page
├── install.php            # Installation wizard
└── README.md              # This file
```

## Security Features

- **Password Hashing**: All passwords are hashed using PHP's password_hash()
- **SQL Injection Protection**: Prepared statements throughout the application
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: CSRF tokens for form submissions
- **Session Security**: Secure session handling
- **File Upload Security**: Validated file uploads with type checking
- **Role-based Access Control**: Different access levels for different user roles

## User Roles

### Administrator
- Full system access
- User management
- System configuration
- Database backup/restore
- All module access

### Staff
- Student management
- Attendance management
- Exam management
- Fee collection
- Limited administrative functions

### Viewer
- Read-only access to most modules
- Limited functionality
- No administrative access

## Configuration

### Database Configuration
Edit `config/database.php` to update database settings:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_management');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### System Settings
System settings can be configured through the admin panel:
- School information
- Email settings
- SMS settings
- Theme preferences
- Session timeout

## Backup and Restore

### Automatic Backup
- Navigate to Backup & Restore module
- Create manual backups
- Download backup files
- Restore from backup files

### Manual Backup
```bash
# Database backup
mysqldump -u username -p school_management > backup.sql

# Files backup
tar -czf school_files_backup.tar.gz /path/to/school-management/
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Errors**
   - Ensure upload directories are writable
   - Check file permissions for config directory

3. **Page Not Found Errors**
   - Ensure mod_rewrite is enabled (if using .htaccess)
   - Check web server configuration

4. **Upload Errors**
   - Check PHP upload limits in php.ini
   - Verify directory permissions
   - Check file size limits

### Error Logs
- Check PHP error logs: `/var/log/php_errors.log`
- Check web server logs: `/var/log/apache2/error.log`
- Application logs are stored in the database

## Support

For support and documentation:
- Check the system's built-in help documentation
- Review the activity logs for error tracking
- Contact system administrator for technical issues

## License

This School Management System is provided as-is for educational and commercial use. Please ensure compliance with your local data protection and privacy laws.

## Updates

To update the system:
1. Backup your current installation
2. Download the latest version
3. Replace files (excluding uploads/ and config/)
4. Run any database migration scripts
5. Test the system thoroughly

## Contributing

To contribute to this project:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**Note**: This system is designed for educational institutions and should be used in compliance with local educational regulations and data protection laws.

-- School Management System Database Schema (Fixed Version)

-- Create database
CREATE DATABASE IF NOT EXISTS school_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE school_management;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'viewer') NOT NULL DEFAULT 'staff',
    profile_image VARCHAR(255) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_email (email),
    INDEX idx_user_role (role)
) ENGINE=InnoDB;

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Classes table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    section VARCHAR(20) DEFAULT NULL,
    capacity INT DEFAULT NULL,
    teacher_id INT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_class_name (name, section)
) ENGINE=InnoDB;

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_no VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    date_of_birth DATE NOT NULL,
    blood_group VARCHAR(5) DEFAULT NULL,
    religion VARCHAR(30) DEFAULT NULL,
    nationality VARCHAR(30) DEFAULT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    current_class_id INT DEFAULT NULL,
    admission_date DATE NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'transferred', 'graduated') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (current_class_id) REFERENCES classes(id) ON DELETE SET NULL,
    INDEX idx_student_admission_no (admission_no),
    INDEX idx_student_class (current_class_id),
    INDEX idx_student_name (first_name, last_name)
) ENGINE=InnoDB;

-- Student guardians table
CREATE TABLE IF NOT EXISTS student_guardians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    relation VARCHAR(30) NOT NULL,
    name VARCHAR(100) NOT NULL,
    occupation VARCHAR(50) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_guardian_student (student_id)
) ENGINE=InnoDB;

-- Student documents table
CREATE TABLE IF NOT EXISTS student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_name VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_document_student (student_id),
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB;

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    remarks TEXT DEFAULT NULL,
    marked_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (student_id, class_id, date),
    INDEX idx_attendance_date (date),
    INDEX idx_attendance_student (student_id),
    INDEX idx_attendance_class (class_id)
) ENGINE=InnoDB;

-- Subjects table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subject_name (subject_name),
    INDEX idx_subject_code (subject_code)
) ENGINE=InnoDB;

-- Exams table
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(100) NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    total_marks DECIMAL(10, 2) NOT NULL,
    passing_marks DECIMAL(10, 2) NOT NULL,
    description TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT DEFAULT NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_exam_class (class_id),
    INDEX idx_exam_subject (subject_id),
    INDEX idx_exam_date (exam_date)
) ENGINE=InnoDB;

-- Exam Results table
CREATE TABLE IF NOT EXISTS exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('present', 'absent') NOT NULL DEFAULT 'present',
    remarks TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT DEFAULT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_exam_result (exam_id, student_id),
    INDEX idx_result_exam (exam_id),
    INDEX idx_result_student (student_id)
) ENGINE=InnoDB;

-- Fee categories table
CREATE TABLE IF NOT EXISTS fee_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Fee structures table
CREATE TABLE IF NOT EXISTS fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    fee_category_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    term VARCHAR(20) NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    due_date DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_fee_structure (class_id, fee_category_id, term, academic_year),
    INDEX idx_fee_structure_class (class_id),
    INDEX idx_fee_structure_category (fee_category_id),
    INDEX idx_fee_structure_term (term, academic_year)
) ENGINE=InnoDB;

-- Fee concessions table
CREATE TABLE IF NOT EXISTS fee_concessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_category_id INT NOT NULL,
    concession_type ENUM('percentage', 'fixed') NOT NULL,
    concession_value DECIMAL(10, 2) NOT NULL,
    reason TEXT DEFAULT NULL,
    academic_year VARCHAR(9) NOT NULL,
    approved_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_concession_student (student_id),
    INDEX idx_concession_category (fee_category_id)
) ENGINE=InnoDB;

-- Fee invoices table
CREATE TABLE IF NOT EXISTS fee_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(20) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    term VARCHAR(20) NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    paid_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('unpaid', 'partially_paid', 'paid', 'overdue') NOT NULL DEFAULT 'unpaid',
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice_student (student_id),
    INDEX idx_invoice_class (class_id),
    INDEX idx_invoice_term (term, academic_year),
    INDEX idx_invoice_status (status)
) ENGINE=InnoDB;

-- Fee invoice items table
CREATE TABLE IF NOT EXISTS fee_invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    fee_category_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    concession_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    final_amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE,
    INDEX idx_invoice_item_invoice (invoice_id),
    INDEX idx_invoice_item_category (fee_category_id)
) ENGINE=InnoDB;

-- Fee payments table
CREATE TABLE IF NOT EXISTS fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(20) NOT NULL UNIQUE,
    invoice_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'cheque', 'bank_transfer', 'online') NOT NULL,
    payment_date DATE NOT NULL,
    transaction_reference VARCHAR(50) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    received_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_payment_invoice (invoice_id),
    INDEX idx_payment_student (student_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB;

-- Expense categories table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    budget DECIMAL(10, 2) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_no VARCHAR(20) NOT NULL UNIQUE,
    expense_category_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'cheque', 'bank_transfer', 'online') NOT NULL,
    reference_no VARCHAR(50) DEFAULT NULL,
    description TEXT NOT NULL,
    receipt_file VARCHAR(255) DEFAULT NULL,
    is_recurring BOOLEAN NOT NULL DEFAULT FALSE,
    recurring_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT NULL,
    created_by INT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_expense_category (expense_category_id),
    INDEX idx_expense_date (expense_date),
    INDEX idx_expense_status (approval_status)
) ENGINE=InnoDB;

-- Gallery categories table
CREATE TABLE IF NOT EXISTS gallery_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Gallery images table
CREATE TABLE IF NOT EXISTS gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    event_date DATE DEFAULT NULL,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    uploaded_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES gallery_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_image_category (category_id),
    INDEX idx_image_status (status)
) ENGINE=InnoDB;

-- Communication templates table
CREATE TABLE IF NOT EXISTS communication_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('email', 'sms') NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    content TEXT NOT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Communication logs table
CREATE TABLE IF NOT EXISTS communication_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('email', 'sms') NOT NULL,
    recipient_type ENUM('student', 'guardian', 'staff', 'other') NOT NULL,
    recipient_id INT DEFAULT NULL,
    recipient_contact VARCHAR(100) NOT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    sent_by INT DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_communication_type (type),
    INDEX idx_communication_recipient (recipient_type, recipient_id),
    INDEX idx_communication_status (status)
) ENGINE=InnoDB;

-- System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_description TEXT DEFAULT NULL,
    is_public BOOLEAN NOT NULL DEFAULT FALSE,
    updated_by INT DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Backup logs table
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    backup_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('available', 'deleted', 'restored') NOT NULL DEFAULT 'available',
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_backup_date (backup_date),
    INDEX idx_backup_status (status)
) ENGINE=InnoDB;

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_description, is_public) VALUES
('school_name', 'School Management System', 'Name of the school', 1),
('school_address', '123 Education Street, Knowledge City', 'Address of the school', 1),
('school_phone', '+1234567890', 'Contact number of the school', 1),
('school_email', 'info@schoolms.com', 'Email address of the school', 1),
('school_website', 'https://www.schoolms.com', 'Website of the school', 1),
('system_theme', 'blue', 'Default theme for the system', 0),
('admin_passcode', '623264', 'Passcode for admin registration', 0),
('currency_symbol', '$', 'Currency symbol for financial transactions', 1),
('session_timeout', '30', 'Session timeout in minutes', 0),
('backup_frequency', 'daily', 'Frequency of automatic backups', 0),
('smtp_host', '', 'SMTP host for sending emails', 0),
('smtp_port', '', 'SMTP port for sending emails', 0),
('smtp_username', '', 'SMTP username for sending emails', 0),
('smtp_password', '', 'SMTP password for sending emails', 0),
('sms_api_key', '', 'API key for SMS service', 0),
('sms_sender_id', '', 'Sender ID for SMS service', 0);

-- Create triggers

-- Update fee invoice balance when paid amount changes
DELIMITER //
CREATE TRIGGER update_invoice_balance_after_update
BEFORE UPDATE ON fee_invoices
FOR EACH ROW
BEGIN
    SET NEW.balance = NEW.total_amount - NEW.paid_amount;
    
    IF NEW.paid_amount = 0 THEN
        SET NEW.status = 'unpaid';
    ELSEIF NEW.paid_amount < NEW.total_amount THEN
        SET NEW.status = 'partially_paid';
    ELSEIF NEW.paid_amount >= NEW.total_amount THEN
        SET NEW.status = 'paid';
    END IF;
    
    IF NEW.status != 'paid' AND CURDATE() > NEW.due_date THEN
        SET NEW.status = 'overdue';
    END IF;
END //
DELIMITER ;

-- Update fee invoice status when a new payment is added
DELIMITER //
CREATE TRIGGER update_invoice_after_payment
AFTER INSERT ON fee_payments
FOR EACH ROW
BEGIN
    DECLARE total_paid DECIMAL(10, 2);
    DECLARE invoice_total DECIMAL(10, 2);
    
    -- Calculate total paid amount for this invoice
    SELECT SUM(amount) INTO total_paid
    FROM fee_payments
    WHERE invoice_id = NEW.invoice_id;
    
    -- Get invoice total amount
    SELECT total_amount INTO invoice_total
    FROM fee_invoices
    WHERE id = NEW.invoice_id;
    
    -- Update invoice paid amount and status
    UPDATE fee_invoices
    SET paid_amount = total_paid,
        balance = total_amount - total_paid,
        status = CASE
            WHEN total_paid = 0 THEN 'unpaid'
            WHEN total_paid < invoice_total THEN 'partially_paid'
            ELSE 'paid'
        END
    WHERE id = NEW.invoice_id;
    
    -- Check if overdue
    UPDATE fee_invoices
    SET status = 'overdue'
    WHERE id = NEW.invoice_id
    AND status != 'paid'
    AND CURDATE() > due_date;
END //
DELIMITER ;

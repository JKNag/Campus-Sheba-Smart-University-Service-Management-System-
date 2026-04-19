-- =====================================================
-- CAMPUS SHEBA - Smart University Service Management
-- Complete Database Setup Script
-- =====================================================

-- Drop database if exists (for fresh installation)
DROP DATABASE IF EXISTS campus_sheba;

-- Create database
CREATE DATABASE campus_sheba;
USE campus_sheba;

-- =====================================================
-- 1. USERS & AUTHENTICATION TABLES
-- =====================================================

-- Users table (supports all roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_pic VARCHAR(255),
    
    -- Role Management
    role ENUM('student', 'staff', 'department_admin', 'super_admin') DEFAULT 'student',
    
    -- Student specific
    student_id VARCHAR(20) UNIQUE,
    semester INT,
    batch VARCHAR(20),
    
    -- Staff/Admin specific
    designation VARCHAR(100),
    department_id INT,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_student_id (student_id)
);

-- Departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) UNIQUE,
    description TEXT,
    head_of_department INT,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (head_of_department) REFERENCES users(id) ON DELETE SET NULL
);

-- Add department foreign key to users
ALTER TABLE users 
ADD CONSTRAINT fk_user_department 
FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;

-- Roles & Permissions (for advanced access control)
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

CREATE TABLE role_permissions (
    role VARCHAR(50) NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role, permission_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- =====================================================
-- 2. SERVICE MANAGEMENT TABLES
-- =====================================================

-- Service Categories (Feature #2)
CREATE TABLE service_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    department_id INT,
    icon VARCHAR(50),
    estimated_hours INT DEFAULT 24,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_department (department_id)
);

-- Service Requests (Core Feature #2, #3, #4)
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(20) UNIQUE NOT NULL, -- Auto-generated like "REQ-2024-0001"
    
    -- Requestor Information
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    
    -- Request Details
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    
    -- Location/Context
    location VARCHAR(255),
    building VARCHAR(100),
    room_number VARCHAR(50),
    
    -- Status Management (Feature #3)
    status ENUM(
        'submitted', 
        'pending_approval',
        'assigned', 
        'in_progress', 
        'pending_info',
        'resolved', 
        'closed',
        'rejected'
    ) DEFAULT 'submitted',
    
    -- Assignment (Feature #4)
    assigned_to INT,
    assigned_by INT,
    assigned_at TIMESTAMP NULL,
    
    -- Timeline
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    
    -- SLA/Deadline
    deadline TIMESTAMP NULL,
    
    -- Attachments
    has_attachments BOOLEAN DEFAULT FALSE,
    
    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES service_categories(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_user (user_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_request_id (request_id)
);

-- Request Attachments (Feature #2)
CREATE TABLE request_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(100),
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Request History/Logs (Feature #3, #5)
CREATE TABLE request_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status VARCHAR(50),
    comment TEXT,
    changed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_request (request_id)
);

-- Request Comments (for communication)
CREATE TABLE request_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_staff_only BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =====================================================
-- 3. NOTIFICATION SYSTEM (Feature #6)
-- =====================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    title VARCHAR(255),
    message TEXT NOT NULL,
    
    -- Request related
    request_id INT,
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    is_emailed BOOLEAN DEFAULT FALSE,
    
    -- Links
    link VARCHAR(500),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);

-- Notification templates
CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) UNIQUE NOT NULL,
    subject VARCHAR(255),
    body TEXT NOT NULL,
    variables TEXT -- JSON array of available variables
);

-- =====================================================
-- 4. FEEDBACK & RATING SYSTEM (Feature #7)
-- =====================================================

CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNIQUE NOT NULL, -- One feedback per resolved request
    user_id INT NOT NULL,
    
    -- Rating (1-5)
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    
    -- Detailed feedback
    comment TEXT,
    response_time_rating INT CHECK (response_time_rating >= 1 AND response_time_rating <= 5),
    quality_rating INT CHECK (quality_rating >= 1 AND quality_rating <= 5),
    behavior_rating INT CHECK (behavior_rating >= 1 AND behavior_rating <= 5),
    
    -- Would recommend?
    would_recommend BOOLEAN DEFAULT TRUE,
    
    -- Staff response
    staff_response TEXT,
    staff_responded_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    
    INDEX idx_request (request_id),
    INDEX idx_rating (rating)
);

-- =====================================================
-- 5. PERFORMANCE MONITORING (Feature #8)
-- =====================================================

-- Staff performance tracking
CREATE TABLE staff_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    
    -- Metrics
    requests_assigned INT DEFAULT 0,
    requests_completed INT DEFAULT 0,
    requests_in_progress INT DEFAULT 0,
    
    -- Time metrics
    avg_response_time DECIMAL(10,2), -- in hours
    avg_resolution_time DECIMAL(10,2), -- in hours
    
    -- Quality metrics
    avg_rating DECIMAL(3,2),
    
    -- Period
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_period (staff_id, period_start, period_end)
);

-- =====================================================
-- 6. ANALYTICS & REPORTING (Feature #9)
-- =====================================================

-- Report definitions
CREATE TABLE report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    query TEXT NOT NULL,
    parameters TEXT, -- JSON of parameters
    created_by INT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Generated reports
CREATE TABLE generated_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT,
    name VARCHAR(200) NOT NULL,
    file_path VARCHAR(500),
    format ENUM('pdf', 'excel', 'csv') DEFAULT 'pdf',
    parameters TEXT,
    generated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id),
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Daily statistics (for dashboards)
CREATE TABLE daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE UNIQUE NOT NULL,
    
    -- Request counts
    total_requests INT DEFAULT 0,
    pending_requests INT DEFAULT 0,
    in_progress_requests INT DEFAULT 0,
    resolved_requests INT DEFAULT 0,
    closed_requests INT DEFAULT 0,
    
    -- Time metrics
    avg_resolution_time DECIMAL(10,2),
    
    -- User metrics
    new_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 7. ADMIN MANAGEMENT (Feature #10)
-- =====================================================

-- System logs (Feature #11)
CREATE TABLE activity_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- System settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- =====================================================
-- 8. INSERT SAMPLE/DEFAULT DATA
-- =====================================================

-- Insert departments
INSERT INTO departments (name, code, description, contact_email) VALUES
('Information Technology', 'IT', 'Handles all IT-related issues', 'it@university.edu'),
('Hostel Management', 'HOSTEL', 'Manages hostel facilities', 'hostel@university.edu'),
('Transport Department', 'TRANS', 'Manages university transport', 'transport@university.edu'),
('Administration', 'ADMIN', 'General administration', 'admin@university.edu'),
('Library Services', 'LIB', 'Library and resource management', 'library@university.edu'),
('Security', 'SEC', 'Campus security', 'security@university.edu');

-- Insert default permissions
INSERT INTO permissions (name, description) VALUES
('view_own_requests', 'View own service requests'),
('view_all_requests', 'View all service requests'),
('create_request', 'Create new service request'),
('assign_request', 'Assign requests to staff'),
('update_request_status', 'Update request status'),
('resolve_request', 'Mark request as resolved'),
('close_request', 'Close resolved requests'),
('manage_users', 'Create, edit, delete users'),
('manage_departments', 'Manage departments'),
('view_reports', 'View analytics and reports'),
('generate_reports', 'Generate reports'),
('manage_settings', 'Manage system settings');

-- Assign permissions to roles
INSERT INTO role_permissions (role, permission_id) VALUES
-- Student permissions
('student', 1), ('student', 3),

-- Staff permissions
('staff', 1), ('staff', 3), ('staff', 4), ('staff', 5), ('staff', 6),

-- Department Admin permissions
('department_admin', 1), ('department_admin', 2), ('department_admin', 3),
('department_admin', 4), ('department_admin', 5), ('department_admin', 6),
('department_admin', 7), ('department_admin', 10),

-- Super Admin permissions (all permissions)
('super_admin', 1), ('super_admin', 2), ('super_admin', 3), ('super_admin', 4),
('super_admin', 5), ('super_admin', 6), ('super_admin', 7), ('super_admin', 8),
('super_admin', 9), ('super_admin', 10), ('super_admin', 11), ('super_admin', 12);

-- Insert service categories
INSERT INTO service_categories (name, description, department_id, estimated_hours) VALUES
('Computer/Lab Issues', 'Hardware, software, printer problems', 1, 24),
('Network/WiFi', 'Internet connectivity issues', 1, 12),
('Email/Account', 'Email login, password reset', 1, 6),
('Room Maintenance', 'AC, plumbing, electricity', 2, 48),
('Hostel Complaint', 'General hostel issues', 2, 24),
('Bus Schedule', 'Transport timing inquiries', 3, 12),
('Vehicle Request', 'Request for university vehicle', 3, 24),
('Document Request', 'Transcripts, certificates', 4, 72),
('Fee Related', 'Payment issues', 4, 24),
('Book Issue', 'Library book problems', 5, 24),
('Security Issue', 'Safety concerns', 6, 12);

-- Insert notification templates
INSERT INTO notification_templates (type, subject, body, variables) VALUES
('request_submitted', 'Request #{request_id} Submitted Successfully',
 'Your request "{title}" has been submitted. Track it here: {tracking_link}',
 '["request_id", "title", "tracking_link"]'),

('request_assigned', 'Request #{request_id} Assigned to Staff',
 'Your request has been assigned to {staff_name}. They will contact you soon.',
 '["request_id", "staff_name"]'),

('status_updated', 'Request #{request_id} Status Updated',
 'Your request status has been updated to "{status}". Comment: {comment}',
 '["request_id", "status", "comment"]'),

('request_resolved', 'Request #{request_id} Resolved',
 'Your request has been resolved. Please provide your feedback: {feedback_link}',
 '["request_id", "feedback_link"]'),

('feedback_acknowledged', 'Thank You for Your Feedback',
 'Thank you for rating our service. Your feedback helps us improve!',
 '[]');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Campus Sheba', 'text', 'Website name'),
('site_email', 'support@campussheba.edu', 'text', 'System email address'),
('auto_request_id_prefix', 'REQ', 'text', 'Prefix for auto-generated request IDs'),
('max_file_size', '10', 'number', 'Maximum file upload size in MB'),
('allowed_file_types', '["jpg","jpeg","png","pdf","doc","docx"]', 'json', 'Allowed file extensions'),
('sla_hours_low', '72', 'number', 'SLA hours for low priority'),
('sla_hours_medium', '48', 'number', 'SLA hours for medium priority'),
('sla_hours_high', '24', 'number', 'SLA hours for high priority'),
('sla_hours_urgent', '12', 'number', 'SLA hours for urgent priority'),
('maintenance_mode', 'false', 'boolean', 'Put site in maintenance mode'),
('enable_notifications', 'true', 'boolean', 'Enable email notifications');

-- Create sample admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@campus.edu', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'super_admin');

-- Note: Generate actual password hash using PHP's password_hash('admin123', PASSWORD_DEFAULT)

-- Create sample staff users
INSERT INTO users (username, email, password, full_name, role, department_id) VALUES
('it.staff', 'it@campus.edu', '$2y$10$YourHashedPasswordHere', 'John Smith', 'staff', 1),
('hostel.staff', 'hostel@campus.edu', '$2y$10$YourHashedPasswordHere', 'Sarah Johnson', 'staff', 2),
('trans.staff', 'transport@campus.edu', '$2y$10$YourHashedPasswordHere', 'Mike Wilson', 'staff', 3);

-- Create sample student
INSERT INTO users (username, email, password, full_name, role, student_id, semester, department_id) VALUES
('student.rahul', 'rahul@student.edu', '$2y$10$YourHashedPasswordHere', 'Rahul Sharma', 'student', '2024CS001', 4, 1);

-- Update department heads
UPDATE departments SET head_of_department = 2 WHERE id = 1;
UPDATE departments SET head_of_department = 3 WHERE id = 2;
UPDATE departments SET head_of_department = 4 WHERE id = 3;

-- =====================================================
-- 9. STORED PROCEDURES & TRIGGERS
-- =====================================================

-- Auto-generate request ID trigger
DELIMITER $$
CREATE TRIGGER generate_request_id BEFORE INSERT ON service_requests
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    DECLARE year_prefix VARCHAR(4);
    
    SET year_prefix = DATE_FORMAT(NOW(), '%Y');
    
    SELECT AUTO_INCREMENT INTO next_id 
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = 'campus_sheba' 
    AND TABLE_NAME = 'service_requests';
    
    SET NEW.request_id = CONCAT('REQ-', year_prefix, '-', LPAD(next_id, 4, '0'));
END$$
DELIMITER ;

-- Log request history trigger
DELIMITER $$
CREATE TRIGGER log_request_status_change 
AFTER UPDATE ON service_requests
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.assigned_to != NEW.assigned_to THEN
        INSERT INTO request_history (request_id, status, comment, changed_by)
        VALUES (
            NEW.id, 
            NEW.status, 
            CONCAT('Status changed from ', OLD.status, ' to ', NEW.status),
            NEW.assigned_by
        );
    END IF;
END$$
DELIMITER ;

-- Create notification on request assignment
DELIMITER $$
CREATE TRIGGER notify_request_assignment
AFTER UPDATE ON service_requests
FOR EACH ROW
BEGIN
    IF OLD.assigned_to IS NULL AND NEW.assigned_to IS NOT NULL THEN
        -- Notify the user
        INSERT INTO notifications (user_id, type, title, message, request_id)
        SELECT 
            NEW.user_id,
            'info',
            'Request Assigned',
            CONCAT('Your request #', NEW.request_id, ' has been assigned to staff.'),
            NEW.id;
        
        -- Notify the staff
        INSERT INTO notifications (user_id, type, title, message, request_id)
        VALUES (
            NEW.assigned_to,
            'info',
            'New Request Assigned',
            CONCAT('You have been assigned to request #', NEW.request_id),
            NEW.id
        );
    END IF;
END$$
DELIMITER ;

-- Update daily stats
DELIMITER $$
CREATE EVENT update_daily_stats
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY
DO
BEGIN
    INSERT INTO daily_stats (stat_date, total_requests, pending_requests, 
                           in_progress_requests, resolved_requests, closed_requests)
    SELECT 
        CURDATE() - INTERVAL 1 DAY,
        COUNT(*),
        SUM(CASE WHEN status = 'submitted' OR status = 'pending_approval' THEN 1 ELSE 0 END),
        SUM(CASE WHEN status = 'assigned' OR status = 'in_progress' THEN 1 ELSE 0 END),
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END),
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END)
    FROM service_requests
    WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY
    ON DUPLICATE KEY UPDATE
        total_requests = VALUES(total_requests),
        pending_requests = VALUES(pending_requests),
        in_progress_requests = VALUES(in_progress_requests),
        resolved_requests = VALUES(resolved_requests),
        closed_requests = VALUES(closed_requests);
END$$
DELIMITER ;

-- =====================================================
-- 10. USEFUL VIEWS FOR REPORTING
-- =====================================================

-- View for request statistics by category
CREATE VIEW view_category_stats AS
SELECT 
    c.id AS category_id,
    c.name AS category_name,
    d.name AS department,
    COUNT(r.id) AS total_requests,
    SUM(CASE WHEN r.status = 'resolved' OR r.status = 'closed' THEN 1 ELSE 0 END) AS resolved_count,
    SUM(CASE WHEN r.status = 'submitted' OR r.status = 'pending_approval' THEN 1 ELSE 0 END) AS pending_count,
    AVG(TIMESTAMPDIFF(HOUR, r.submitted_at, r.resolved_at)) AS avg_resolution_hours
FROM service_categories c
LEFT JOIN departments d ON c.department_id = d.id
LEFT JOIN service_requests r ON c.id = r.category_id
GROUP BY c.id, c.name, d.name;

-- View for staff performance
CREATE VIEW view_staff_performance AS
SELECT 
    u.id AS staff_id,
    u.full_name AS staff_name,
    d.name AS department,
    COUNT(DISTINCT r.id) AS total_assigned,
    COUNT(DISTINCT CASE WHEN r.status = 'resolved' OR r.status = 'closed' THEN r.id END) AS completed,
    COUNT(DISTINCT CASE WHEN r.status = 'in_progress' THEN r.id END) AS in_progress,
    AVG(f.rating) AS avg_rating,
    AVG(TIMESTAMPDIFF(HOUR, r.assigned_at, r.resolved_at)) AS avg_resolution_time
FROM users u
LEFT JOIN departments d ON u.department_id = d.id
LEFT JOIN service_requests r ON u.id = r.assigned_to
LEFT JOIN feedback f ON r.id = f.request_id
WHERE u.role = 'staff'
GROUP BY u.id, u.full_name, d.name;

-- View for user request summary
CREATE VIEW view_user_request_summary AS
SELECT 
    u.id AS user_id,
    u.full_name,
    u.role,
    COUNT(r.id) AS total_requests,
    SUM(CASE WHEN r.status = 'resolved' OR r.status = 'closed' THEN 1 ELSE 0 END) AS resolved_requests,
    SUM(CASE WHEN r.status = 'in_progress' OR r.status = 'assigned' THEN 1 ELSE 0 END) AS active_requests,
    MAX(r.created_at) AS last_request_date
FROM users u
LEFT JOIN service_requests r ON u.id = r.user_id
GROUP BY u.id, u.full_name, u.role;

-- =====================================================
-- VERIFY INSTALLATION
-- =====================================================

-- Show all tables
SHOW TABLES;

-- Check counts
SELECT 'Users' AS table_name, COUNT(*) AS count FROM users
UNION ALL
SELECT 'Departments', COUNT(*) FROM departments
UNION ALL
SELECT 'Service Categories', COUNT(*) FROM service_categories
UNION ALL
SELECT 'Service Requests', COUNT(*) FROM service_requests;

-- Display sample data
SELECT * FROM users LIMIT 5;
SELECT * FROM departments;
SELECT * FROM service_categories;
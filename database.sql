
-- Create Database
CREATE DATABASE IF NOT EXISTS trashbin_management;
USE trashbin_management;

CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    employee_id VARCHAR(50) UNIQUE,
    profile_picture VARCHAR(255),
    reset_token_hash VARCHAR(64) NULL DEFAULT NULL,
    reset_token_expires_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS janitors (
    janitor_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    employee_id VARCHAR(50) UNIQUE,
    profile_picture VARCHAR(255),
    reset_token_hash VARCHAR(64) NULL DEFAULT NULL,
    reset_token_expires_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE bins 
MODIFY COLUMN status 
ENUM('empty', 'half_full', 'needs_attention', 'full') 
NOT NULL DEFAULT 'empty';

CREATE TABLE IF NOT EXISTS bins (
    bin_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_code VARCHAR(50) UNIQUE NOT NULL,
    location VARCHAR(255) NOT NULL,
    type ENUM('General', 'Recyclable', 'Organic') NOT NULL DEFAULT 'General',
    capacity INT NOT NULL DEFAULT 0 COMMENT 'Capacity percentage (0-100)',
    status ENUM('empty', 'needs_attention', 'full', 'half_full') NOT NULL DEFAULT 'empty',
    assigned_to INT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    installation_date DATE,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES janitors(janitor_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_location (location),
    INDEX idx_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS collections (
    collection_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    janitor_id INT NOT NULL,
    action_type ENUM('emptied', 'cleaning', 'inspection', 'maintenance', 'repair') NOT NULL,
    status ENUM('completed', 'in_progress', 'pending', 'cancelled') NOT NULL DEFAULT 'completed',
    notes TEXT,
    collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE CASCADE,
    INDEX idx_bin_id (bin_id),
    INDEX idx_janitor_id (janitor_id),
    INDEX idx_collected_at (collected_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    janitor_id INT NOT NULL,
    task_type ENUM('empty', 'inspection', 'maintenance', 'repair') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE CASCADE,
    INDEX idx_janitor_id (janitor_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    janitor_id INT NULL,
    bin_id INT,
    notification_type ENUM('critical', 'warning', 'info', 'success') NOT NULL DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE CASCADE,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
    INDEX idx_is_read (is_read),
    INDEX idx_notification_type (notification_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE reports
ADD COLUMN description TEXT AFTER report_name;

CREATE TABLE IF NOT EXISTS reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('collections', 'performance', 'status', 'revenue', 'custom') NOT NULL,
    generated_by INT NOT NULL,
    date_from DATE,
    date_to DATE,
    report_data JSON,
    format ENUM('pdf', 'excel', 'csv') NOT NULL DEFAULT 'pdf',
    status ENUM('generating', 'completed', 'failed') NOT NULL DEFAULT 'generating',
    file_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES admins(admin_id) ON DELETE CASCADE,
    INDEX idx_generated_by (generated_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bin_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    bin_id INT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT,
    notes TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_bin_id (bin_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    janitor_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE SET NULL,
    INDEX idx_admin_id (admin_id),
    INDEX idx_janitor_id (janitor_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- create contact_messages table to store incoming messages
/* DROP TABLE contact_messages;
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NULL,
  email VARCHAR(191) NULL,
  subject VARCHAR(255) NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; */


-- ==================================================
-- INSERT SAMPLE DATA
-- ==================================================

-- Insert Admin User
-- Password: password
INSERT INTO admins (
    first_name,
    last_name,
    email,
    phone,
    password,
    status,
    employee_id
) VALUES (
    'Admin',
    'User',
    'admin@gmail.com',
    '+1 (555) 000-0000',
    '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62',
    'active',
    'ADM-001'
);

SELECT * FROM bins;

	


-- Default password for all users: password

-- Insert Sample Janitors
INSERT INTO janitors (
    first_name,
    last_name,  
    email,
    phone,
    password,
    status,
    employee_id
) VALUES
('John', 'Doe', 'john@gmail.com', '+1 (555) 123-4567', '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62', 'active', 'JAN-001'),
('Jane', 'Smith', 'jane@gmail.com', '+1 (555) 234-5678', '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62', 'active', 'JAN-002'),
('Bob', 'Johnson', 'bob@gmail.com', '+1 (555) 345-6789', '$2y$10$zrso/wR/n/AIPhvxa1oReOLFVS0aLAUAD/6wNbUbYJwdpBgjvzb62', 'active', 'JAN-003');

-- Insert Sample Bins 
SELECT * FROM bins;

SET SQL_SAFE_UPDATES = 0;
DELETE FROM bins;

INSERT INTO bins (bin_code, location, type, capacity, status, assigned_to) VALUES
('BIN-001', 'CICS Building - 5th Floor', 'General', 95, 'full', 2),
('BIN-002', 'HEB Building - 1st and 2nd Floor', 'General', 10, 'empty', 3),
('BIN-003', 'HEB Building - 3rd to 5th Floor', 'Organic', 80, 'needs_attention', 4),
('BIN-004', 'GZB Building -  1st to 3rd Floor', 'General', 45, 'needs_attention', 2),
('BIN-005', 'OB Building - 1st and 2nd Floor', 'Recyclable', 60, 'needs_attention', 3),
('BIN-006', 'OB Building - 3rd and 4th Floor', 'General', 5, 'empty', 4),
('BIN-007', 'OB Building - Library Area', 'Organic', 70, 'needs_attention', 2);

-- Insert Sample Collections SELECT * FROM collections;
SELECT * FROM notifications;

SET SQL_SAFE_UPDATES = 0;
DELETE FROM bins;SELECT * FROM bins;

INSERT INTO collections (bin_id, janitor_id, action_type, status, notes) VALUES
(2, 3, 'emptied', 'completed', 'Regular emptying completed successfully'),
(6, 4, 'emptied', 'completed', 'Empty bin service'),
(8, 3, 'cleaning', 'completed', 'Cleaned and sanitized the bin');

-- Insert Sample Tasks 
SELECT * FROM tasks;

INSERT INTO tasks (bin_id, janitor_id, task_type, priority, status, notes) VALUES
(1, 2, 'empty', 'critical', 'pending', 'Full bin needs immediate attention'),
(3, 4, 'inspection', 'medium', 'pending', 'Routine inspection required'),
(7, 2, 'maintenance', 'high', 'pending', 'Bin mechanism needs checking'),
(9, 4, 'empty', 'critical', 'pending', 'Overflow detected');

-- Insert Sample Notifications SELECT * FROM notifications;

INSERT INTO notifications (user_id, bin_id, notification_type, title, message, is_read) VALUES
(1, 1, 'critical', 'Bin Full Alert', 'Bin BIN-001 is FULL - Immediate action required', FALSE),
(1, 3, 'warning', 'Bin Capacity Warning', 'Bin BIN-003 capacity at 80% - Schedule emptying soon', FALSE),
(1, 2, 'info', 'Bin Emptied', 'Bin BIN-002 emptied successfully', TRUE),
(2, 1, 'critical', 'Assigned Bin Full', 'Your assigned bin BIN-001 is full and needs attention', FALSE),
(3, 4, 'warning', 'Inspection Due', 'Bin BIN-004 scheduled for inspection', FALSE);

-- Insert Sample Activity Logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description) VALUES
(1, 'login', 'user', 1, 'User logged in'),
(2, 'update', 'bin', 2, 'Updated bin status'),
(3, 'create', 'collection', 2, 'Recorded bin collection'),
(4, 'create', 'task', 3, 'Assigned new task to janitor');

-- ==================================================
-- CREATE VIEWS
-- ==================================================

-- View: Active Janitors with their bin counts
CREATE OR REPLACE VIEW v_active_janitors AS
SELECT 
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    u.phone,
    u.status,
    COUNT(b.bin_id) as assigned_bins_count
FROM users u
LEFT JOIN bins b ON u.user_id = b.assigned_to
WHERE u.role = 'janitor' AND u.status = 'active'
GROUP BY u.user_id;

-- View: Bin Statistics
CREATE OR REPLACE VIEW v_bin_statistics AS
SELECT 
    status,
    type,
    COUNT(*) as bin_count,
    AVG(capacity) as avg_capacity
FROM bins
GROUP BY status, type;

-- View: Recent Collections
CREATE OR REPLACE VIEW v_recent_collections AS
SELECT 
    c.collection_id,
    b.bin_code,
    b.location,
    CONCAT(u.first_name, ' ', u.last_name) as janitor_name,
    c.action_type,
    c.status,
    c.collected_at
FROM collections c
JOIN bins b ON c.bin_id = b.bin_id
JOIN users u ON c.janitor_id = u.user_id
ORDER BY c.collected_at DESC
LIMIT 50;

-- ==================================================
-- END OF DATABASE SCHEMA
-- Note: Stored Procedures and Triggers removed for phpMyAdmin compatibility
-- These can be added later manually if needed
-- ==================================================
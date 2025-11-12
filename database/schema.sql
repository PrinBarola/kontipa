-- database/schema.sql
-- Run this SQL to create the tables the dashboard expects.

CREATE TABLE IF NOT EXISTS janitors (
  janitor_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(50) DEFAULT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bins (
  bin_id INT AUTO_INCREMENT PRIMARY KEY,
  bin_code VARCHAR(50) NOT NULL UNIQUE,
  location VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL,
  status ENUM('full','empty','needs_attention','in_progress','out_of_service') NOT NULL DEFAULT 'empty',
  capacity INT NOT NULL DEFAULT 0,
  assigned_to INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (assigned_to) REFERENCES janitors(janitor_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collections (
  collection_id INT AUTO_INCREMENT PRIMARY KEY,
  bin_id INT NOT NULL,
  janitor_id INT DEFAULT NULL,
  collected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bin_id) REFERENCES bins(bin_id) ON DELETE CASCADE,
  FOREIGN KEY (janitor_id) REFERENCES janitors(janitor_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- optional sample data
INSERT INTO janitors (first_name, last_name, email, phone, status) VALUES
('John','Doe','john@example.com','+1-555-1234','active'),
('Jane','Smith','jane@example.com','+1-555-5678','active');

INSERT INTO bins (bin_code, location, type, status, capacity, assigned_to) VALUES
('BIN-001','Main Street - Corner Store','General','full',95,1),
('BIN-002','Park Avenue - Central Park','Recyclable','empty',10,2),
('BIN-003','Downtown - Market Square','Organic','needs_attention',80,NULL);

INSERT INTO collections (bin_id, janitor_id, collected_at, notes) VALUES
(1,1,DATE_SUB(NOW(), INTERVAL 2 HOUR),'Picked up - nearly full'),
(2,2,DATE_SUB(NOW(), INTERVAL 30 MINUTE),'Recyclable cleared');
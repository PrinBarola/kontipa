-- Run this script once (or re-run safely). It creates missing tables/columns and seeds optional test data.

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE;
SET SQL_MODE='TRADITIONAL';

-- ============================
-- janitors table
-- ============================
CREATE TABLE IF NOT EXISTS janitors (
  janitor_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  email VARCHAR(150) UNIQUE,
  phone VARCHAR(50),
  employee_id VARCHAR(50),
  status VARCHAR(20) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================
-- admins table (minimal)
-- ============================
CREATE TABLE IF NOT EXISTS admins (
  admin_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255),
  status VARCHAR(20) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================
-- bins table
-- ============================
CREATE TABLE IF NOT EXISTS bins (
  bin_id INT AUTO_INCREMENT PRIMARY KEY,
  bin_code VARCHAR(100) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  type VARCHAR(50) DEFAULT NULL,
  janitor_id INT NULL,
  capacity INT DEFAULT 0,
  status VARCHAR(50) DEFAULT 'empty',
  assigned_to INT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  installation_date DATE NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bins_janitor_id (janitor_id),
  INDEX idx_bins_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================
-- reports table
-- ============================
CREATE TABLE IF NOT EXISTS reports (
  report_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(100) NOT NULL,
  description TEXT NULL,
  from_date DATE NULL,
  to_date DATE NULL,
  created_by INT NULL,
  status VARCHAR(50) DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure description column exists (safe on MySQL 8 / MariaDB recent)
ALTER TABLE reports ADD COLUMN IF NOT EXISTS description TEXT NULL;

-- ============================
-- collections table
-- ============================
CREATE TABLE IF NOT EXISTS collections (
  collection_id INT AUTO_INCREMENT PRIMARY KEY,
  bin_id INT NULL,
  janitor_id INT NULL,
  collected_at DATETIME NULL,
  status VARCHAR(50) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_collections_bin (bin_id),
  INDEX idx_collections_janitor (janitor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================
-- notifications table
-- ============================
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NULL,
  janitor_id INT NULL,
  bin_id INT NULL,
  notification_type VARCHAR(50) NULL,
  title VARCHAR(255) NULL,
  message TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notifications_janitor (janitor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================
-- Optional: migrate existing assigned_to values into janitor_id (if assigned_to stores numeric janitor ids)
-- This updates janitor_id only when it's NULL and assigned_to looks numeric.
-- Safe fallback: it won't overwrite existing janitor_id.
-- ============================
UPDATE bins
SET janitor_id = CAST(assigned_to AS UNSIGNED)
WHERE janitor_id IS NULL
  AND assigned_to IS NOT NULL
  AND assigned_to REGEXP '^[0-9]+$';

-- ============================
-- Optional: add an index on reports.created_at (help fetch most recent)
-- ============================
ALTER TABLE reports ADD INDEX IF NOT EXISTS idx_reports_created_at (created_at);

-- ============================
-- Optional: seed small test data (uncomment if you want sample rows)
-- ============================
/*
INSERT INTO janitors (first_name, last_name, email, status) VALUES
  ('Juan','Dela Cruz','janitor1@example.com','active'),
  ('Maria','Santos','janitor2@example.com','active');

INSERT INTO admins (first_name, last_name, email, password, status) VALUES
  ('Super','Admin','admin@example.com', 'REPLACE_WITH_HASHED_PASSWORD', 'active');

INSERT INTO bins (bin_code, location, type, janitor_id, assigned_to, capacity, status, created_at)
VALUES
  ('Bin 001','Main Gate','General', 1, 1, 10, 'empty', NOW()),
  ('Bin 002','Building A','Recyclable', 2, 2, 50, 'half_full', NOW());

INSERT INTO reports (name, type, description, from_date, to_date, created_by, status, created_at)
VALUES
  ('Monthly Collections','collections','Collections summary for the month', '2025-11-01', '2025-11-30', 1, 'completed', NOW());
*/

-- ============================
-- Cleanup / restore settings
-- ============================
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

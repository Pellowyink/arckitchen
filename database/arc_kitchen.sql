-- =====================================================
-- ARC KITCHEN DATABASE - COMPLETE SETUP
-- =====================================================
-- This is the ONLY SQL file you need to import.
-- It includes all tables, data, and admin authentication setup.
-- Created: April 29, 2026
-- =====================================================

CREATE DATABASE IF NOT EXISTS arc_kitchen;
USE arc_kitchen;

-- =====================================================
-- 1. USERS TABLE (Admin Authentication)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) DEFAULT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. INQUIRIES TABLE (Booking Inquiries)
-- =====================================================
CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    guest_count INT NOT NULL,
    package_interest VARCHAR(150) DEFAULT NULL,
    message TEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. MENU ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT 'assets/images/food-placeholder.svg',
    category VARCHAR(100) DEFAULT 'Sample Category',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 4. PACKAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    serves VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 5. CONTACT MESSAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 6. SAMPLE DATA - ADMIN USER
-- =====================================================
-- Default Admin Account:
--   Username: admin
--   Password: admin123
--   Hash: bcrypt (PASSWORD_DEFAULT)
-- ⚠️ CHANGE THIS PASSWORD IMMEDIATELY IN PRODUCTION!

INSERT INTO users (username, password, full_name, role)
SELECT 
    'admin', 
    '$2y$10$TVNx1k2o18YAJVg8HL.toOayLllOI82ZPzFZN4Z5MOC0X.R9bxevm',
    'ARC Kitchen Administrator',
    'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);

-- =====================================================
-- 7. SAMPLE DATA - MENU ITEMS
-- =====================================================

INSERT INTO menu_items (name, description, price, image, category)
SELECT 'Sample Dish 1', 'Menu description placeholder. Replace this text with your final dish details later.', 0.00, 'assets/images/food-placeholder.svg', 'Sample Category'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Sample Dish 1');

INSERT INTO menu_items (name, description, price, image, category)
SELECT 'Sample Dish 2', 'Menu description placeholder. Replace this text with your final dish details later.', 0.00, 'assets/images/food-placeholder.svg', 'Sample Category'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Sample Dish 2');

INSERT INTO menu_items (name, description, price, image, category)
SELECT 'Sample Dish 3', 'Menu description placeholder. Replace this text with your final dish details later.', 0.00, 'assets/images/food-placeholder.svg', 'Sample Category'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Sample Dish 3');

-- =====================================================
-- 8. SAMPLE DATA - PACKAGES
-- =====================================================

INSERT INTO packages (name, description, price, serves)
SELECT 'Sample Package 1', 'Package description placeholder. Replace this with your final package inclusions and notes.', 0.00, 'XX - XX pax'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name = 'Sample Package 1');

INSERT INTO packages (name, description, price, serves)
SELECT 'Sample Package 2', 'Package description placeholder. Replace this with your final package inclusions and notes.', 0.00, 'XX - XX pax'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name = 'Sample Package 2');

INSERT INTO packages (name, description, price, serves)
SELECT 'Sample Package 3', 'Package description placeholder. Replace this with your final package inclusions and notes.', 0.00, 'XX - XX pax'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name = 'Sample Package 3');

-- =====================================================
-- 9. PERFORMANCE INDEXES
-- =====================================================
-- Add indexes for commonly queried columns

ALTER TABLE users ADD INDEX IF NOT EXISTS idx_username (username);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_role (role);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_created_at (created_at);

ALTER TABLE inquiries ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE inquiries ADD INDEX IF NOT EXISTS idx_event_date (event_date);
ALTER TABLE inquiries ADD INDEX IF NOT EXISTS idx_created_at (created_at);

ALTER TABLE menu_items ADD INDEX IF NOT EXISTS idx_is_active (is_active);
ALTER TABLE menu_items ADD INDEX IF NOT EXISTS idx_category (category);

ALTER TABLE packages ADD INDEX IF NOT EXISTS idx_is_active (is_active);

-- =====================================================
-- 10. OPTIONAL TABLES (Commented Out)
-- =====================================================
-- Uncomment these if you want to enable audit logging,
-- session tracking, or password reset functionality.

-- AUDIT LOGGING TABLE (for tracking admin actions)
/*
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_value LONGTEXT,
    new_value LONGTEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
*/

-- SESSION TRACKING TABLE (for concurrent session limits)
/*
CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
*/

-- PASSWORD RESET TOKENS TABLE (for password recovery)
/*
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);
*/

-- =====================================================
-- SETUP COMPLETE ✅
-- =====================================================
-- Database: arc_kitchen
-- Tables Created: 5 (users, inquiries, menu_items, packages, contact_messages)
-- Admin Account: admin / admin123 (change password immediately!)
-- Indexes: Added for performance optimization
--
-- Next Steps:
-- 1. Test login: http://localhost/arckitchen/admin/login.php
-- 2. Change default admin password
-- 3. Create additional admin accounts via /admin/setup_admin.php
-- 4. Delete setup_admin.php when done
--
-- You can now delete arc_kitchen_admin_setup.sql (no longer needed)


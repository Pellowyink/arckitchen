-- =====================================================
-- ARC KITCHEN - COMPLETE DATABASE SETUP (MERGED)
-- =====================================================
-- This is the ONLY SQL file you need to import.
-- It includes ALL tables, ALL menu items, ALL packages, and admin setup.
-- Run this in phpMyAdmin or MySQL client.
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS arc_kitchen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. INQUIRIES TABLE
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
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    down_payment DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Payment received during inquiry approval',
    full_payment DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Final payment after booking confirmation',
    payment_status ENUM('pending', 'partial', 'fully_paid') DEFAULT 'pending',
    total_amount DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Calculated total from inquiry items',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_event_date (event_date),
    INDEX idx_created_at (created_at),
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. MENU ITEMS TABLE (Complete Menu)
-- =====================================================
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT 'assets/images/food-placeholder.svg',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_menu_category (category),
    INDEX idx_menu_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert BEEF items (IDs 1-6)
INSERT INTO menu_items (id, name, category, price, description) VALUES
(1, 'Roast Beef w/ Mashed Potato', 'BEEF', 1399.00, 'Tender roast beef served with creamy mashed potato. Good for 10 pax per tray.'),
(2, 'Beef Broccoli', 'BEEF', 1399.00, 'Classic beef and broccoli stir-fry. Good for 10 pax per tray.'),
(3, 'Creamy Beef Mushroom', 'BEEF', 1399.00, 'Beef in rich creamy mushroom sauce. Good for 10 pax per tray.'),
(4, 'Beef Caldereta', 'BEEF', 1399.00, 'Filipino-style beef stew with vegetables. Good for 10 pax per tray.'),
(5, 'Beef Salpicao', 'BEEF', 1399.00, 'Garlic-butter beef cubes. Good for 10 pax per tray.'),
(6, 'Beef Mongolian', 'BEEF', 1399.00, 'Stir-fried beef with Mongolian sauce. Good for 10 pax per tray.');

-- Insert PORK items (IDs 7-13)
INSERT INTO menu_items (id, name, category, price, description) VALUES
(7, 'Hickory Baby Back Ribs', 'PORK', 1399.00, 'Smoky, tender baby back ribs with hickory flavor. Good for 10 pax per tray.'),
(8, 'Pork ala Lengua', 'PORK', 1399.00, 'Creamy pork in mushroom sauce. Good for 10 pax per tray.'),
(9, 'Pork Caldereta', 'PORK', 1399.00, 'Filipino pork stew with vegetables. Good for 10 pax per tray.'),
(10, 'Pork Mongolian', 'PORK', 1299.00, 'Stir-fried pork with Mongolian sauce. Good for 10 pax per tray.'),
(11, 'Crispy Liempo Kare-Kare', 'PORK', 1199.00, 'Crispy pork belly with peanut sauce. Good for 10 pax per tray.'),
(12, 'Pork & Tofu Dinakdakan', 'PORK', 899.00, 'Grilled pork with tofu in spicy dressing. Good for 10 pax per tray.'),
(13, 'Lumpiang Shanghai', 'PORK', 799.00, 'Crispy Filipino spring rolls. Good for 10 pax per tray.');

-- Insert CHICKEN items (IDs 14-19)
INSERT INTO menu_items (id, name, category, price, description) VALUES
(14, 'Flavored Wings (Honey Soy Garlic)', 'CHICKEN', 799.00, 'Choose: Honey Soy Garlic, Garlic Parmesan, Buffalo, or Korean Style. Good for 10 pax per tray.'),
(15, 'Tropical Chicken', 'CHICKEN', 899.00, 'Chicken with tropical fruit glaze. Good for 10 pax per tray.'),
(16, 'Chicken Stroganoff', 'CHICKEN', 899.00, 'Creamy chicken with mushrooms. Good for 10 pax per tray.'),
(17, 'Chicken Roll', 'CHICKEN', 899.00, 'Stuffed and rolled chicken dish. Good for 10 pax per tray.'),
(18, 'Chicken BBQ', 'CHICKEN', 899.00, 'Grilled BBQ chicken. Good for 10 pax per tray.'),
(19, 'Chicken Mongolian', 'CHICKEN', 899.00, 'Stir-fried chicken with Mongolian sauce. Good for 10 pax per tray.');

-- Insert SEAFOOD items (IDs 20-25)
INSERT INTO menu_items (id, name, category, price, description) VALUES
(20, 'Shrimp Salpicao', 'SEAFOOD', 1399.00, 'Garlic-butter shrimp. Good for 10 pax per tray.'),
(21, 'Buttered Garlic Shrimp', 'SEAFOOD', 1399.00, 'Shrimp in rich butter and garlic. Good for 10 pax per tray.'),
(22, 'Mixed Seafood in Aligue', 'SEAFOOD', 999.00, 'Seafood medley with crab fat sauce. Good for 10 pax per tray.'),
(23, 'Mixed Seafood Salpicao', 'SEAFOOD', 999.00, 'Seafood medley in garlic-butter sauce. Good for 10 pax per tray.'),
(24, 'Fish Fillet with dip (Creamy Garlic / Sweet & Sour)', 'SEAFOOD', 799.00, 'Crispy fish fillet with choice of dip. Good for 10 pax per tray.'),
(25, 'SPECIAL PROMO: 2 trays Mixed Seafood (Aligue/Salpicao)', 'SEAFOOD', 1799.00, 'Two trays of Mixed Seafood - choose Aligue or Salpicao. Good for 10 pax per tray.');

-- Insert PASTA items (IDs 26-31)
INSERT INTO menu_items (id, name, category, price, description) VALUES
(26, 'Pasta Aligue', 'PASTA', 999.00, 'Pasta with crab fat sauce. Good for 10 pax per tray.'),
(27, 'ARC\'s Jackie Chan (Spicy)', 'PASTA', 849.00, 'Spicy specialty pasta. Good for 10 pax per tray.'),
(28, 'Tomato Basil Pasta', 'PASTA', 799.00, 'Classic tomato and basil pasta. Good for 10 pax per tray.'),
(29, 'Spaghetti Bolognese', 'PASTA', 799.00, 'Italian meat sauce pasta. Good for 10 pax per tray.'),
(30, 'Pesto Pasta', 'PASTA', 799.00, 'Pasta with basil pesto sauce. Good for 10 pax per tray.'),
(31, 'Spaghetti Carbonara', 'PASTA', 799.00, 'Creamy bacon pasta. Good for 10 pax per tray.');

-- Insert VEGETABLE items (IDs 32-35)
INSERT INTO menu_items (id, name, category, price, description) VALUES
(32, 'Sipo Egg', 'VEGETABLE', 899.00, 'Mixed vegetables with quail eggs. Good for 10 pax per tray.'),
(33, 'Shawarma Salad', 'VEGETABLE', 849.00, 'Fresh salad with shawarma-style toppings. Good for 10 pax per tray.'),
(34, 'Caesar Salad', 'VEGETABLE', 799.00, 'Classic Caesar salad. Good for 10 pax per tray.'),
(35, 'Potato Gratin', 'VEGETABLE', 799.00, 'Baked layered potatoes with cheese. Good for 10 pax per tray.');

-- =====================================================
-- 4. PACKAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    total_price DECIMAL(10,2) NOT NULL,
    serves VARCHAR(50) DEFAULT '10 pax per tray',
    is_active TINYINT(1) DEFAULT 1,
    items_json LONGTEXT DEFAULT NULL COMMENT 'JSON array of selected menu item IDs',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_packages_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Packages
INSERT INTO packages (id, name, description, total_price) VALUES
(1, 'The Budget Fiesta', 'Perfect for small gatherings on a budget. Includes Lumpiang Shanghai, Flavored Wings, Spaghetti Carbonara, and Caesar Salad.', 3196.00),
(2, 'The Crowd Pleaser', 'A balanced selection that everyone will love. Includes Creamy Beef Mushroom, Crispy Liempo Kare-Kare, Chicken BBQ, Fish Fillet, and Spaghetti Bolognese.', 5095.00),
(3, 'The Premium Feast', 'Elevated dishes for special occasions. Includes Roast Beef, Hickory Baby Back Ribs, Buttered Garlic Shrimp, Pasta Aligue, and Sipo Egg.', 6095.00),
(4, 'The Grand Surf & Turf', 'The ultimate feast with land and sea. Includes 2 trays Mixed Seafood, Beef Caldereta, Pork & Tofu Dinakdakan, Chicken Roll, Shawarma Salad, and Tomato Basil Pasta.', 6644.00);

-- =====================================================
-- 5. PACKAGE ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS package_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    is_removable TINYINT(1) DEFAULT 1,
    is_editable TINYINT(1) DEFAULT 1,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_package_item (package_id, menu_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Package 1: The Budget Fiesta
-- Lumpiang Shanghai (13), Flavored Wings (14), Spaghetti Carbonara (31), Caesar Salad (34)
INSERT INTO package_items (package_id, menu_item_id, quantity) VALUES
(1, 13, 1),
(1, 14, 1),
(1, 31, 1),
(1, 34, 1);

-- Package 2: The Crowd Pleaser
-- Creamy Beef Mushroom (3), Crispy Liempo Kare-Kare (11), Chicken BBQ (18), Fish Fillet (24), Spaghetti Bolognese (29)
INSERT INTO package_items (package_id, menu_item_id, quantity) VALUES
(2, 3, 1),
(2, 11, 1),
(2, 18, 1),
(2, 24, 1),
(2, 29, 1);

-- Package 3: The Premium Feast
-- Roast Beef (1), Hickory Baby Back Ribs (7), Buttered Garlic Shrimp (21), Pasta Aligue (26), Sipo Egg (32)
INSERT INTO package_items (package_id, menu_item_id, quantity) VALUES
(3, 1, 1),
(3, 7, 1),
(3, 21, 1),
(3, 26, 1),
(3, 32, 1);

-- Package 4: The Grand Surf & Turf
-- SPECIAL PROMO Mixed Seafood (25), Beef Caldereta (4), Pork & Tofu Dinakdakan (12), Chicken Roll (17), Shawarma Salad (33), Tomato Basil Pasta (28)
INSERT INTO package_items (package_id, menu_item_id, quantity) VALUES
(4, 25, 1),
(4, 4, 1),
(4, 12, 1),
(4, 17, 1),
(4, 33, 1),
(4, 28, 1);

-- =====================================================
-- 6. BOOKINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT DEFAULT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    guest_count INT NOT NULL,
    items_json LONGTEXT DEFAULT NULL COMMENT 'JSON array of order items',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    down_payment DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Payment received during inquiry/approval',
    full_payment DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Final payment received',
    payment_status ENUM('pending', 'partial', 'fully_paid') DEFAULT 'pending',
    package_id INT DEFAULT NULL,
    special_requests TEXT DEFAULT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled', 'blocked') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_event_date (event_date),
    INDEX idx_customer_email (customer_email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. INQUIRY ITEMS TABLE (Order Details)
-- =====================================================
CREATE TABLE IF NOT EXISTS inquiry_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT NOT NULL,
    menu_item_id INT NULL,
    is_package TINYINT(1) NOT NULL DEFAULT 0,
    package_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES inquiries(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL,
    INDEX idx_inquiry_items_inquiry (inquiry_id),
    INDEX idx_inquiry_items_menu (menu_item_id),
    INDEX idx_inquiry_items_package (package_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. UNAVAILABLE DATES TABLE (Calendar Blocking)
-- =====================================================
CREATE TABLE IF NOT EXISTS unavailable_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    reason VARCHAR(255),
    status ENUM('blocked', 'fully_booked') DEFAULT 'blocked',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. CONTACT MESSAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. DEFAULT ADMIN USER
-- =====================================================
-- Username: admin
-- Password: admin123
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
-- ALTER TABLE FOR EXISTING DATABASES (Run if upgrading)
-- =====================================================
-- If you already have the database set up, run these ALTER statements:
-- ALTER TABLE packages ADD COLUMN IF NOT EXISTS items_json LONGTEXT DEFAULT NULL COMMENT 'JSON array of selected menu item IDs';
-- =====================================================

-- =====================================================
-- SETUP COMPLETE
-- =====================================================
-- Database: arc_kitchen
-- Tables Created: 9 total
--   - users (admin authentication)
--   - inquiries (booking requests)
--   - menu_items (28 complete menu items)
--   - packages (4 preset packages)
--   - package_items (package compositions)
--   - bookings (confirmed events)
--   - inquiry_items (order line items)
--   - unavailable_dates (calendar blocking)
--   - contact_messages (contact form)
--
-- Admin Login: admin / admin123
-- CHANGE THIS PASSWORD IMMEDIATELY IN PRODUCTION!
-- =====================================================

CREATE DATABASE IF NOT EXISTS arc_kitchen;
USE arc_kitchen;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) DEFAULT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    serves VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, full_name, role)
SELECT 'admin', '$2y$10$TVNx1k2o18YAJVg8HL.toOayLllOI82ZPzFZN4Z5MOC0X.R9bxevm', 'ARC Kitchen Administrator', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);

INSERT INTO menu_items (name, description, price, image, category)
SELECT 'Sample Dish 1', 'Menu description placeholder. Replace this text with your final dish details later.', 0.00, 'assets/images/food-placeholder.svg', 'Sample Category'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Sample Dish 1');

INSERT INTO menu_items (name, description, price, image, category)
SELECT 'Sample Dish 2', 'Menu description placeholder. Replace this text with your final dish details later.', 0.00, 'assets/images/food-placeholder.svg', 'Sample Category'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Sample Dish 2');

INSERT INTO menu_items (name, description, price, image, category)
SELECT 'Sample Dish 3', 'Menu description placeholder. Replace this text with your final dish details later.', 0.00, 'assets/images/food-placeholder.svg', 'Sample Category'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE name = 'Sample Dish 3');

INSERT INTO packages (name, description, price, serves)
SELECT 'Sample Package 1', 'Package description placeholder. Replace this with your final package inclusions and notes.', 0.00, 'XX - XX pax'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name = 'Sample Package 1');

INSERT INTO packages (name, description, price, serves)
SELECT 'Sample Package 2', 'Package description placeholder. Replace this with your final package inclusions and notes.', 0.00, 'XX - XX pax'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name = 'Sample Package 2');

INSERT INTO packages (name, description, price, serves)
SELECT 'Sample Package 3', 'Package description placeholder. Replace this with your final package inclusions and notes.', 0.00, 'XX - XX pax'
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE name = 'Sample Package 3');


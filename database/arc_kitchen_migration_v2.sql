-- =====================================================
-- ARC KITCHEN DATABASE - MIGRATION V2
-- Adds Bookings Table and Updates Inquiries Status
-- =====================================================
-- Run this file AFTER arc_kitchen.sql
-- This migration adds the bookings table and ensures
-- proper status enums for the state machine logic
-- =====================================================

USE arc_kitchen;

-- =====================================================
-- 1. BOOKINGS TABLE (Confirmed/Active Bookings)
-- =====================================================
-- This table stores confirmed bookings that have been
-- approved from inquiries. It includes order items,
-- amounts, and status tracking through the lifecycle.

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT DEFAULT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_email VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    guest_count INT NOT NULL,
    items_json LONGTEXT DEFAULT NULL COMMENT 'JSON array of {id, name, price, quantity, flavors}',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
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
);

-- =====================================================
-- 2. UPDATE INQUIRIES TABLE STATUS
-- =====================================================
-- Update the status column to use ENUM values
-- This ensures consistency with the state machine logic

ALTER TABLE inquiries 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending';

-- Add indexes if they don't exist (performance)
ALTER TABLE inquiries ADD INDEX IF NOT EXISTS idx_email (email);
ALTER TABLE inquiries ADD INDEX IF NOT EXISTS idx_phone (phone);

-- =====================================================
-- 3. STATE MACHINE TRIGGER LOGIC
-- =====================================================
-- When an inquiry is approved, it may automatically
-- create a booking record (this can be handled in PHP
-- via a helper function instead of database triggers)

-- Example trigger (optional - commented out for manual control):
/*
DELIMITER $$
CREATE TRIGGER tr_inquiry_to_booking AFTER UPDATE ON inquiries
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status != 'approved' THEN
        INSERT INTO bookings 
        (inquiry_id, customer_name, customer_email, customer_phone, 
         event_date, event_type, guest_count, status)
        VALUES 
        (NEW.id, NEW.full_name, NEW.email, NEW.phone,
         NEW.event_date, NEW.event_type, NEW.guest_count, 'pending');
    END IF;
END$$
DELIMITER ;
*/

-- =====================================================
-- 4. PERFORMANCE OPTIMIZATION
-- =====================================================
-- Add any additional indexes for reporting

ALTER TABLE menu_items ADD INDEX IF NOT EXISTS idx_created_at (created_at);
ALTER TABLE packages ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- =====================================================
-- MIGRATION COMPLETE ✅
-- =====================================================
-- Tables Updated:
-- 1. inquiries - Status column updated to use ENUM
-- 2. bookings - NEW TABLE created for confirmed bookings
--
-- State Machine Logic (handled in PHP):
-- - Inquiry 'pending' → 'approved' → Booking created with 'pending' status
-- - Booking 'pending' → 'confirmed' (admin approves)
-- - Booking 'confirmed' → 'completed' (event finished)
-- - Booking can be 'cancelled' or 'blocked' at any stage
--
-- Dashboard Counters:
-- - Total Inquiries: COUNT(inquiries WHERE status = 'pending')
-- - Confirmed Bookings: COUNT(bookings WHERE status = 'confirmed')
-- - Completed Bookings: COUNT(bookings WHERE status = 'completed')
-- - Active Packages: COUNT(packages WHERE is_active = 1)
-- - Active Menu Items: COUNT(menu_items WHERE is_active = 1)


-- =====================================================
-- ARC KITCHEN: MASTER SQL FIXES FILE
-- This file contains ALL database fixes in one place
-- Run this entire file in phpMyAdmin SQL tab
-- Last Updated: May 5, 2026
-- =====================================================

-- Select the database (change 'arc_kitchen' to your actual database name if different)
USE arc_kitchen;

-- ============================================================================
-- PART 1: CALENDAR SYSTEM FIXES (unavailable_dates table)
-- ============================================================================

-- Step 1.1: Add capacity_note column (ignore error if already exists)
-- Note: MariaDB may not support IF NOT EXISTS on ALTER TABLE
-- If you get error "Duplicate column name", it means column already exists - that's OK!
ALTER TABLE unavailable_dates 
ADD COLUMN capacity_note TEXT NULL 
AFTER status;

-- Step 1.2: Add UNIQUE key to date column (CRITICAL for ON DUPLICATE KEY UPDATE)
ALTER TABLE unavailable_dates ADD UNIQUE KEY IF NOT EXISTS unique_date (date);

-- Step 1.3: Fix all dates with empty status
UPDATE unavailable_dates 
SET status = 'limited' 
WHERE (status = '' OR status IS NULL) 
AND (capacity_note IS NOT NULL AND capacity_note != '');

UPDATE unavailable_dates 
SET status = 'blocked' 
WHERE (status = '' OR status IS NULL) 
AND (capacity_note IS NULL OR capacity_note = '');

-- ============================================================================
-- PART 2: BOOKING ENHANCEMENT FIXES (inquiries table)
-- ============================================================================

-- Step 2.1: Add event_time column to inquiries table
-- Note: If column already exists, you'll get "Duplicate column name" error - that's OK!
ALTER TABLE inquiries 
ADD COLUMN event_time TIME NULL 
AFTER event_date;

-- Step 2.2: Add event_location column to inquiries table
ALTER TABLE inquiries 
ADD COLUMN event_location TEXT NULL 
AFTER event_time;

-- ============================================================================
-- PART 3: STATUS & BOOKINGS FIXES (bookings table)
-- ============================================================================

-- Step 3.1: Add event_time column to bookings table
ALTER TABLE bookings 
ADD COLUMN event_time TIME NULL 
AFTER event_date;

-- Step 3.2: Add event_location column to bookings table
ALTER TABLE bookings 
ADD COLUMN event_location TEXT NULL 
AFTER event_time;

-- Fix bookings that should be fully paid but show as partial
-- This sets full_payment to remaining balance when status is completed
UPDATE bookings 
SET 
    full_payment = (total_amount - COALESCE(down_payment, 0)),
    payment_status = 'fully_paid'
WHERE 
    status = 'completed' 
    AND (full_payment IS NULL OR full_payment = 0)
    AND (total_amount - COALESCE(down_payment, 0)) > 0;

-- ============================================================================
-- PART 4: VERIFICATION - Show current status
-- ============================================================================

SELECT '========================================' as '================================';
SELECT 'ALL FIXES APPLIED SUCCESSFULLY!' as 'STATUS';
SELECT '========================================' as '================================';

-- Show unavailable dates status
SELECT 
    'Unavailable Dates Status' as section,
    COUNT(*) as total_dates,
    SUM(CASE WHEN status = 'limited' THEN 1 ELSE 0 END) as limited_dates,
    SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_dates,
    SUM(CASE WHEN status = 'fully_booked' THEN 1 ELSE 0 END) as booked_dates
FROM unavailable_dates;

-- Show inquiries table columns
SELECT 
    'Inquiries Table Columns' as section,
    COLUMN_NAME as column_name,
    DATA_TYPE as data_type
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'inquiries' 
AND COLUMN_NAME IN ('event_date', 'event_time', 'event_location');

-- Show bookings that were fixed
SELECT 
    'Bookings Fixed to Fully Paid' as section,
    id,
    customer_name,
    total_amount,
    down_payment,
    full_payment,
    payment_status
FROM bookings 
WHERE payment_status = 'fully_paid' 
ORDER BY id DESC 
LIMIT 5;

-- ============================================================================
-- PART 5: SCHEMA FAIL-SAFE FIXES (Prevent "Field doesn't have a default value" errors)
-- ============================================================================

-- Step 5.1: Make bookings table columns nullable with safe defaults
-- This prevents database crashes when PHP doesn't send all values
ALTER TABLE `bookings` 
MODIFY COLUMN `event_date` DATE NULL DEFAULT NULL,
MODIFY COLUMN `event_time` VARCHAR(50) NULL DEFAULT NULL,
MODIFY COLUMN `venue_location` TEXT NULL DEFAULT NULL,
MODIFY COLUMN `event_location` TEXT NULL DEFAULT NULL,
MODIFY COLUMN `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `down_payment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `full_payment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
MODIFY COLUMN `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00;

-- Step 5.2: Make inquiries table columns nullable with safe defaults
ALTER TABLE `inquiries` 
MODIFY COLUMN `event_date` DATE NULL DEFAULT NULL,
MODIFY COLUMN `event_time` TIME NULL DEFAULT NULL,
MODIFY COLUMN `event_location` TEXT NULL DEFAULT NULL,
MODIFY COLUMN `guest_count` INT NULL DEFAULT 0,
MODIFY COLUMN `package_interest` TEXT NULL DEFAULT NULL,
MODIFY COLUMN `message` TEXT NULL DEFAULT NULL;

-- ============================================================================
-- FEATURE 2: Scheduling Details - Complete Address & Delivery Time
-- ============================================================================

-- Add structured address fields to inquiries table
ALTER TABLE `inquiries`
ADD COLUMN IF NOT EXISTS `street_address` TEXT NULL AFTER `event_location`,
ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL AFTER `street_address`,
ADD COLUMN IF NOT EXISTS `province` VARCHAR(100) NULL AFTER `city`,
ADD COLUMN IF NOT EXISTS `zip_code` VARCHAR(20) NULL AFTER `province`,
ADD COLUMN IF NOT EXISTS `landmarks` TEXT NULL AFTER `zip_code`,
ADD COLUMN IF NOT EXISTS `delivery_time` TIME NULL AFTER `event_time`;

-- Add same fields to bookings table for consistency
ALTER TABLE `bookings`
ADD COLUMN IF NOT EXISTS `street_address` TEXT NULL AFTER `event_location`,
ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL AFTER `street_address`,
ADD COLUMN IF NOT EXISTS `province` VARCHAR(100) NULL AFTER `city`,
ADD COLUMN IF NOT EXISTS `zip_code` VARCHAR(20) NULL AFTER `province`,
ADD COLUMN IF NOT EXISTS `landmarks` TEXT NULL AFTER `zip_code`,
ADD COLUMN IF NOT EXISTS `delivery_time` TIME NULL AFTER `event_time`;

-- ============================================================================
-- FEATURE 3: Admin Deletion - Soft Delete, Bulk Delete, Delete Reasons
-- ============================================================================

-- Add soft delete columns to inquiries table
ALTER TABLE `inquiries`
ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `deleted_by` INT NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `delete_reason` TEXT NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;

-- Add soft delete columns to bookings table
ALTER TABLE `bookings`
ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `deleted_by` INT NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `delete_reason` TEXT NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;

-- Create index for efficient soft delete queries
CREATE INDEX IF NOT EXISTS idx_inquiries_is_active ON inquiries(is_active, deleted_at);
CREATE INDEX IF NOT EXISTS idx_bookings_is_active ON bookings(is_active, deleted_at);

-- Create deleted_records_log table for audit trail
CREATE TABLE IF NOT EXISTS `deleted_records_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `record_type` VARCHAR(50) NOT NULL COMMENT 'inquiry, booking, package, menu',
    `record_id` INT NOT NULL,
    `record_data` JSON NULL COMMENT 'Snapshot of deleted record',
    `deleted_by` INT NOT NULL,
    `deleted_by_name` VARCHAR(255) NULL,
    `delete_reason` TEXT NULL,
    `delete_type` VARCHAR(20) NOT NULL DEFAULT 'soft' COMMENT 'soft, permanent, bulk',
    `deleted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `restored_at` DATETIME NULL DEFAULT NULL,
    `restored_by` INT NULL DEFAULT NULL,
    INDEX idx_record_type (record_type),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ARC KITCHEN: ALL CALENDAR FIXES IN ONE FILE
-- Run this entire file in phpMyAdmin SQL tab
-- =====================================================

-- ============================================================================
-- STEP 1: Add capacity_note column if it doesn't exist
-- ============================================================================
SET @dbname = DATABASE();
SET @tablename = 'unavailable_dates';
SET @columnname = 'capacity_note';

-- Check if column exists
SET @sql = CONCAT(
    'SELECT COUNT(*) INTO @col_exists FROM information_schema.columns',
    ' WHERE table_schema = ''', @dbname, '''',
    ' AND table_name = ''', @tablename, '''',
    ' AND column_name = ''', @columnname, ''''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add column if it doesn't exist
SET @add_col = IF(@col_exists = 0, 
    'ALTER TABLE unavailable_dates ADD COLUMN capacity_note TEXT NULL AFTER status',
    'SELECT "Step 1: capacity_note column already exists" as message'
);
PREPARE stmt FROM @add_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- STEP 2: Add UNIQUE key to date column (CRITICAL for ON DUPLICATE KEY UPDATE)
-- ============================================================================
-- This prevents duplicate dates and makes the insert/update work properly
ALTER TABLE unavailable_dates ADD UNIQUE KEY IF NOT EXISTS unique_date (date);

-- ============================================================================
-- STEP 3: Fix all dates with empty status
-- ============================================================================
-- Set to 'limited' if they have a capacity note
UPDATE unavailable_dates 
SET status = 'limited' 
WHERE (status = '' OR status IS NULL) 
AND (capacity_note IS NOT NULL AND capacity_note != '');

-- Set to 'blocked' if they have no capacity note
UPDATE unavailable_dates 
SET status = 'blocked' 
WHERE (status = '' OR status IS NULL) 
AND (capacity_note IS NULL OR capacity_note = '');

-- ============================================================================
-- STEP 4: Show current status of all unavailable dates
-- ============================================================================
SELECT 
    'FIX COMPLETE!' as status,
    'Check inquiry.php to see honey-gold limited dates' as next_step;

-- Show all dates with their display status
SELECT 
    date, 
    status, 
    capacity_note,
    CASE 
        WHEN status = 'limited' THEN 'Will show HONEY-GOLD on customer calendar'
        WHEN status = 'blocked' THEN 'Will show GRAY on customer calendar'
        WHEN status = 'fully_booked' THEN 'Will show RED on customer calendar'
        ELSE 'NEEDS FIX - Status is empty'
    END as calendar_display
FROM unavailable_dates 
WHERE date >= CURDATE()
ORDER BY date;

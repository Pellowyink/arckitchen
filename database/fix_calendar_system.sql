-- =====================================================
-- ARC KITCHEN: Calendar System Fixes & Setup
-- Run this in phpMyAdmin SQL tab to fix all issues
-- =====================================================

-- 1. Add capacity_note column if it doesn't exist
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
    'SELECT "Column already exists" as message'
);
PREPARE stmt FROM @add_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Fix any dates that were saved with empty status (set them to 'limited' if they have capacity_note)
UPDATE unavailable_dates 
SET status = 'limited' 
WHERE (status = '' OR status IS NULL) 
AND (capacity_note IS NOT NULL AND capacity_note != '');

-- 3. Fix any dates that were saved with empty status (set them to 'blocked' if no capacity_note)
UPDATE unavailable_dates 
SET status = 'blocked' 
WHERE (status = '' OR status IS NULL) 
AND (capacity_note IS NULL OR capacity_note = '');

-- 4. Show results
SELECT 'Database fixes applied successfully!' as result;
SELECT 
    date, 
    status, 
    capacity_note,
    CASE 
        WHEN status = 'limited' THEN 'Will show HONEY-GOLD on calendar'
        WHEN status = 'blocked' THEN 'Will show GRAY on calendar'
        WHEN status = 'fully_booked' THEN 'Will show RED on calendar'
        ELSE 'NEEDS FIX - Status is empty'
    END as calendar_display
FROM unavailable_dates 
WHERE date >= CURDATE()
ORDER BY date;

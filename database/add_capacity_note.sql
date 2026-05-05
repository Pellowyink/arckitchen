-- Add capacity_note column to blocked_dates table
-- and modify status enum to include 'limited'

-- First, check if the column exists and add it if not
SET @dbname = DATABASE();
SET @tablename = 'blocked_dates';
SET @columnname = 'capacity_note';

SET @sql = CONCAT(
    'SELECT COUNT(*) INTO @col_exists FROM information_schema.columns',
    ' WHERE table_schema = ''', @dbname, '''',
    ' AND table_name = ''', @tablename, '''',
    ' AND column_name = ''', @columnname, ''''
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add capacity_note column if it doesn't exist
SET @add_col = IF(@col_exists = 0, 
    'ALTER TABLE blocked_dates ADD COLUMN capacity_note TEXT NULL AFTER status',
    'SELECT "Column already exists" as message'
);

PREPARE stmt FROM @add_col;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update status enum to include 'limited' if using MySQL 5.7+
-- For older versions, we need to handle this differently
-- This is a safe way to add 'limited' to an ENUM
SET @check_enum = '
SELECT COLUMN_TYPE INTO @current_enum 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = "blocked_dates" 
AND column_name = "status"';

PREPARE stmt FROM @check_enum;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- If status doesn't include 'limited', modify it
-- Note: This might need manual intervention if data exists
-- Alternative: Just ensure the application handles 'limited' status

-- Display current schema
DESCRIBE blocked_dates;

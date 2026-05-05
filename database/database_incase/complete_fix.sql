-- COMPLETE FIX for Calendar System
-- Run this entire file in phpMyAdmin SQL tab

-- 1. First, add capacity_note column if missing
ALTER TABLE unavailable_dates ADD COLUMN IF NOT EXISTS capacity_note TEXT NULL AFTER status;

-- 2. Add UNIQUE key to date column (CRITICAL for ON DUPLICATE KEY UPDATE to work)
-- This ensures dates don't get duplicated
ALTER TABLE unavailable_dates ADD UNIQUE KEY IF NOT EXISTS unique_date (date);

-- 3. Fix all existing dates with empty status
UPDATE unavailable_dates SET status = 'limited' WHERE status = '' OR status IS NULL;

-- 4. Show the fixed data
SELECT date, status, capacity_note, reason FROM unavailable_dates ORDER BY date DESC LIMIT 10;

-- DIRECT FIX - Run this exact SQL in phpMyAdmin
-- This will immediately fix your May 6 date

-- Check current state
SELECT date, status, capacity_note FROM unavailable_dates WHERE date = '2026-05-06';

-- Fix the status to 'limited'
UPDATE unavailable_dates SET status = 'limited' WHERE date = '2026-05-06';

-- Verify the fix
SELECT date, status, capacity_note FROM unavailable_dates WHERE date = '2026-05-06';

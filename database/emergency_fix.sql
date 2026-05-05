-- EMERGENCY FIX: Update the broken dates to have 'limited' status
-- Run this in phpMyAdmin SQL tab

-- Fix May 6 - set it to limited status
UPDATE unavailable_dates SET status = 'limited', capacity_note = 'only taking 1 more customer' WHERE date = '2026-05-06';

-- Show the result
SELECT * FROM unavailable_dates WHERE date >= '2026-05-01' ORDER BY date;

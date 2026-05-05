-- Simple SQL to add capacity_note column to unavailable_dates table
-- Run this in phpMyAdmin SQL tab

ALTER TABLE unavailable_dates 
ADD COLUMN IF NOT EXISTS capacity_note TEXT NULL AFTER status;

-- =====================================================
-- FIX: Add missing columns to inquiries table
-- Run this in phpMyAdmin or MySQL to fix the error
-- =====================================================

USE arc_kitchen;

-- Add items_json column to store order items
ALTER TABLE inquiries 
ADD COLUMN IF NOT EXISTS items_json LONGTEXT DEFAULT NULL COMMENT 'JSON array of order items';

-- Add updated_at column for tracking
ALTER TABLE inquiries 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Verify columns were added
DESCRIBE inquiries;

-- =====================================================
-- ARC KITCHEN: Enhanced Booking Experience
-- Add event_time and event_location columns
-- =====================================================

-- Add event_time column to inquiries table
ALTER TABLE inquiries 
ADD COLUMN IF NOT EXISTS event_time TIME NULL AFTER event_date;

-- Add event_location column to inquiries table  
ALTER TABLE inquiries 
ADD COLUMN IF NOT EXISTS event_location TEXT NULL AFTER event_time;

-- Show updated table structure
DESCRIBE inquiries;

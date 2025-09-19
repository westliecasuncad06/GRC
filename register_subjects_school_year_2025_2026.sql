-- Register all subjects to the current school year 2025-2026
-- This updates the school_year column in the classes table for all existing classes

UPDATE classes SET school_year = '2025-2026' WHERE school_year IS NULL OR school_year = '';

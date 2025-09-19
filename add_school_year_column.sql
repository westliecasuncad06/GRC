-- Add school_year column to classes table
ALTER TABLE classes ADD COLUMN school_year VARCHAR(20) DEFAULT NULL;

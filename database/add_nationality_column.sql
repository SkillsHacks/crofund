-- Add nationality column to users table
ALTER TABLE users ADD COLUMN nationality VARCHAR(100) NULL AFTER country;

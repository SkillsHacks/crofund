-- Add original_amount column to donations table to track what the donor actually paid
ALTER TABLE donations ADD COLUMN original_amount DECIMAL(10,2) NULL AFTER amount;

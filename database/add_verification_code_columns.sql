-- Add verification code and expiration columns to users table
ALTER TABLE users 
ADD COLUMN verification_code VARCHAR(6) NULL,
ADD COLUMN verification_code_expires_at DATETIME NULL;

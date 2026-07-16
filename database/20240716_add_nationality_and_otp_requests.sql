-- Add nationality column to users table
ALTER TABLE users ADD COLUMN nationality VARCHAR(100) NULL AFTER country;

-- Create OTP requests table for rate limiting and logging
CREATE TABLE IF NOT EXISTS otp_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

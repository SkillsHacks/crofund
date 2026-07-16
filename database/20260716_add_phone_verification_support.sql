ALTER TABLE users
ADD COLUMN phone_country_code VARCHAR(10) NULL AFTER phone,
ADD COLUMN is_phone_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER is_verified,
ADD COLUMN phone_verified_at DATETIME NULL AFTER is_phone_verified,
ADD COLUMN phone_risk_score INT NOT NULL DEFAULT 0 AFTER phone_verified_at,
ADD COLUMN phone_verification_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER phone_risk_score;

CREATE TABLE IF NOT EXISTS phone_otp_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(30) NOT NULL,
    device_id VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    risk_score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_device_id (device_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS phone_otp_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(30) NOT NULL,
    device_id VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_device_id (device_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

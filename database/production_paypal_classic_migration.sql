-- Run this against the PRODUCTION database after pulling the paypal-classic code.
-- Safe to re-run (idempotent) -- uses INSERT ... ON DUPLICATE KEY UPDATE.

-- 1. Create the pending_donations table (same as database/create_pending_donations_table.sql)
CREATE TABLE IF NOT EXISTS pending_donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token CHAR(64) NOT NULL COMMENT 'Random id sent as the PayPal custom field',
    campaign_id INT NOT NULL,
    user_id INT NULL,
    donor_name VARCHAR(150) NULL,
    donor_email VARCHAR(150) NULL,
    amount_requested DECIMAL(10,2) NOT NULL,
    currency_code VARCHAR(10) NOT NULL DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    transaction_id VARCHAR(100) NULL COMMENT 'PayPal tx id once verified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    UNIQUE KEY uniq_token (token),
    KEY idx_campaign (campaign_id),
    CONSTRAINT fk_pending_donations_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Register the paypal_classic gateway and make it the only enabled one
INSERT INTO payment_gateways (code, name, enabled)
VALUES ('paypal_classic', 'PayPal', 1)
ON DUPLICATE KEY UPDATE name = 'PayPal', enabled = 1;

UPDATE payment_gateways SET enabled = 0 WHERE code IN ('paypal', 'stripe');

-- 3. PayPal classic settings (values confirmed as the real production PayPal account)
INSERT INTO settings (setting_key, setting_value) VALUES
    ('paypal_business_email', 'sdfyujhhesdd@gmail.com'),
    ('paypal_classic_mode', 'live'),
    ('paypal_pdt_identity_token', 'FzK6jVjpWcm0PwoSewc7izXQrOp40Io3_pMSYk9tv9z4Cb_DlPxEf4k0UG0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

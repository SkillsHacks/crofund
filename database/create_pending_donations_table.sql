-- Tracks a donation attempt BEFORE the donor is redirected to PayPal.
-- The `token` value is what gets sent as PayPal's `custom` field. On return,
-- we look the token up here instead of trusting whatever the browser sends back --
-- that's what stops someone from typing the return URL manually and crediting
-- a campaign without ever paying.

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

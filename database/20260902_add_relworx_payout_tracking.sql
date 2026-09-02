ALTER TABLE premium_payments
    ADD COLUMN payout_status ENUM('pending', 'processing', 'sent', 'failed') NOT NULL DEFAULT 'pending' AFTER status,
    ADD COLUMN payout_error TEXT NULL AFTER payout_status,
    ADD COLUMN payout_started_at DATETIME NULL AFTER payout_error,
    ADD COLUMN payout_completed_at DATETIME NULL AFTER payout_started_at;

CREATE INDEX idx_premium_payments_payout_status ON premium_payments(payout_status);
ALTER TABLE donations
    ADD COLUMN user_id INT NULL AFTER campaign_id,
    ADD COLUMN payment_token CHAR(64) NULL AFTER payment_id,
    ADD UNIQUE KEY uniq_donations_payment_token (payment_token),
    ADD KEY idx_donations_user_id (user_id);

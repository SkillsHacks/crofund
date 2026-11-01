ALTER TABLE campaigns
    ADD COLUMN campaign_country_code CHAR(2) NULL AFTER category,
    ADD COLUMN campaign_city VARCHAR(150) NULL AFTER campaign_country_code,
    ADD COLUMN campaign_postal_code VARCHAR(32) NULL AFTER campaign_city,
    ADD COLUMN beneficiary_type ENUM('self', 'individual', 'organization') NULL AFTER campaign_postal_code,
    ADD COLUMN beneficiary_name VARCHAR(255) NULL AFTER beneficiary_type,
    ADD COLUMN beneficiary_relationship VARCHAR(255) NULL AFTER beneficiary_name;

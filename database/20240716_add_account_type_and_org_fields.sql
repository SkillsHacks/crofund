-- Add account_type and organization-related fields to users table
ALTER TABLE users 
ADD COLUMN account_type VARCHAR(20) NOT NULL DEFAULT 'personal' COMMENT 'Account type: personal or organization' AFTER id,
ADD COLUMN organization_name VARCHAR(255) NULL COMMENT 'Organization name (for organization accounts)' AFTER nationality,
ADD COLUMN organization_type VARCHAR(100) NULL COMMENT 'Type of organization (e.g., NGO, company, etc.)' AFTER organization_name,
ADD COLUMN registration_number VARCHAR(100) NULL COMMENT 'Organization registration number (optional)' AFTER organization_type,
ADD COLUMN organization_website VARCHAR(255) NULL COMMENT 'Organization website URL (optional)' AFTER registration_number,
ADD COLUMN organization_contact VARCHAR(255) NULL COMMENT 'Organization contact information (optional)' AFTER organization_website;

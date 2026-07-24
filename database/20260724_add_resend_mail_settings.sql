INSERT INTO settings (setting_key, setting_value) VALUES
    ('resend_api_key', ''),
    ('mail_from_address', 'no-reply@crofund.org'),
    ('mail_from_name', 'CroFund'),
    ('mail_reply_to_address', 'support@crofund.org'),
    ('mail_reply_to_name', 'CroFund')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);

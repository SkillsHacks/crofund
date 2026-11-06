INSERT INTO settings (setting_key, setting_value)
VALUES ('mail_reply_to_address', 'support@crofunding.org')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

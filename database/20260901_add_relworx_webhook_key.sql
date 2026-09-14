INSERT INTO settings (setting_key, setting_value)
VALUES ('relworx_webhook_key', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

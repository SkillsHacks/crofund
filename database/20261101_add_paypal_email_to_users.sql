SET @paypal_email_column_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'users'
      AND column_name = 'paypal_email'
);

SET @paypal_email_column_sql = IF(
    @paypal_email_column_exists = 0,
    'ALTER TABLE users ADD COLUMN paypal_email VARCHAR(255) NULL AFTER phone',
    'SET @paypal_email_column_exists = 1'
);

PREPARE paypal_email_column_statement FROM @paypal_email_column_sql;
EXECUTE paypal_email_column_statement;
DEALLOCATE PREPARE paypal_email_column_statement;

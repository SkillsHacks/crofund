CREATE TABLE IF NOT EXISTS withdrawal_completion_notifications (
    withdrawal_id INT NOT NULL PRIMARY KEY,
    status ENUM('sent', 'skipped') NOT NULL,
    notified_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_withdrawal_completion_notifications_withdrawal
        FOREIGN KEY (withdrawal_id) REFERENCES withdrawals(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Do not send completion notices for withdrawals completed before this migration.
INSERT IGNORE INTO withdrawal_completion_notifications (withdrawal_id, status)
SELECT id, 'skipped'
FROM withdrawals
WHERE status = 'completed';

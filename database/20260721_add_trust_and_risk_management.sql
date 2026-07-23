ALTER TABLE users
ADD COLUMN risk_status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' AFTER status,
ADD COLUMN risk_status_reason TEXT NULL AFTER risk_status,
ADD COLUMN risk_review_expires_at DATETIME NULL AFTER risk_status_reason,
ADD COLUMN risk_last_action_at DATETIME NULL AFTER risk_review_expires_at,
ADD COLUMN risk_last_action_by INT NULL AFTER risk_last_action_at,
ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER risk_last_action_by;

CREATE INDEX idx_users_risk_status ON users (risk_status);
CREATE INDEX idx_users_is_admin ON users (is_admin);

CREATE TABLE IF NOT EXISTS user_risk_flags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    flag_type VARCHAR(100) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'medium',
    source VARCHAR(50) NOT NULL DEFAULT 'manual',
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    signal_key VARCHAR(100) NULL,
    signal_value VARCHAR(255) NULL,
    score_impact INT NOT NULL DEFAULT 0,
    reason TEXT NOT NULL,
    internal_notes TEXT NULL,
    created_by INT NULL,
    resolved_by INT NULL,
    review_expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    INDEX idx_user_risk_flags_user_id (user_id),
    INDEX idx_user_risk_flags_status (status),
    INDEX idx_user_risk_flags_type (flag_type),
    CONSTRAINT fk_user_risk_flags_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS moderation_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    admin_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    previous_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NULL,
    reason TEXT NOT NULL,
    internal_notes TEXT NULL,
    review_expires_at DATETIME NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_moderation_actions_user_id (user_id),
    INDEX idx_moderation_actions_admin_id (admin_id),
    INDEX idx_moderation_actions_created_at (created_at),
    CONSTRAINT fk_moderation_actions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_moderation_actions_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS account_appeals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    moderation_action_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    admin_notes TEXT NULL,
    reviewed_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL,
    INDEX idx_account_appeals_user_id (user_id),
    INDEX idx_account_appeals_status (status),
    CONSTRAINT fk_account_appeals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_account_appeals_action FOREIGN KEY (moderation_action_id) REFERENCES moderation_actions(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS risk_signal_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    signal_type VARCHAR(100) NOT NULL,
    signal_key VARCHAR(100) NULL,
    signal_value VARCHAR(255) NULL,
    score_impact INT NOT NULL DEFAULT 0,
    source VARCHAR(50) NOT NULL DEFAULT 'system',
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    device_id VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_risk_signal_events_user_id (user_id),
    INDEX idx_risk_signal_events_type (signal_type),
    INDEX idx_risk_signal_events_created_at (created_at),
    CONSTRAINT fk_risk_signal_events_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
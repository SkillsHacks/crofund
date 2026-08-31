CREATE TABLE IF NOT EXISTS premium_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    duration_days INT NOT NULL,
    features JSON NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS premium_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    transaction_id VARCHAR(80) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    gateway VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_reference VARCHAR(255) NULL,
    status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL
);

CREATE TABLE IF NOT EXISTS premium_memberships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    plan_code VARCHAR(50) NOT NULL,
    plan_title VARCHAR(120) NOT NULL,
    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

CREATE INDEX IF NOT EXISTS idx_premium_payments_user_status ON premium_payments(user_id, status);
CREATE INDEX IF NOT EXISTS idx_premium_memberships_expires_at ON premium_memberships(expires_at, status);

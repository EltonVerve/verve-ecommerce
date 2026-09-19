CREATE TABLE IF NOT EXISTS auth_rate_limits (
    bucket CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at BIGINT NOT NULL,
    INDEX idx_auth_expiry (expires_at)
) ENGINE=InnoDB;

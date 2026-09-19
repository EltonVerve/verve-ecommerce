CREATE TABLE IF NOT EXISTS customer_login_codes (
    challenge CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id INT NULL,
    code_hash VARCHAR(255) NOT NULL,
    password_fingerprint CHAR(64) NOT NULL,
    expires_at BIGINT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX (expires_at)
) ENGINE=InnoDB;

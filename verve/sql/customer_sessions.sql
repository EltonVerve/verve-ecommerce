CREATE TABLE IF NOT EXISTS customer_sessions (
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id INT NOT NULL,
    expires_at BIGINT NOT NULL DEFAULT 0,
    password_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    INDEX (user_id)
);

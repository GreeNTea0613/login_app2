CREATE DATABASE IF NOT EXISTS app CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE app;

CREATE TABLE users (
                       id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                       email VARCHAR(255) NOT NULL UNIQUE,
                       password_hash VARCHAR(255) NOT NULL,
                       name VARCHAR(100),
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                       failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
                       last_failed_at DATETIME DEFAULT NULL,
                       is_locked TINYINT(1) NOT NULL DEFAULT 0,
                       lock_expires_at DATETIME DEFAULT NULL,
                       INDEX(email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

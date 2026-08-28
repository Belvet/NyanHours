SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('operator', 'admin') NOT NULL DEFAULT 'operator',
    hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT chk_users_hourly_rate CHECK (hourly_rate >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#5046E5',
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_clients_name UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE time_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    source ENUM('timesheet', 'tracker') NOT NULL DEFAULT 'tracker',
    description VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_time_entries_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_time_entries_client FOREIGN KEY (client_id)
        REFERENCES clients (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_time_entries_duration CHECK (duration_minutes BETWEEN 1 AND 1440),

    INDEX idx_time_entries_user_date (user_id, work_date),
    INDEX idx_time_entries_client_date (client_id, work_date),
    INDEX idx_time_entries_work_date (work_date),
    INDEX idx_time_entries_cell (user_id, client_id, work_date, source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE closed_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year SMALLINT UNSIGNED NOT NULL,
    month TINYINT UNSIGNED NOT NULL,
    closed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_by BIGINT UNSIGNED NOT NULL,

    CONSTRAINT fk_closed_periods_user FOREIGN KEY (closed_by)
        REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_closed_periods_year_month UNIQUE (year, month),
    CONSTRAINT chk_closed_periods_year CHECK (year BETWEEN 2000 AND 2100),
    CONSTRAINT chk_closed_periods_month CHECK (month BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

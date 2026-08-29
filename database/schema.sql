SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE nh_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('operator', 'admin', 'owner') NOT NULL DEFAULT 'operator',
    owner_slot TINYINT GENERATED ALWAYS AS (CASE WHEN role = 'owner' THEN 1 ELSE NULL END) STORED,
    hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_single_owner UNIQUE (owner_slot),
    CONSTRAINT chk_users_hourly_rate CHECK (hourly_rate >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nh_clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#5046E5',
    hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_clients_name UNIQUE (name),
    CONSTRAINT chk_clients_hourly_rate CHECK (hourly_rate >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nh_time_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    client_hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    user_hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    is_owner_work BOOLEAN NOT NULL DEFAULT FALSE,
    source ENUM('timesheet', 'tracker') NOT NULL DEFAULT 'tracker',
    description VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_time_entries_user FOREIGN KEY (user_id)
        REFERENCES nh_users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_time_entries_client FOREIGN KEY (client_id)
        REFERENCES nh_clients (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_time_entries_duration CHECK (duration_minutes BETWEEN 1 AND 1440),
    CONSTRAINT chk_time_entries_client_rate CHECK (client_hourly_rate >= 0),
    CONSTRAINT chk_time_entries_user_rate CHECK (user_hourly_rate >= 0),

    INDEX idx_time_entries_user_date (user_id, work_date),
    INDEX idx_time_entries_client_date (client_id, work_date),
    INDEX idx_time_entries_work_date (work_date),
    INDEX idx_time_entries_cell (user_id, client_id, work_date, source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nh_closed_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year SMALLINT UNSIGNED NOT NULL,
    month TINYINT UNSIGNED NOT NULL,
    closed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_by BIGINT UNSIGNED NOT NULL,

    CONSTRAINT fk_closed_periods_user FOREIGN KEY (closed_by)
        REFERENCES nh_users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT uq_closed_periods_year_month UNIQUE (year, month),
    CONSTRAINT chk_closed_periods_year CHECK (year BETWEEN 2000 AND 2100),
    CONSTRAINT chk_closed_periods_month CHECK (month BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

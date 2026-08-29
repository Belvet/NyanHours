ALTER TABLE time_entries
    ADD COLUMN client_hourly_rate DECIMAL(10, 2) NULL AFTER duration_minutes,
    ADD COLUMN user_hourly_rate DECIMAL(10, 2) NULL AFTER client_hourly_rate;

UPDATE time_entries te
INNER JOIN clients c ON c.id = te.client_id
INNER JOIN users u ON u.id = te.user_id
SET te.client_hourly_rate = c.hourly_rate,
    te.user_hourly_rate = u.hourly_rate;

ALTER TABLE time_entries
    MODIFY client_hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    MODIFY user_hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    ADD CONSTRAINT chk_time_entries_client_rate CHECK (client_hourly_rate >= 0),
    ADD CONSTRAINT chk_time_entries_user_rate CHECK (user_hourly_rate >= 0);

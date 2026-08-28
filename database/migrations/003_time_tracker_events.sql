ALTER TABLE time_entries
    DROP INDEX uq_time_entries_user_client_date,
    ADD COLUMN source ENUM('timesheet', 'tracker') NOT NULL DEFAULT 'timesheet' AFTER duration_minutes,
    ADD INDEX idx_time_entries_cell (user_id, client_id, work_date, source);

ALTER TABLE time_entries
    MODIFY source ENUM('timesheet', 'tracker') NOT NULL DEFAULT 'tracker';

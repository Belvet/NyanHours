ALTER TABLE time_entries
    ADD COLUMN duration_minutes SMALLINT UNSIGNED NULL AFTER work_date;

UPDATE time_entries
SET duration_minutes = TIMESTAMPDIFF(
    MINUTE,
    CONCAT(work_date, ' ', start_time),
    CONCAT(work_date, ' ', end_time)
);

ALTER TABLE time_entries
    MODIFY duration_minutes SMALLINT UNSIGNED NOT NULL,
    MODIFY start_time TIME NULL,
    MODIFY end_time TIME NULL,
    ADD CONSTRAINT chk_time_entries_duration CHECK (duration_minutes BETWEEN 1 AND 1440),
    ADD CONSTRAINT uq_time_entries_user_client_date UNIQUE (user_id, client_id, work_date);

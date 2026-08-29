ALTER TABLE time_entries
    ADD COLUMN is_owner_work BOOLEAN NOT NULL DEFAULT FALSE AFTER user_hourly_rate;

UPDATE time_entries te
INNER JOIN users u ON u.id = te.user_id
SET te.is_owner_work = (u.role = 'owner');

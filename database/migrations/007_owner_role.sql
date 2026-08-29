ALTER TABLE users
    MODIFY role ENUM('operator', 'admin', 'owner') NOT NULL DEFAULT 'operator';

UPDATE users
SET role = 'owner', hourly_rate = 0.00
WHERE id = (
    SELECT id FROM (
        SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1
    ) AS first_admin
);

UPDATE time_entries te
INNER JOIN users u ON u.id = te.user_id
SET te.user_hourly_rate = 0.00
WHERE u.role = 'owner';

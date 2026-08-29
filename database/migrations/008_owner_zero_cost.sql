UPDATE time_entries te
INNER JOIN users u ON u.id = te.user_id
SET te.user_hourly_rate = 0.00
WHERE u.role = 'owner';

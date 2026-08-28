ALTER TABLE users
    MODIFY role ENUM('employee', 'operator', 'admin') NOT NULL DEFAULT 'operator';

UPDATE users SET role = 'operator' WHERE role = 'employee';

ALTER TABLE users
    MODIFY role ENUM('operator', 'admin') NOT NULL DEFAULT 'operator';

ALTER TABLE users CHANGE COLUMN email username VARCHAR(50) NOT NULL;

UPDATE users
SET username = LOWER(SUBSTRING_INDEX(username, '@', 1))
WHERE username LIKE '%@nyanhours.local';

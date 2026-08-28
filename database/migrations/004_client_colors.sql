ALTER TABLE clients
    ADD COLUMN color CHAR(7) NOT NULL DEFAULT '#5046E5' AFTER name;

UPDATE clients SET color = '#E85D75' WHERE name = 'Cinthya';
UPDATE clients SET color = '#2D9CDB' WHERE name = 'Kelly';
UPDATE clients SET color = '#27AE60' WHERE name = 'Erena';

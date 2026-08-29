ALTER TABLE users
    ADD COLUMN owner_slot TINYINT
        GENERATED ALWAYS AS (CASE WHEN role = 'owner' THEN 1 ELSE NULL END) STORED
        AFTER role,
    ADD CONSTRAINT uq_users_single_owner UNIQUE (owner_slot);

ALTER TABLE clients
    ADD COLUMN hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER color,
    ADD CONSTRAINT chk_clients_hourly_rate CHECK (hourly_rate >= 0);

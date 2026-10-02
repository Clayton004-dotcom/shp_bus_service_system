CREATE DATABASE IF NOT EXISTS shp_bus_service
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE shp_bus_service;

CREATE TABLE IF NOT EXISTS passengers (
  passenger_id VARCHAR(64) NOT NULL PRIMARY KEY,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fare_transactions (
  transaction_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  passenger_id VARCHAR(64) NOT NULL,
  pcard_id VARCHAR(64) NOT NULL,
  bus_id VARCHAR(64) NOT NULL,
  fare_amount DECIMAL(10,2) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fare_transactions_passenger
    FOREIGN KEY (passenger_id) REFERENCES passengers(passenger_id),
  INDEX idx_fare_transactions_timestamp (`timestamp`),
  INDEX idx_fare_transactions_passenger (passenger_id)
) ENGINE=InnoDB;

-- Passengers can create accounts from the sign-in page; passwords are hashed in PHP.
-- For administrator-created accounts, use password_hash() in PHP and never store plaintext passwords.
-- Example (run in a trusted PHP shell after replacing the sample values):
-- INSERT INTO passengers (passenger_id, password_hash) VALUES ('SHP-10001', '<PHP password_hash() output>');

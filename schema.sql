-- ============================================================
-- Curtz Home — Database Schema
-- Run this once: mysql -u root -p curtzhome < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS curtzhome CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE curtzhome;

-- USERS
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(80)  NOT NULL,
    last_name     VARCHAR(80)  NOT NULL,
    email         VARCHAR(180) NOT NULL UNIQUE,
    phone         VARCHAR(30),
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user','admin') DEFAULT 'user',
    address       TEXT,
    nin           VARCHAR(40),
    status        ENUM('active','suspended') DEFAULT 'active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed admin account  (password: Admin@2025)
INSERT IGNORE INTO users (first_name,last_name,email,phone,password_hash,role)
VALUES ('Curtz','Admin','admin@curtzhome.com','+1000000000',
        '$2y$12$LtL0JrKBrR2RbG.pIW3MquYHaxXtT8rLcT2hhMsUY/7B7GW6VJXQ6','admin');

-- INVESTMENTS
CREATE TABLE IF NOT EXISTS investments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    plan            ENUM('starter','growth','elite') NOT NULL,
    amount          DECIMAL(18,2) NOT NULL,
    rate            DECIMAL(5,2)  NOT NULL,
    tenure_months   TINYINT       NOT NULL,
    payout_freq     ENUM('monthly','quarterly','weekly') DEFAULT 'monthly',
    expected_return DECIMAL(18,2) NOT NULL,
    total_value     DECIMAL(18,2) NOT NULL,
    maturity_date   DATE          NOT NULL,
    payment_method  VARCHAR(40),
    tx_ref          VARCHAR(120),
    status          ENUM('pending','active','completed','cancelled') DEFAULT 'pending',
    admin_note      TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PROPERTY PURCHASES
CREATE TABLE IF NOT EXISTS property_purchases (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    property_id     INT,
    property_name   VARCHAR(200) NOT NULL,
    location        VARCHAR(200),
    purchase_type   ENUM('outright','installment','mortgage') DEFAULT 'outright',
    price           DECIMAL(18,2),
    deposit_amount  DECIMAL(18,2),
    payment_method  VARCHAR(40),
    tx_ref          VARCHAR(120),
    full_name       VARCHAR(200),
    phone           VARCHAR(30),
    nin             VARCHAR(40),
    source_of_funds VARCHAR(80),
    address         TEXT,
    notes           TEXT,
    status          ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    admin_note      TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- CONTACT MESSAGES
CREATE TABLE IF NOT EXISTS contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80),
    last_name  VARCHAR(80),
    email      VARCHAR(180),
    phone      VARCHAR(30),
    interest   VARCHAR(60),
    message    TEXT,
    status     ENUM('new','read','replied') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ADMIN ACTIVITY LOG
CREATE TABLE IF NOT EXISTS admin_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    admin_id   INT NOT NULL,
    action     VARCHAR(200),
    target     VARCHAR(100),
    target_id  INT,
    detail     TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

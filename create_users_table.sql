-- SQL Script to create users table for ScreenHub app
-- Compatible with XAMPP MySQL
-- Run in phpMyAdmin or MySQL CLI: mysql -u root -p < create_users_table.sql

-- Step 1: Create database if not exists
CREATE DATABASE IF NOT EXISTS screenhub_db;
USE screenhub_db;

-- Step 2: Drop table if exists (optional, for clean recreate)
-- DROP TABLE IF EXISTS users;

-- Step 3: Complete users table with all fields for signup
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  first_name VARCHAR(50) NOT NULL,
  middle_name VARCHAR(50) NULL,
  last_name VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  birthdate DATE NOT NULL,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(500) DEFAULT 'https://i.pravatar.cc/150?img=3',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example: Insert test user (password: 'test12345' hashed with password_hash)
-- Run PHP: echo password_hash('test12345', PASSWORD_DEFAULT);
INSERT INTO users (username, first_name, middle_name, last_name, email, birthdate, password, avatar) VALUES 
('testuser', 'Test', NULL, 'User', 'test@example.com', '1990-01-01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'https://i.pravatar.cc/150?img=3')
ON DUPLICATE KEY UPDATE avatar=avatar;

-- Verify
-- SELECT * FROM users;
-- DESCRIBE users;

-- Usage:
-- 1. Save as create_users_table.sql
-- 2. phpMyAdmin: Import or SQL tab
-- 3. CLI: mysql -u root -p screenhub_db < create_users_table.sql
-- Table ready for signup.php, login.php etc.


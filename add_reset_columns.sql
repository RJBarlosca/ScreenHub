-- Add forgot password columns to users table
-- Safe: Idempotent ALTER (won't error if columns exist)
-- Run in phpMyAdmin SQL tab or CLI: mysql -u root -p screenhub_db < add_reset_columns.sql

USE screenhub_db;

-- Add columns if not exists
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL;

-- Add indexes for performance/security
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_reset_token (reset_token),
ADD INDEX IF NOT EXISTS idx_reset_expires (reset_expires);

-- Verify structure
DESCRIBE users;

-- Clean any existing test tokens (optional)
UPDATE users SET reset_token = NULL, reset_expires = NULL;

-- Test query
-- SELECT id, username, email, reset_token, reset_expires FROM users;


-- Add 4-digit PIN support
USE screenhub_db;

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS reset_code VARCHAR(6) NULL,
ADD COLUMN IF NOT EXISTS reset_code_expires DATETIME NULL;

-- Clean old
UPDATE users SET reset_token = NULL, reset_expires = NULL, reset_code = NULL, reset_code_expires = NULL;

DESCRIBE users;


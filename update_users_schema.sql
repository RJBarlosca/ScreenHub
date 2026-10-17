USE screenhub_db;

-- Full recreate for new schema (safe)
DROP TABLE IF EXISTS users;

-- Create complete table
CREATE TABLE users (
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

-- Test user (password: test12345)
INSERT INTO users (username, first_name, middle_name, last_name, email, birthdate, password, avatar) VALUES 
('testuser', 'Test', NULL, 'User', 'test@example.com', '1990-01-01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'https://i.pravatar.cc/150?img=3');

-- Verify
DESCRIBE users;
SELECT * FROM users;

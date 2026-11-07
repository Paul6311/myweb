-- OPTIONAL: run this in phpMyAdmin (SQL tab) if you prefer to create the database yourself.
-- The site also creates all of this automatically the first time it runs.
CREATE DATABASE IF NOT EXISTS portfolio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE portfolio;

CREATE TABLE IF NOT EXISTS messages(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100), email VARCHAR(255), subject VARCHAR(255), message TEXT, ip VARCHAR(45),
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin(
  id INT PRIMARY KEY, username VARCHAR(100), pass VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS files(
  id INT AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(20), stored_name VARCHAR(100), original VARCHAR(255),
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attempts(
  ip VARCHAR(45), created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX(ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

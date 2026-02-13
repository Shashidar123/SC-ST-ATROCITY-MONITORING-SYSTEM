-- Drop existing users table if it exists
DROP TABLE IF EXISTS users;

-- Create users table with correct structure
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('police', 'rdo', 'c_section', 'collector', 'social_welfare') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 
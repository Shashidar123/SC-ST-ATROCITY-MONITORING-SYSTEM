CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default users (using password_hash for passwords)
INSERT INTO users (username, password, role) VALUES
('police', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police'), -- password: police@123
('rdo', '$2y$10$JQv6h9hVt9JLfE6uIHQkPOYhxC7OT6RzJ1Vz6.4rKjr5YQbmryOgG', 'rdo'), -- password: rdo@456
('c_section', '$2y$10$wk1AQKrqUMG7zK9K9mzOyOkG8kJ0QGy0O1PzY.hlvqZFJ.0IXqOjO', 'c_section'), -- password: c@1993
('collector', '$2y$10$uKH.qHy0K6tqR9Y3ZkIbweMbg6wIWEoGEpVDqF1OsNtZ0A4fMg7Hy', 'collector'), -- password: SecurePass123!
('social_welfare', '$2y$10$YwI9h8PyF0v5WyRhGZ0kPeqK9nGGkCkXY5K5nJK5.XBbQ0VZgj0Uy', 'social_welfare') -- password: social_welfare@123
ON DUPLICATE KEY UPDATE username=username; 
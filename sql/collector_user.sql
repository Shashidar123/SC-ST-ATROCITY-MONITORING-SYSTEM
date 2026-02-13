-- Insert collector user if not exists
INSERT INTO users (username, password, role, name, email, created_at)
SELECT 'collector', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'collector', 'District Collector', 'collector@district.gov', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'collector'
); 
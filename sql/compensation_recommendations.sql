CREATE TABLE IF NOT EXISTS compensation_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    recommended_amount DECIMAL(10,2) NOT NULL,
    recommended_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (recommended_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 
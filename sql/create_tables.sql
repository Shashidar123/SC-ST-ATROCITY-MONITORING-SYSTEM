-- Create compensation_approvals table
CREATE TABLE IF NOT EXISTS compensation_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    approved_amount DECIMAL(10,2) NOT NULL,
    approved_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Create compensation_recommendations table
CREATE TABLE IF NOT EXISTS compensation_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    recommended_amount DECIMAL(10,2) NOT NULL,
    recommended_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(id),
    FOREIGN KEY (recommended_by) REFERENCES users(id)
); 
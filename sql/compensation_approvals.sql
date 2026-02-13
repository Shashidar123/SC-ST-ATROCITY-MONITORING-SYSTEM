CREATE TABLE IF NOT EXISTS compensation_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    approved_amount DECIMAL(10,2) NOT NULL,
    approved_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 
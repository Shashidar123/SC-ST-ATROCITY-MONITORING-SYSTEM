-- Create database if not exists
CREATE DATABASE IF NOT EXISTS sc_st_cases;
USE sc_st_cases;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'police', 'c_section', 'collector', 'social_welfare') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create cases table
CREATE TABLE IF NOT EXISTS cases (
    case_id INT AUTO_INCREMENT PRIMARY KEY,
    victim_name VARCHAR(100) NOT NULL,
    victim_address TEXT NOT NULL,
    case_type VARCHAR(50) NOT NULL,
    incident_date DATE NOT NULL,
    incident_description TEXT NOT NULL,
    filed_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    assigned_officer VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (filed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create case_status table
CREATE TABLE IF NOT EXISTS case_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    status ENUM('filed', 'police_review', 'c_section_review', 'collector_review', 'social_welfare_review', 'completed', 'rejected') NOT NULL,
    comments TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create compensation_recommendations table
CREATE TABLE IF NOT EXISTS compensation_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    recommended_amount DECIMAL(10,2) NOT NULL,
    recommended_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (recommended_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create compensation_approvals table
CREATE TABLE IF NOT EXISTS compensation_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    approved_amount DECIMAL(10,2) NOT NULL,
    approved_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create investigation_reports table
CREATE TABLE IF NOT EXISTS investigation_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    findings TEXT,
    witness_summary TEXT,
    recommendations TEXT,
    submitted_by INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'submitted', 'reviewed') DEFAULT 'draft',
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (submitted_by) REFERENCES users(id)
);

-- Insert default users if they don't exist
INSERT INTO users (username, password, role, name, email, created_at)
SELECT 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Admin', 'admin@system.gov', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

INSERT INTO users (username, password, role, name, email, created_at)
SELECT 'police', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'police', 'Police Officer', 'police@district.gov', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'police');

INSERT INTO users (username, password, role, name, email, created_at)
SELECT 'c_section', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'c_section', 'C-Section Officer', 'c_section@district.gov', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'c_section');

INSERT INTO users (username, password, role, name, email, created_at)
SELECT 'collector', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'collector', 'District Collector', 'collector@district.gov', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'collector');

INSERT INTO users (username, password, role, name, email, created_at)
SELECT 'social_welfare', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'social_welfare', 'Social Welfare Officer', 'social_welfare@district.gov', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'social_welfare'); 
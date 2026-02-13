-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop all existing tables
DROP TABLE IF EXISTS compensation;
DROP TABLE IF EXISTS case_updates;
DROP TABLE IF EXISTS case_documents;
DROP TABLE IF EXISTS case_status;
DROP TABLE IF EXISTS cases;
DROP TABLE IF EXISTS users;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('police', 'rdo', 'c_section', 'collector', 'social_welfare') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cases table
CREATE TABLE cases (
    case_id INT AUTO_INCREMENT PRIMARY KEY,
    victim_name VARCHAR(100) NOT NULL,
    victim_address TEXT NOT NULL,
    incident_date DATE NOT NULL,
    incident_description TEXT NOT NULL,
    case_type ENUM('SC', 'ST') NOT NULL,
    status ENUM('pending', 'police_review', 'sp_review', 'c_section_review', 'collector_review', 'social_welfare_review', 'completed', 'rejected') NOT NULL DEFAULT 'pending',
    filed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (filed_by) REFERENCES users(id)
);

-- Create case_documents table
CREATE TABLE case_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Create case_status table
CREATE TABLE case_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    status ENUM('pending', 'police_review', 'sp_review', 'c_section_review', 'collector_review', 'social_welfare_review', 'completed', 'rejected') NOT NULL,
    comments TEXT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Create compensation table
CREATE TABLE compensation (
    compensation_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'disbursed') NOT NULL DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
); 
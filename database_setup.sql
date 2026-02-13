-- Create Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('police', 'rdo', 'c_section', 'collector', 'social_welfare') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Cases table
CREATE TABLE IF NOT EXISTS cases (
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

-- Create Case Updates table
CREATE TABLE IF NOT EXISTS case_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    status ENUM('pending', 'police_review', 'sp_review', 'c_section_review', 'collector_review', 'social_welfare_review', 'completed', 'rejected') NOT NULL,
    comments TEXT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Create Compensation table
CREATE TABLE IF NOT EXISTS compensation (
    compensation_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'disbursed') NOT NULL DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Create Documents table
CREATE TABLE IF NOT EXISTS documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
); 
CREATE TABLE IF NOT EXISTS recipients (
    recipient_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    recipient_name VARCHAR(100) NOT NULL,
    relationship_to_victim VARCHAR(50) NOT NULL,
    age INT NOT NULL,
    gender VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    education_qualification VARCHAR(100),
    job_assigned VARCHAR(100),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    ifsc_code VARCHAR(20),
    compensation_amount DECIMAL(10,2),
    draft_saved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create recipient_documents table to store uploaded documents
CREATE TABLE IF NOT EXISTS recipient_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES recipients(recipient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Fix database schema for IO functionality
-- Add missing columns to cases table

-- Add IO-related columns to cases table if they don't exist
ALTER TABLE cases 
ADD COLUMN IF NOT EXISTS io_username VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS io_report TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS io_witness_statements TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS io_recommendations TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS sp_instructions TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS fir_number VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS fir_date DATE DEFAULT NULL,
ADD COLUMN IF NOT EXISTS police_station VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS victim_age INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS victim_gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
ADD COLUMN IF NOT EXISTS victim_caste VARCHAR(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS victim_contact VARCHAR(20) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS victim_aadhaar VARCHAR(12) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS victim_statement TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS medical_report TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS case_sections TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS investigating_officer VARCHAR(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS forward_to_sp BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update case_status table to include sp_review_from_io status
ALTER TABLE case_status 
MODIFY COLUMN status ENUM('pending', 'police_review', 'sp_review', 'sp_review_from_io', 'c_section_review', 'collector_review', 'social_welfare_review', 'completed', 'rejected', 'io_investigation') NOT NULL DEFAULT 'pending';

-- Update cases table status enum to include sp_review_from_io
ALTER TABLE cases 
MODIFY COLUMN status ENUM('pending', 'police_review', 'sp_review', 'sp_review_from_io', 'c_section_review', 'collector_review', 'social_welfare_review', 'completed', 'rejected', 'io_investigation', 'dcr_review') NOT NULL DEFAULT 'pending';

-- Create case_documents table if it doesn't exist
CREATE TABLE IF NOT EXISTS case_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create investigation_reports table if it doesn't exist
CREATE TABLE IF NOT EXISTS investigation_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    findings TEXT,
    witness_summary TEXT,
    recommendations TEXT,
    submitted_by INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'submitted', 'reviewed') DEFAULT 'draft',
    FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add IO role to users table if it doesn't exist
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'police', 'rdo', 'io', 'c_section', 'collector', 'social_welfare') NOT NULL;

-- Insert sample IO user if not exists
INSERT INTO users (username, password, role, name, email, created_at)
SELECT 'io_officer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'io', 'IO Officer', 'io@district.gov', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'io_officer');

-- Create uploads directory if it doesn't exist
-- Note: This needs to be done manually or via PHP
-- mkdir -p uploads
-- chmod 755 uploads 
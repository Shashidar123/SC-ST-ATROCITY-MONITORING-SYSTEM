-- Add job_allocated column to recommendations table
ALTER TABLE recommendations ADD COLUMN job_allocated VARCHAR(20) NOT NULL DEFAULT 'peon'; 
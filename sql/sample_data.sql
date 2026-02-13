-- Insert sample cases
INSERT INTO cases (victim_name, incident_date, incident_location, incident_details, filed_by, created_at)
VALUES 
('Suresh Kumar', '2024-03-01', 'Madurai District', 'Discrimination and harassment at workplace', 1, NOW()),
('John Doe', '2024-03-02', 'Sample Location 1', 'Sample incident details 1', 1, NOW()),
('Jane Smith', '2024-03-03', 'Sample Location 2', 'Sample incident details 2', 1, NOW());

-- Insert case statuses
INSERT INTO case_status (case_id, status, comments, updated_by, created_at)
VALUES
(1, 'filed', 'Case filed by police', 1, NOW()),
(1, 'rdo_review', 'Forwarded to RDO for review', 1, NOW() + INTERVAL 1 DAY),
(1, 'c_section_review', 'Forwarded to C-Section', 2, NOW() + INTERVAL 2 DAY),
(1, 'collector_review', 'Forwarded to Collector', 3, NOW() + INTERVAL 3 DAY),
(2, 'filed', 'Case filed by police', 1, NOW()),
(2, 'rdo_review', 'Forwarded to RDO for review', 1, NOW() + INTERVAL 1 DAY),
(3, 'filed', 'Case filed by police', 1, NOW());

-- Insert compensation recommendations
INSERT INTO compensation_recommendations (case_id, recommended_amount, recommended_by, created_at)
VALUES
(1, 100000.00, 3, NOW() + INTERVAL 3 DAY); 
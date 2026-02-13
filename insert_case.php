<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sc_st_cases', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert case
    $stmt = $pdo->prepare('INSERT INTO cases (victim_name, victim_address, case_type, incident_date, incident_description, filed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute(['Suresh Kumar', 'Madurai District', 'Discrimination', '2024-03-01', 'Discrimination and harassment at workplace', 1]);
    $case_id = $pdo->lastInsertId();

    // Insert case statuses
    $statuses = [
        ['filed', 'Case filed by police', 1],
        ['rdo_review', 'Forwarded to RDO for review', 1],
        ['c_section_review', 'Forwarded to C-Section', 2],
        ['collector_review', 'Case forwarded to collector for review', 3]
    ];

    $stmt = $pdo->prepare('INSERT INTO case_status (case_id, status, comments, updated_by, created_at) VALUES (?, ?, ?, ?, NOW() + INTERVAL ? DAY)');
    foreach ($statuses as $index => $status) {
        $stmt->execute([$case_id, $status[0], $status[1], $status[2], $index]);
    }

    // Insert compensation recommendation
    $stmt = $pdo->prepare('INSERT INTO compensation_recommendations (case_id, recommended_amount, recommended_by, created_at) VALUES (?, ?, ?, NOW() + INTERVAL 3 DAY)');
    $stmt->execute([$case_id, 100000.00, 3]);

    echo "Case inserted successfully with ID: " . $case_id;

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
} 
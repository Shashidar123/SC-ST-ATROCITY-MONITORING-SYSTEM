<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sc_st_cases', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fix case_status table structure
    $pdo->exec("ALTER TABLE case_status MODIFY COLUMN status ENUM('filed', 'police_review', 'c_section_review', 'collector_review', 'social_welfare_review', 'completed', 'rejected') NOT NULL DEFAULT 'filed'");
    echo "Updated case_status table structure\n";

    // Fix empty status values
    $pdo->exec("UPDATE case_status SET status = 'filed' WHERE status = '' AND comments LIKE '%filed%'");
    $pdo->exec("UPDATE case_status SET status = 'police_review' WHERE status = '' AND comments LIKE '%RDO%'");
    $pdo->exec("UPDATE case_status SET status = 'filed' WHERE status = ''");
    echo "Fixed empty status values\n";

    // Update sp_review to c_section_review
    $pdo->exec("UPDATE case_status SET status = 'c_section_review' WHERE status = 'sp_review'");
    echo "Updated sp_review to c_section_review\n";

    // Check the results
    echo "\nCase Status Data after fixes:\n";
    $stmt = $pdo->query('SELECT * FROM case_status ORDER BY created_at DESC');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
} 
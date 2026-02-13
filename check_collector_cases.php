<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sc_st_cases', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get cases in collector review
    $stmt = $pdo->query("
        WITH LatestStatus AS (
            SELECT 
                case_id,
                status,
                created_at,
                ROW_NUMBER() OVER (PARTITION BY case_id ORDER BY created_at DESC) as rn
            FROM case_status
        )
        SELECT 
            c.*,
            ls.status as current_status,
            u.username as filed_by_name,
            cr.recommended_amount,
            cr.created_at as recommendation_date
        FROM cases c
        JOIN LatestStatus ls ON c.case_id = ls.case_id AND ls.rn = 1
        JOIN users u ON c.filed_by = u.id
        LEFT JOIN compensation_recommendations cr ON c.case_id = cr.case_id
        WHERE ls.status = 'collector_review'
        ORDER BY c.created_at DESC
    ");

    echo "Cases in collector review:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

    // Get all latest statuses
    $stmt = $pdo->query("
        WITH LatestStatus AS (
            SELECT 
                case_id,
                status,
                created_at,
                ROW_NUMBER() OVER (PARTITION BY case_id ORDER BY created_at DESC) as rn
            FROM case_status
        )
        SELECT c.case_id, c.victim_name, ls.status
        FROM cases c
        JOIN LatestStatus ls ON c.case_id = ls.case_id AND ls.rn = 1
        ORDER BY c.case_id
    ");

    echo "\nAll cases with their latest status:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
        echo "\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
} 
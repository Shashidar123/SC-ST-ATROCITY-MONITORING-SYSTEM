<?php
require_once '../includes/auth.php';
requireRole('collector');

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        WITH LatestStatus AS (
            SELECT 
                case_id,
                status,
                ROW_NUMBER() OVER (PARTITION BY case_id ORDER BY created_at DESC) as rn
            FROM case_status
        )
        SELECT 
            (SELECT COUNT(*) FROM cases) as total_cases,
            SUM(CASE WHEN status = 'collector_review' AND rn = 1 THEN 1 ELSE 0 END) as new_cases,
            SUM(CASE WHEN status = 'collector_reverify' AND rn = 1 THEN 1 ELSE 0 END) as inprogress_cases,
            SUM(CASE WHEN (status = 'collector_approved' OR status = 'collector_allotted') AND rn = 1 THEN 1 ELSE 0 END) as resolved_cases,
            SUM(CASE WHEN status = 'collector_rejected' AND rn = 1 THEN 1 ELSE 0 END) as rejected_cases
        FROM LatestStatus
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log the stats for debugging
    error_log("AJAX Stats Update: " . print_r($stats, true));
    
    echo json_encode($stats);
} catch(PDOException $e) {
    error_log("Error in get_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 
<?php
require_once '../includes/auth.php';
requireRole('social_welfare');

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        WITH LatestStatus AS (
            SELECT 
                case_id,
                status,
                created_at,
                ROW_NUMBER() OVER (PARTITION BY case_id ORDER BY created_at DESC) as rn
            FROM case_status
        )
        SELECT 
            (SELECT COUNT(*) FROM cases) as total_cases,
            (
                SELECT COUNT(*) 
                FROM LatestStatus
                WHERE status = 'social_welfare_review'
                AND rn = 1
            ) as pending_verification,
            (
                SELECT COUNT(*) 
                FROM LatestStatus
                WHERE status = 'compensation_approved'
                AND rn = 1
            ) as verified_cases,
            (
                SELECT COUNT(*) 
                FROM LatestStatus
                WHERE status = 'compensation_paid'
                AND rn = 1
            ) as compensated_cases
    ");
    
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Log the stats for debugging
    error_log("Social Welfare Stats Update: " . print_r($stats, true));
    
    echo json_encode($stats);
} catch(PDOException $e) {
    error_log("Error in get_social_welfare_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 
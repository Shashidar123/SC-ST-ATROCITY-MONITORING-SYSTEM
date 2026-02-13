<?php
require_once '../includes/auth.php';
requireRole('collector');
require_once '../includes/db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        WITH LatestStatus AS (
            SELECT 
                case_id,
                status as current_status,
                created_at as last_updated,
                ROW_NUMBER() OVER (PARTITION BY case_id ORDER BY created_at DESC) as rn
            FROM case_status
        )
        SELECT 
            c.case_id,
            c.fir_number,
            c.created_at,
            c.victim_name,
            c.victim_contact,
            c.case_type,
            c.priority,
            c.assigned_officer,
            c.police_station,
            ls.current_status,
            ls.last_updated
        FROM cases c
        JOIN LatestStatus ls ON c.case_id = ls.case_id AND ls.rn = 1
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($cases as $case) {
        // Determine collector status
        $status = strtolower($case['current_status']);
        $collector_statuses = ['collector_approved', 'collector_allotted', 'collector_reverify', 'collector_rejected'];
        $display_status = 'new';
        if ($status === 'collector_reverify') {
            $display_status = 'inprogress';
        } elseif ($status === 'collector_approved' || $status === 'collector_allotted') {
            $display_status = 'resolved';
        } elseif ($status === 'collector_rejected') {
            $display_status = 'rejected';
        } elseif (in_array($status, $collector_statuses)) {
            $display_status = 'resolved';
        } else if ($status === 'collector_review') {
            $display_status = 'new';
        }
        // Only show in dashboard if not resolved or rejected
        $show_in_dashboard = !in_array($display_status, ['resolved', 'rejected']);
        $case['dashboard_status'] = $display_status;
        $case['show_in_dashboard'] = $show_in_dashboard;
        $result[] = $case;
    }
    echo json_encode($result);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
} 
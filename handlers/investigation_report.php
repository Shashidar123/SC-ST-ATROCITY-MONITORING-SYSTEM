<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $case_id = $_GET['case_id'] ?? null;
    if (!$case_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing case_id']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT * FROM investigation_reports WHERE case_id = ? ORDER BY submitted_at DESC LIMIT 1');
    $stmt->execute([$case_id]);
    $report = $stmt->fetch();
    echo json_encode(['report' => $report]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id = $_POST['case_id'] ?? null;
    $findings = $_POST['findings'] ?? '';
    $witness_summary = $_POST['witness_summary'] ?? '';
    $recommendations = $_POST['recommendations'] ?? '';
    $submitted_by = $_SESSION['user_id'] ?? null;
    $status = $_POST['status'] ?? 'draft';
    
    if (!$case_id || !$submitted_by) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update the cases table with IO report data
        $stmt = $pdo->prepare('UPDATE cases SET io_report = ?, io_witness_statements = ?, io_recommendations = ? WHERE case_id = ?');
        $stmt->execute([$findings, $witness_summary, $recommendations, $case_id]);
        
        // Insert into investigation_reports table
        $stmt = $pdo->prepare('INSERT INTO investigation_reports (case_id, findings, witness_summary, recommendations, submitted_by, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$case_id, $findings, $witness_summary, $recommendations, $submitted_by, $status]);
        
        // If status is submitted, update case status to sp_review_from_io
        if ($status === 'submitted') {
            $stmt = $pdo->prepare('UPDATE cases SET status = ? WHERE case_id = ?');
            $stmt->execute(['sp_review_from_io', $case_id]);
            
            // Add status entry
            $stmt = $pdo->prepare('INSERT INTO case_status (case_id, status, comments, updated_by) VALUES (?, ?, ?, ?)');
            $stmt->execute([$case_id, 'sp_review_from_io', 'IO submitted investigation report', $submitted_by]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $status === 'submitted' ? 'Report submitted successfully' : 'Draft saved successfully']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']); 
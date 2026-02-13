<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id = $_POST['case_id'] ?? null;
    $new_status = $_POST['status'] ?? null;
    $comments = $_POST['comments'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$case_id || !$new_status || !$user_id) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Missing required fields',
            'debug' => [
                'case_id' => $case_id,
                'new_status' => $new_status,
                'user_id' => $user_id,
                'session' => $_SESSION
            ]
        ]);
        exit;
    }

    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update cases table
        $stmt = $pdo->prepare('UPDATE cases SET status = ?, updated_at = NOW() WHERE case_id = ?');
        $stmt->execute([$new_status, $case_id]);
        
        // Add status entry to case_status table
        $stmt = $pdo->prepare('INSERT INTO case_status (case_id, status, comments, updated_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$case_id, $new_status, $comments, $user_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Case status updated successfully']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error: ' . $e->getMessage(),
            'debug' => [
                'case_id' => $case_id,
                'new_status' => $new_status,
                'user_id' => $user_id,
                'session' => $_SESSION
            ]
        ]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']); 
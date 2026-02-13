<?php
require_once 'includes/auth.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$case_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

if (!$case_id || !$action) {
    header('Location: ' . getRoleRedirect($_SESSION['role']));
    exit();
}

try {
    // Get current case status
    $stmt = $pdo->prepare("
        SELECT c.*, 
        (SELECT status FROM case_updates WHERE case_id = c.case_id ORDER BY created_at DESC LIMIT 1) as current_status
        FROM cases c
        WHERE c.case_id = ?
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    if (!$case) {
        throw new Exception('Case not found');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    $new_status = '';
    $comments = '';
    
    // Process action based on role and current status
    switch ($_SESSION['role']) {
        case 'police':
            if ($action === 'forward_sp' && $case['current_status'] === 'pending') {
                $new_status = 'sp_review';
                $comments = 'Case forwarded to SP for review';
            }
            break;
            
        case 'c_section':
            if ($case['current_status'] === 'sp_review') {
                if ($action === 'approve') {
                    $new_status = 'c_section_review';
                    $comments = 'Case approved by C-Section';
                } elseif ($action === 'reject') {
                    $new_status = 'rejected';
                    $comments = 'Case rejected by C-Section';
                }
            }
            break;
            
        case 'collector':
            if ($case['current_status'] === 'c_section_review') {
                if ($action === 'approve') {
                    $new_status = 'collector_approved';
                    $comments = 'Case approved by Collector';
                } elseif ($action === 'reject') {
                    $new_status = 'rejected';
                    $comments = 'Case rejected by Collector';
                }
            }
            break;
    }
    
    if (!$new_status) {
        throw new Exception('Invalid action for current status');
    }
    
    // Update case status
    $stmt = $pdo->prepare("
        UPDATE cases 
        SET status = ?
        WHERE case_id = ?
    ");
    $stmt->execute([$new_status, $case_id]);
    
    // Create case update record
    $stmt = $pdo->prepare("
        INSERT INTO case_updates (case_id, status, comments, updated_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$case_id, $new_status, $comments, $_SESSION['user_id']]);
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect back to case view
    header('Location: view_case.php?id=' . $case_id . '&success=1');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Redirect with error
    header('Location: view_case.php?id=' . $case_id . '&error=' . urlencode($e->getMessage()));
    exit();
} 
<?php
require_once '../includes/auth.php';
requireRole('rdo');
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id = $_POST['case_id'] ?? null;
    $io_username = trim($_POST['io_username'] ?? '');
    $sp_instructions = trim($_POST['sp_instructions'] ?? 'Assigned to IO');
    if ($case_id && $io_username) {
        try {
            // Check if IO user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND role = 'io'");
            $stmt->execute([$io_username]);
            $io_user = $stmt->fetch();
            if (!$io_user) {
                header('Location: ../review_case.php?case_id=' . urlencode($case_id) . '&error=IO+user+not+found');
                exit();
            }
            // Update case
            $stmt = $pdo->prepare("UPDATE cases SET io_username = ?, status = 'io_investigation' WHERE case_id = ?");
            $stmt->execute([$io_username, $case_id]);
            // Insert into case_status
            $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by) VALUES (?, 'io_investigation', ?, ?)");
            $stmt->execute([$case_id, $sp_instructions, $_SESSION['user_id']]);
            header('Location: ../review_case.php?case_id=' . urlencode($case_id) . '&success=Case+assigned+to+IO');
            exit();
        } catch (PDOException $e) {
            header('Location: ../review_case.php?case_id=' . urlencode($case_id) . '&error=Database+error');
            exit();
        }
    } else {
        header('Location: ../review_case.php?case_id=' . urlencode($case_id) . '&error=Missing+data');
        exit();
    }
} else {
    header('Location: ../sp_dashboard.php');
    exit();
} 
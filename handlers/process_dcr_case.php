<?php
require_once '../includes/auth.php';
requireRole('dcr');
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dcr_dashboard.php?error=Invalid request method');
    exit();
}

if (!isset($_POST['case_id'], $_POST['verification_status'], $_POST['dcr_remarks'])) {
    header('Location: ../dcr_dashboard.php?error=Missing required fields');
    exit();
}

$case_id = $_POST['case_id'];
$verification_status = $_POST['verification_status'];
$dcr_remarks = $_POST['dcr_remarks'];

try {
    $pdo->beginTransaction();
    // Optionally, store DCR verification info in a separate table if needed
    // For now, just update case_status and cases
    $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by, created_at) VALUES (?, 'sp_review', ?, ?, NOW())");
    $stmt->execute([$case_id, $dcr_remarks, $_SESSION['user_id']]);
    $stmt = $pdo->prepare("UPDATE cases SET status = 'sp_review' WHERE case_id = ?");
    $stmt->execute([$case_id]);
    $pdo->commit();
    header('Location: ../dcr_dashboard.php?success=1&case_id=' . urlencode($case_id));
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: ../dcr_dashboard.php?error=' . urlencode($e->getMessage()));
    exit();
} 
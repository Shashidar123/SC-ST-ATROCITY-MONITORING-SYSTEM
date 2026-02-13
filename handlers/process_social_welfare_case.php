<?php
require_once '../includes/auth.php';
requireRole('social_welfare');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../review_social_welfare_case.php?case_id=' . ($_POST['case_id'] ?? 0) . '&error=Invalid request method');
    exit();
}

if (!isset($_POST['case_id']) || !isset($_POST['action'])) {
    header('Location: ../review_social_welfare_case.php?case_id=' . ($_POST['case_id'] ?? 0) . '&error=Missing required fields');
    exit();
}

$case_id = $_POST['case_id'];
$action = $_POST['action'];
$comments = $_POST['comments'] ?? '';
$recommendation = $_POST['recommendation'] ?? null;
$job_allocated = $_POST['job_allocated'] ?? null;

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

try {
    // Start transaction
    $pdo->beginTransaction();

    // Verify case exists and is in correct status
    $stmt = $pdo->prepare("
        SELECT c.case_id, cs.status as current_status 
        FROM cases c
        JOIN case_status cs ON c.case_id = cs.case_id
        WHERE c.case_id = ? AND cs.created_at = (
            SELECT MAX(created_at)
            FROM case_status
            WHERE case_id = c.case_id
        )
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();

    if (!$case) {
        throw new Exception('Case not found');
    }

    if ($case['current_status'] !== 'social_welfare_review') {
        throw new Exception('Case is not in the correct status for this action');
    }

    // Update case status based on action
    if ($action === 'save_recommendation') {
        // Only save the recommendation, do not update status
        if ($recommendation && $job_allocated) {
            $stmt = $pdo->prepare("INSERT INTO recommendations (case_id, recommendation, comments, job_allocated, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$case_id, $recommendation, $comments, $job_allocated, $_SESSION['user_id']]);
        }
        $pdo->commit();
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Recommendation saved successfully.']);
            exit();
        } else {
            header('Location: ../review_social_welfare_case.php?case_id=' . $case_id . '&success=1');
            exit();
        }
    } else if ($action === 'approve' || $action === 'forward_to_c_section') {
        // Require recipient form submission before forwarding
        $stmt = $pdo->prepare("SELECT recipient_id, draft_saved FROM recipients WHERE case_id = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$case_id]);
        $recipient = $stmt->fetch();
        if (!$recipient || (int)$recipient['draft_saved'] === 1) {
            throw new Exception('Recipient form is not submitted. Please fill and submit the recipient form before forwarding.');
        }
        $new_status = 'from_social_welfare';
        $stmt = $pdo->prepare("UPDATE cases SET status = ?, updated_at = NOW() WHERE case_id = ?");
        $stmt->execute([$new_status, $case_id]);
        // Debug log for recommendation insert
        file_put_contents(__DIR__.'/../debug_log.txt', 'SWD RECOMMEND INSERT: case_id=' . $case_id . ', job_allocated=' . $job_allocated . ', recommendation=' . $recommendation . ', comments=' . $comments . "\n", FILE_APPEND);
        // Always insert recommendation when forwarding
        if ($recommendation && $job_allocated) {
            $stmt = $pdo->prepare("INSERT INTO recommendations (case_id, recommendation, comments, job_allocated, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$case_id, $recommendation, $comments, $job_allocated, $_SESSION['user_id']]);
        }
        $stmt = $pdo->prepare(
            "INSERT INTO case_status (
                case_id,
                status,
                comments,
                updated_by,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $case_id,
            $new_status,
            $comments,
            $_SESSION['user_id']
        ]);
        $pdo->commit();
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Case forwarded to C Section successfully.']);
            exit();
        } else {
            header('Location: ../review_social_welfare_case.php?case_id=' . $case_id . '&success=forwarded');
            exit();
        }
    } else {
        $new_status = 'compensation_rejected';
        if ($recommendation && $job_allocated) {
            $stmt = $pdo->prepare("INSERT INTO recommendations (case_id, recommendation, comments, job_allocated, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$case_id, $recommendation, $comments, $job_allocated, $_SESSION['user_id']]);
        }
        $stmt = $pdo->prepare(
            "INSERT INTO case_status (
                case_id,
                status,
                comments,
                updated_by,
                created_at
            ) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $case_id,
            $new_status,
            $comments,
            $_SESSION['user_id']
        ]);
        $pdo->commit();
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Case rejected successfully.']);
            exit();
        } else {
            header('Location: ../review_social_welfare_case.php?case_id=' . $case_id . '&success=rejected');
            exit();
        }
    }

    // Save recommendation in recommendations table
    if ($recommendation) {
        $stmt = $pdo->prepare("INSERT INTO recommendations (case_id, recommendation, comments, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$case_id, $recommendation, $comments, $_SESSION['user_id']]);
    }

    // Log the action for debugging
    error_log("Social Welfare case {$case_id} {$action}d. New status: {$new_status}");

    // Commit transaction
    $pdo->commit();

    // Redirect with success message
    header('Location: ../social_welfare_dashboard.php?success=1');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error processing social welfare case: " . $e->getMessage());
    header('Location: ../review_social_welfare_case.php?case_id=' . ($_POST['case_id'] ?? 0) . '&error=' . urlencode($e->getMessage()));
    exit();
}
?> 
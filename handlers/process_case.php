<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
file_put_contents(__DIR__.'/../debug_log.txt', json_encode($_POST) . "\n", FILE_APPEND);
file_put_contents(__DIR__.'/../debug_log.txt', json_encode($_FILES) . "\n", FILE_APPEND);
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Check if user has required role
$allowed_roles = ['rdo', 'c_section', 'collector', 'social_welfare'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../' . ($_SESSION['role'] === 'rdo' ? 'sp' : $_SESSION['role']) . '_dashboard.php');
    exit;
}

// Get form data
$case_id = $_POST['case_id'] ?? '';
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$comments = $_POST['comments'] ?? '';

// Validate data
if (!$case_id || !$action) {
    $redirect_page = $_SESSION['role'] === 'c_section' ? 'c_section_dashboard.php' : 'sp_dashboard.php';
    header("Location: ../{$redirect_page}?error=" . urlencode('Invalid request data'));
    exit;
}

// Check if request is AJAX
$is_ajax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
);

// Helper to handle file upload (copied from submit_case.php)
function handle_upload($input_name, $case_id, $document_type, $pdo, $user_id) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $_FILES[$input_name];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid($document_type . '_') . "." . $ext;
    $target_path = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $stmt = $pdo->prepare("INSERT INTO case_documents (case_id, document_type, file_path, uploaded_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$case_id, $document_type, $filename, $user_id]);
        return $filename;
    }
    return null;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get current case status
    $stmt = $pdo->prepare("
        SELECT status 
        FROM case_status 
        WHERE case_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$case_id]);
    $current_status = $stmt->fetchColumn();

    file_put_contents(__DIR__.'/../debug_ajax.txt', 'POST: '.print_r($_POST, true).PHP_EOL, FILE_APPEND);
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'FILES: '.print_r($_FILES, true).PHP_EOL, FILE_APPEND);

    switch ($action) {
        case 'forward_to_collector':
            if ($_SESSION['role'] !== 'c_section') {
                throw new Exception('Unauthorized action');
            }
            $collector_remarks = $_POST['collector_remarks'] ?? '';
            if (empty(trim($collector_remarks))) {
                $redirect_page = '../review_c_section_from_swd_case.php?case_id=' . urlencode($case_id) . '&error=' . urlencode('Comments are required to forward the case.');
                header('Location: ' . $redirect_page);
                exit;
            }
            // Handle digital signature upload if present
            if (isset($_FILES['digital_signature']) && $_FILES['digital_signature']['error'] === UPLOAD_ERR_OK) {
                handle_upload('digital_signature', $case_id, 'digital_signature', $pdo, $_SESSION['user_id']);
            }
            // Update case status
            $stmt = $pdo->prepare("
                UPDATE cases SET status = 'collector_review', updated_at = NOW() WHERE case_id = ?
            ");
            $stmt->execute([$case_id]);
            $stmt = $pdo->prepare("
                INSERT INTO case_status (case_id, status, comments, updated_by, created_at)
                VALUES (?, 'collector_review', ?, ?, NOW())
            ");
            $stmt->execute([$case_id, $collector_remarks ?: 'Forwarded to Collector by C Section', $_SESSION['user_id']]);
            $pdo->commit();
            if ($is_ajax) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                header('Location: ../c_section_dashboard.php?success=1&case_id=' . urlencode($case_id));
                exit;
            }
            break;

        case 'forward_to_c_section':
            if ($_SESSION['role'] !== 'rdo') {
                throw new Exception('Unauthorized action');
            }

            // Insert new status
            $stmt = $pdo->prepare("
                INSERT INTO case_status (
                    case_id,
                    status,
                    comments,
                    updated_by,
                    created_at
                ) VALUES (?, 'c_section_review', ?, ?, NOW())
            ");

            $stmt->execute([
                $case_id,
                $comments,
                $_SESSION['user_id']
            ]);

            $pdo->commit();
            if ($is_ajax) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                header('Location: ../sp_dashboard.php?success=1&case_id=' . urlencode($case_id));
                exit;
            }
            break;

        case 'forward_to_social_welfare':
            // Allow C Section to forward to Social Welfare
            if ($_SESSION['role'] !== 'c_section') {
                throw new Exception('Unauthorized action');
            }
            $remarks = $_POST['collector_remarks'] ?? '';
            // Handle digital signature upload if present
            if (isset($_FILES['digital_signature']) && $_FILES['digital_signature']['error'] === UPLOAD_ERR_OK) {
                handle_upload('digital_signature', $case_id, 'digital_signature', $pdo, $_SESSION['user_id']);
            }
            // Update case status in cases table
            $stmt = $pdo->prepare("
                UPDATE cases SET status = 'social_welfare_review', updated_at = NOW() WHERE case_id = ?
            ");
            $stmt->execute([$case_id]);
            // Insert new status in case_status table
            $stmt = $pdo->prepare("
                INSERT INTO case_status (case_id, status, comments, updated_by, created_at)
                VALUES (?, 'social_welfare_review', ?, ?, NOW())
            ");
            $stmt->execute([$case_id, $remarks ?: 'Forwarded to Social Welfare by C Section', $_SESSION['user_id']]);
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
            break;

        case 'collector_approve':
            if ($_SESSION['role'] !== 'collector') {
                throw new Exception('Unauthorized action');
            }
            $collector_comments = $_POST['collector_comments'] ?? '';
            $stmt = $pdo->prepare("UPDATE cases SET status = 'collector_approved', updated_at = NOW() WHERE case_id = ?");
            $stmt->execute([$case_id]);
            $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by, created_at) VALUES (?, 'collector_approved', ?, ?, NOW())");
            $stmt->execute([$case_id, $collector_comments, $_SESSION['user_id']]);
            $pdo->commit();
            header('Location: ../collector_dashboard.php?success=Case approved by Collector');
            exit;
            break;
        case 'collector_reverify':
            if ($_SESSION['role'] !== 'collector') {
                throw new Exception('Unauthorized action');
            }
            $collector_comments = $_POST['collector_comments'] ?? '';
            $stmt = $pdo->prepare("UPDATE cases SET status = 'collector_reverify', updated_at = NOW() WHERE case_id = ?");
            $stmt->execute([$case_id]);
            $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by, created_at) VALUES (?, 'collector_reverify', ?, ?, NOW())");
            $stmt->execute([$case_id, $collector_comments, $_SESSION['user_id']]);
            $pdo->commit();
            header('Location: ../collector_dashboard.php?success=Case sent for re-verification');
            exit;
            break;
        case 'collector_reject':
            if ($_SESSION['role'] !== 'collector') {
                throw new Exception('Unauthorized action');
            }
            $collector_comments = $_POST['collector_comments'] ?? '';
            $stmt = $pdo->prepare("UPDATE cases SET status = 'collector_rejected', updated_at = NOW() WHERE case_id = ?");
            $stmt->execute([$case_id]);
            $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by, created_at) VALUES (?, 'collector_rejected', ?, ?, NOW())");
            $stmt->execute([$case_id, $collector_comments, $_SESSION['user_id']]);
            $pdo->commit();
            header('Location: ../collector_dashboard.php?success=Case rejected by Collector');
            exit;
            break;
        case 'collector_allot':
            if ($_SESSION['role'] !== 'collector') {
                throw new Exception('Unauthorized action');
            }
            $collector_comments = $_POST['collector_comments'] ?? '';
            $stmt = $pdo->prepare("UPDATE cases SET status = 'collector_allotted', updated_at = NOW() WHERE case_id = ?");
            $stmt->execute([$case_id]);
            $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by, created_at) VALUES (?, 'collector_allotted', ?, ?, NOW())");
            $stmt->execute([$case_id, $collector_comments, $_SESSION['user_id']]);
            $pdo->commit();
            header('Location: ../collector_dashboard.php?success=Job allotted by Collector');
            exit;
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    file_put_contents(__DIR__.'/../debug_ajax.txt', 'ERROR: '.$e->getMessage().PHP_EOL, FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
} 
<?php
require_once '../includes/auth.php';
requireRole('police');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../police.php');
    exit;
}

// Helper to handle file upload
function handle_upload($input_name, $case_id, $document_type, $pdo, $user_id, $allow_multiple = false) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $files = $allow_multiple ? $_FILES[$input_name] : [
        'name' => [$_FILES[$input_name]['name']],
        'tmp_name' => [$_FILES[$input_name]['tmp_name']],
        'error' => [$_FILES[$input_name]['error']],
        'size' => [$_FILES[$input_name]['size']]
    ];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK && $files['size'][$i] > 0) {
            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = uniqid($document_type . '_') . "." . $ext;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                $stmt = $pdo->prepare("INSERT INTO case_documents (case_id, document_type, file_path, uploaded_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$case_id, $document_type, $filename, $user_id]);
            }
        }
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert into cases table (all new fields)
    $stmt = $pdo->prepare("
        INSERT INTO cases (
            victim_name, victim_age, victim_gender, victim_caste, victim_address, victim_contact, victim_aadhaar,
            fir_number, fir_date, police_station, victim_statement, medical_report, case_sections, investigating_officer, forward_to_sp,
            case_type, filed_by, created_at, priority, assigned_officer, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['victim_name'],
        $_POST['victim_age'],
        $_POST['victim_gender'],
        $_POST['victim_caste'],
        $_POST['victim_address'],
        $_POST['victim_contact'],
        $_POST['victim_aadhaar'],
        $_POST['fir_number'],
        $_POST['fir_date'],
        $_POST['police_station'],
        $_POST['victim_statement'],
        $_POST['medical_report'],
        $_POST['case_sections'],
        $_POST['investigating_officer'],
        isset($_POST['forward_to_sp']) ? 1 : 0,
        isset($_POST['victim_caste']) ? strtoupper($_POST['victim_caste']) : '', // for case_type
        $_SESSION['user_id'],
        $_POST['priority'],
        $_POST['assigned_officer'],
        'dcr_review'
    ]);
    $case_id = $pdo->lastInsertId();

    // Handle file uploads
    if (isset($_FILES['fir_upload'])) {
        handle_upload('fir_upload', $case_id, 'fir', $pdo, $_SESSION['user_id']);
    }
    if (isset($_FILES['medical_upload'])) {
        handle_upload('medical_upload', $case_id, 'medical', $pdo, $_SESSION['user_id']);
    }
    if (isset($_FILES['statement_media'])) {
        handle_upload('statement_media', $case_id, 'statement', $pdo, $_SESSION['user_id']);
    }
    if (isset($_FILES['evidence_upload'])) {
        handle_upload('evidence_upload', $case_id, 'evidence', $pdo, $_SESSION['user_id'], true);
    }

    // Always insert initial status as dcr_review
    $stmt = $pdo->prepare("
        INSERT INTO case_status (
            case_id,
            status,
            updated_by,
            created_at
        ) VALUES (?, 'dcr_review', ?, NOW())
    ");
    $stmt->execute([$case_id, $_SESSION['user_id']]);

    // Commit transaction
    $pdo->commit();

    // Set a session flash message for success
    session_start();
    $_SESSION['case_success'] = 'Case filed and forwarded to DCR successfully!';

    // Redirect back to police.php
    header('Location: ../police.php');
    exit;

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log error and redirect with error message
    error_log("Error submitting case: " . $e->getMessage());
    header('Location: ../police.php?error=' . urlencode('Failed to submit case. Please try again.'));
    exit;
} 
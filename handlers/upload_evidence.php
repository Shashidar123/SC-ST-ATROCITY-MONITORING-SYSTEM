<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_id = $_POST['case_id'] ?? null;
    $document_type = $_POST['document_type'] ?? null;
    $uploaded_by = $_SESSION['user_id'] ?? null;
    
    if (!$case_id || !$document_type || !$uploaded_by || !isset($_FILES['evidence_file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        $target_dir = '../uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file = $_FILES['evidence_file'];
        $file_name = basename($file['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = $document_type . '_' . uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $new_file_name;
        
        // Validate file size (10MB max)
        if ($file['size'] > 10 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'File size too large. Maximum 10MB allowed.']);
            exit;
        }
        
        // Validate file type based on document_type
        $allowed_types = [
            'medical' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
            'cctv' => ['mp4', 'avi', 'mov', 'mkv', 'wmv'],
            'photo' => ['jpg', 'jpeg', 'png', 'gif'],
            'evidence' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'mp4', 'avi']
        ];
        
        if (!isset($allowed_types[$document_type]) || !in_array(strtolower($file_ext), $allowed_types[$document_type])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type for ' . $document_type . ' documents']);
            exit;
        }
        
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Insert into case_documents table
            $stmt = $pdo->prepare('INSERT INTO case_documents (case_id, document_type, file_path, uploaded_by) VALUES (?, ?, ?, ?)');
            $stmt->execute([$case_id, $document_type, $new_file_name, $uploaded_by]);
            
            echo json_encode([
                'success' => true, 
                'message' => ucfirst($document_type) . ' uploaded successfully',
                'file' => $new_file_name
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'File upload failed']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']); 
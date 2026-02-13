<?php
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['case_id'])) {
    echo json_encode(['success' => false, 'message' => 'Case ID is required']);
    exit;
}

$case_id = intval($_GET['case_id']);

// Get recipient data
$stmt = $conn->prepare("SELECT * FROM recipients WHERE case_id = ?");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $recipient_data = $result->fetch_assoc();
    $recipient_id = $recipient_data['recipient_id'];
    
    // Get document data
    $stmt = $conn->prepare("SELECT document_type, file_path FROM recipient_documents WHERE recipient_id = ?");
    $stmt->bind_param("i", $recipient_id);
    $stmt->execute();
    $doc_result = $stmt->get_result();
    
    $documents = [];
    while ($doc = $doc_result->fetch_assoc()) {
        $documents[] = $doc;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $recipient_data,
        'documents' => $documents
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No data found']);
}
?>
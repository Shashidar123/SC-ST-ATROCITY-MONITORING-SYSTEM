<?php
require_once 'includes/db_connect.php';

if (!isset($_GET['case_id'])) {
    echo "Case ID is required";
    exit;
}

$case_id = intval($_GET['case_id']);

// Get case details
$stmt = $conn->prepare("SELECT * FROM cases WHERE case_id = ?");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$case_result = $stmt->get_result();

if ($case_result->num_rows == 0) {
    echo "Case not found";
    exit;
}

$case_data = $case_result->fetch_assoc();

// Get recipient details
$stmt = $conn->prepare("SELECT * FROM recipients WHERE case_id = ?");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$recipient_result = $stmt->get_result();

if ($recipient_result->num_rows == 0) {
    echo "Recipient details not found";
    exit;
}

$recipient_data = $recipient_result->fetch_assoc();
$recipient_id = $recipient_data['recipient_id'];

// Get document details
$stmt = $conn->prepare("SELECT * FROM recipient_documents WHERE recipient_id = ?");
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$documents_result = $stmt->get_result();

$documents = [];
while ($doc = $documents_result->fetch_assoc()) {
    $documents[$doc['document_type']] = $doc;
}

// Include the HTML template
include 'formDetails.php';
?>
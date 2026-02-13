<?php
session_start();
require_once 'includes/db.php';

// Function to validate input
function validate_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to handle file upload
function upload_document($file, $document_type, $recipient_id) {
    $target_dir = "uploads/recipient_documents/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = $document_type . "_" . uniqid() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if file is valid
    $allowed_extensions = array("jpg", "jpeg", "png", "pdf");
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO recipient_documents (recipient_id, document_type, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$recipient_id, $document_type, $target_file]);
        return true;
    } else {
        return false;
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_draft = isset($_POST['save_draft']) ? 1 : 0;
    $case_id = validate_input($_POST['case_id']);

    // Helper to support old/new field names from the Social Welfare form
    $getField = function($keys, $default = '') {
        foreach ((array)$keys as $key) {
            if (isset($_POST[$key]) && $_POST[$key] !== '') {
                return validate_input($_POST[$key]);
            }
        }
        return $default;
    };
    
    // Recipient information (supporting both snake_case and camelCase)
    $recipient_name = $getField(['recipient_name', 'recipientName']);
    $relationship = $getField('relationship');
    $age = $getField(['recipient_age', 'recipientAge']);
    $gender = $getField(['recipient_gender', 'recipientGender']);
    $address = $getField(['recipient_address', 'address']);
    $contact = $getField(['recipient_contact', 'contactNumber']);
    
    // Compensation details
    $education = $getField(['education', 'educationQualification']);
    $job = $getField(['job_assigned', 'jobAssigned']);
    $bank_name = $getField(['bank_name', 'bankName']);
    $account_number = $getField(['account_number', 'accountNumber']);
    $ifsc = $getField(['ifsc_code', 'ifscCode']);

    // Normalize compensation to a numeric amount (stored as DECIMAL)
    $raw_comp = $getField(['compensation_amount', 'compensationAmount'], null);
    $normalizeComp = function($val) {
        if ($val === null || $val === '') {
            return null;
        }
        // If it's already numeric, return as-is
        if (is_numeric($val)) {
            return $val;
        }
        $lower = strtolower($val);
        if (strpos($lower, 'fully') !== false) {
            return 825000;
        }
        if (strpos($lower, 'partial') !== false) {
            return 412500;
        }
        if (strpos($lower, 'not assigned') !== false) {
            return 0;
        }
        // Try to extract digits (e.g., "₹4,12,500")
        $digits = preg_replace('/[^0-9\.]/', '', $val);
        return $digits !== '' ? $digits : null;
    };
    $compensation = $normalizeComp($raw_comp);
    
    // Check if this is an update to an existing draft
    $stmt = $pdo->prepare("SELECT recipient_id FROM recipients WHERE case_id = ?");
    $stmt->execute([$case_id]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Update existing record
        $recipient_id = $result['recipient_id'];
        
        $stmt = $pdo->prepare("UPDATE recipients SET 
            recipient_name = ?, relationship_to_victim = ?, age = ?, 
            gender = ?, address = ?, contact_number = ?, 
            education_qualification = ?, job_assigned = ?, 
            bank_name = ?, account_number = ?, ifsc_code = ?, 
            compensation_amount = ?, draft_saved = ?, 
            updated_at = CURRENT_TIMESTAMP 
            WHERE recipient_id = ?");
        
        $stmt->execute([
            $recipient_name, $relationship, $age, 
            $gender, $address, $contact, 
            $education, $job, 
            $bank_name, $account_number, $ifsc, 
            $compensation, $is_draft, 
            $recipient_id
        ]);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("INSERT INTO recipients 
            (case_id, recipient_name, relationship_to_victim, age, gender, address, contact_number, 
            education_qualification, job_assigned, bank_name, account_number, ifsc_code, 
            compensation_amount, draft_saved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $case_id, $recipient_name, $relationship, $age, 
            $gender, $address, $contact, 
            $education, $job, 
            $bank_name, $account_number, $ifsc, 
            $compensation, $is_draft
        ]);
        
        $recipient_id = $pdo->lastInsertId();
    }
    
    // Handle document uploads
    // Map Social Welfare form upload field names to our document types
    $document_map = [
        'firCopy' => 'fir_copy',
        'deathCertificate' => 'death_certificate',
        'aadharCard' => 'aadhar_card',
        'socialStatusReport' => 'social_status',
        'chargeSheet' => 'charge_sheet',
        'postmortemReport' => 'postmortem',
        'legalHeirCertificate' => 'legal_heir',
        'casteCertificate' => 'caste_certificate',
        'appointmentOrder' => 'appointment_order'
    ];

    foreach ($document_map as $field_name => $doc_type) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] == 0) {
            upload_document($_FILES[$field_name], $doc_type, $recipient_id);
        }
    }
    
    // Update case status if form is submitted (not draft)
    if (!$is_draft) {
        $stmt = $pdo->prepare("UPDATE cases SET status = 'from_social_welfare' WHERE case_id = ?");
        $stmt->execute([$case_id]);
        
        // Redirect with success message
        header("Location: social_welfare_dashboard.php?success=1");
        exit();
    } else {
        // Redirect back to form with draft saved message
        header("Location: recpdetails.html?case_id=$case_id&draft=1");
        exit();
    }
} else {
    // Error handling
    header("Location: recpdetails.html?case_id=$case_id&error=1");
    exit();
}
?>
<?php
// This code should be added to the page where cases are displayed
// Assuming there's a loop that displays cases, add this inside that loop

// Check if the case is in the appropriate status for recipient form
if ($case['status'] == 'collector_approved' || $case['status'] == 'collector_allotted') {
    echo '<div class="action-buttons">';
    
    // Check if recipient data exists for this case
    $stmt = $conn->prepare("SELECT recipient_id FROM recipients WHERE case_id = ? AND draft_saved = 0");
    $stmt->bind_param("i", $case['case_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Recipient data exists, show View Details button
        echo '<a href="view_recipient_details.php?case_id=' . $case['case_id'] . '" class="btn btn-info">View Details</a>';
    } else {
        // No recipient data, show Fill Form button
        echo '<a href="recpdetails.html?case_id=' . $case['case_id'] . '" class="btn btn-primary">Fill Recipient Form</a>';
        
        // Check if there's a draft
        $stmt = $conn->prepare("SELECT recipient_id FROM recipients WHERE case_id = ? AND draft_saved = 1");
        $stmt->bind_param("i", $case['case_id']);
        $stmt->execute();
        $draft_result = $stmt->get_result();
        
        if ($draft_result->num_rows > 0) {
            echo '<span class="draft-badge">Draft Saved</span>';
        }
    }
    
    echo '</div>';
}
?>
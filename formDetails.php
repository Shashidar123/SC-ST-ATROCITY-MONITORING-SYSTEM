<?php
// This file is included from view_recipient_details.php
// Variables available: $case_data, $recipient_data, $documents

// Function to display document link
function displayDocument($documents, $type, $label) {
    echo '<div class="detail-item">';
    echo '<div class="detail-label">' . $label . '</div>';
    echo '<ul class="document-list">';
    
    if (isset($documents[$type])) {
        $doc = $documents[$type];
        $filename = basename($doc['file_path']);
        $icon_class = (pathinfo($filename, PATHINFO_EXTENSION) == 'pdf') ? 'fa-file-pdf' : 'fa-file-image';
        
        echo '<li class="document-item">';
        echo '<i class="fas ' . $icon_class . ' document-icon"></i>';
        echo '<span class="document-name">' . $filename . '</span>';
        echo '<a href="' . $doc['file_path'] . '" class="document-action" target="_blank">View</a>';
        echo '</li>';
    } else {
        echo '<li class="document-item">';
        echo '<span class="document-name empty-value">Not provided</span>';
        echo '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details | SC/ST Victim Compensation</title>
    <style>
        /* Keep the existing CSS styles */
        /* ... */
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <div class="badge">SW</div>
            <div>
                <h1>Social Welfare Department</h1>
                <p>SC/ST Victim Compensation Portal</p>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Case Header -->
        <div class="case-header">
            <h2>Compensation Application Details</h2>
            <div class="case-id">App ID: <?php echo htmlspecialchars($case_data['case_id']); ?></div>
        </div>

        <!-- Victim Information Section -->
        <div class="details-section">
            <h3 class="section-title">
                <i class="fas fa-user"></i>
                Victim Information
            </h3>
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Full Name of Victim</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case_data['victim_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Age at time of incident</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case_data['victim_age']); ?> years</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value"><?php echo htmlspecialchars(ucfirst($case_data['victim_gender'])); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Date of Incident</div>
                    <div class="detail-value"><?php echo htmlspecialchars(date('d/m/Y', strtotime($case_data['incident_date']))); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Incident Details</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case_data['incident_description']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Recipient Information Section -->
        <div class="details-section">
            <h3 class="section-title">
                <i class="fas fa-users"></i>
                Recipient Information
            </h3>
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Full Name of Recipient</div>
                    <div class="detail-value"><?php echo htmlspecialchars($recipient_data['recipient_name']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Relationship to Victim</div>
                    <div class="detail-value"><?php echo htmlspecialchars($recipient_data['relationship_to_victim']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Age</div>
                    <div class="detail-value"><?php echo htmlspecialchars($recipient_data['age']); ?> years</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Gender</div>
                    <div class="detail-value"><?php echo htmlspecialchars(ucfirst($recipient_data['gender'])); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Full Address</div>
                    <div class="detail-value"><?php echo htmlspecialchars($recipient_data['address']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Contact Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($recipient_data['contact_number']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Document Upload Section -->
        <div class="details-section">
            <h3 class="section-title">
                <i class="fas fa-file"></i>
                Submitted Documents
            </h3>
            <div class="details-grid">
                <?php 
                displayDocument($documents, 'fir_copy', 'FIR Copy');
                displayDocument($documents, 'death_certificate', 'Death Certificate');
                displayDocument($documents, 'aadhar_card', 'Aadhar Card');
                displayDocument($documents, 'social_status', 'Social Status Report (issued by RDO)');
                displayDocument($documents, 'charge_sheet', 'Charge Sheet');
                displayDocument($documents, 'postmortem', 'Postmortem Report');
                displayDocument($documents, 'legal_heir', 'Legal Heir Certificate (issued by RDO)');
                displayDocument($documents, 'caste_certificate', 'Caste Certificate');
                ?>
            </div>
        </div>
        
        <!-- Compensation Details Section -->
        <div class="details-section">
            <h3 class="section-title">
                <i class="fas fa-money-bill-wave"></i>
                Compensation & Rehabilitation Details
            </h3>
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Education Qualification</div>
                    <div class="detail-value"><?php echo !empty($recipient_data['education_qualification']) ? htmlspecialchars($recipient_data['education_qualification']) : '<span class="empty-value">Not provided</span>'; ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Job Assigned (if any)</div>
                    <div class="detail-value"><?php echo !empty($recipient_data['job_assigned']) ? htmlspecialchars($recipient_data['job_assigned']) : '<span class="empty-value">Not Assigned</span>'; ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Bank Name</div>
                    <div class="detail-value"><?php echo !empty($recipient_data['bank_name']) ? htmlspecialchars($recipient_data['bank_name']) : '<span class="empty-value">Not provided</span>'; ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Bank Account Number</div>
                    <div class="detail-value"><?php echo !empty($recipient_data['account_number']) ? htmlspecialchars($recipient_data['account_number']) : '<span class="empty-value">Not provided</span>'; ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">IFSC Code</div>
                    <div class="detail-value"><?php echo !empty($recipient_data['ifsc_code']) ? htmlspecialchars($recipient_data['ifsc_code']) : '<span class="empty-value">Not provided</span>'; ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Compensation Amount (₹)</div>
                    <div class="detail-value"><?php echo !empty($recipient_data['compensation_amount']) ? htmlspecialchars($recipient_data['compensation_amount']) : '<span class="empty-value">Not Assigned</span>'; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Back Button -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Back to Applications
            </button>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Social Welfare Department © <?php echo date('Y'); ?> | SC/ST Victim Compensation Portal</p>
    </div>

    <script>
        // Functionality for document viewing
        document.querySelectorAll('.document-action').forEach(action => {
            action.addEventListener('click', function(e) {
                // This will now open the actual document since we're providing real links
            });
        });
    </script>
</body>
</html>
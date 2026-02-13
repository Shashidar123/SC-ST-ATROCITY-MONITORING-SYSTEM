<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/auth.php';
requireRole('social_welfare');
require_once 'includes/db.php';

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
if (!$case_id) {
    header('Location: social_welfare_dashboard.php?error=No case selected');
    exit();
}

// Fetch case details
$stmt = $pdo->prepare("SELECT c.*, u.username as filed_by_name FROM cases c JOIN users u ON c.filed_by = u.id WHERE c.case_id = ?");
$stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$case) {
    header('Location: social_welfare_dashboard.php?error=Case not found');
    exit();
    }   

// Fetch all documents for this case
    $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at DESC");
    $stmt->execute([$case_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch status history
$stmt = $pdo->prepare("SELECT cs.*, u.username as updated_by_name FROM case_status cs JOIN users u ON cs.updated_by = u.id WHERE cs.case_id = ? ORDER BY cs.created_at ASC");
    $stmt->execute([$case_id]);
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest investigation report
$stmt = $pdo->prepare("SELECT * FROM investigation_reports WHERE case_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$case_id]);
$investigation_report = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch latest recommendation (if any)
$stmt = $pdo->prepare("SELECT * FROM recommendations WHERE case_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$case_id]);
$recommendation = $stmt->fetch(PDO::FETCH_ASSOC);

function get_documents($documents, $type) {
    return array_filter($documents, function($doc) use ($type) {
        return $doc['document_type'] === $type;
    });
}
function get_first_doc_path($documents, $type) {
    foreach ($documents as $doc) {
        if ($doc['document_type'] === $type) {
            return 'uploads/' . $doc['file_path'];
        }
    }
    return '';
}

$successMsg = '';
$errorMsg = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == '1') {
        $successMsg = 'Recommendation saved successfully.';
    } elseif ($_GET['success'] == 'forwarded') {
        $successMsg = 'Case forwarded to C Section successfully.';
    }
}
if (isset($_GET['error'])) {
    $errorMsg = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Welfare | SC/ST Atrocity Case Review</title>
    <style>
        :root {
            --swd-green: #2e7d32;
            --swd-dark-green: #1b5e20;
            --swd-light: #e8f5e9;
            --swd-white: #ffffff;
            --swd-dark: #333333;
            --swd-gray: #e0e0e0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--swd-light); }
        .header { background: linear-gradient(135deg, var(--swd-dark-green), var(--swd-green)); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .header-title { display: flex; align-items: center; gap: 1rem; }
        .badge { width: 50px; height: 50px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--swd-green); font-weight: bold; font-size: 1.2rem; border: 2px solid gold; }
        .header-actions button { background-color: #d32f2f; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
        .container { display: flex; min-height: calc(100vh - 82px); }
        .sidebar { width: 250px; background-color: white; padding: 1.5rem; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-menu { list-style: none; margin-top: 2rem; }
        .sidebar-menu li { margin-bottom: 1rem; }
        .sidebar-menu a { display: flex; align-items: center; gap: 0.8rem; color: var(--swd-dark); text-decoration: none; padding: 0.8rem; border-radius: 4px; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--swd-light); color: var(--swd-green); }
        .main-content { flex: 1; padding: 2rem; }
        .case-header { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .case-id { background-color: var(--swd-green); color: white; padding: 0.5rem 1rem; border-radius: 4px; }
        .case-section { background-color: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .section-title { color: var(--swd-green); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--swd-gray); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: var(--swd-light); padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: var(--swd-dark); }
        .detail-value { flex: 1; }
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .evidence-item { border: 1px solid var(--swd-gray); border-radius: 4px; overflow: hidden; }
        .evidence-img { width: 100%; height: 150px; object-fit: cover; }
        .evidence-doc { width: 100%; height: 150px; background-color: var(--swd-light); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--swd-green); }
        .evidence-caption { padding: 0.5rem; text-align: center; background-color: var(--swd-gray); }
        .recommendation-section { margin-top: 2rem; background: #e8f5e9; border-left: 4px solid #2e7d32; border-radius: 8px; padding: 1.5rem; }
        .recommendation-title { color: #2e7d32; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group select, .form-group input, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid var(--swd-gray); border-radius: 4px; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .action-buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-success { background-color: #2e7d32; color: white; }
        .btn-success:hover { background-color: #1b5e20; }
        .btn-danger { background-color: #d32f2f; color: white; }
        .btn-danger:hover { background-color: #b71c1c; }
        .status-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background-color: #fff3e0; color: #e65100; }
        .status-active { background-color: #e3f2fd; color: #1565c0; }
        .status-completed { background-color: #e8f5e9; color: var(--swd-green); }
        @media (max-width: 768px) { .container { flex-direction: column; } .sidebar { width: 100%; } .detail-grid { grid-template-columns: 1fr; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <div class="badge">SWD</div>
            <div>
                <h1>Social Welfare Dept</h1>
                <p>SC/ST Atrocity Case Management System</p>
            </div>
        </div>
        <div class="header-actions">
            <form method="post" action="logout.php"><button><i class="fas fa-sign-out-alt"></i> Logout</button></form>
        </div>
    </header>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-profile">
                <h3>Social Welfare</h3>
                <p>District: <?php echo htmlspecialchars($case['district'] ?? '-'); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="social_welfare_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="recpdetails.html?case_id=<?php echo urlencode($case_id); ?>" target="_blank"><i class="fas fa-file-alt"></i> Recipient Form</a></li>
            </ul>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Case Header -->
            <div class="case-header">
                <h2>Case Review & Recommendation</h2>
                <div class="case-id">Case ID: SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></div>
            </div>
            <!-- Case Information -->
            <div class="case-section">
                <h3 class="section-title">Case Summary</h3>
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">FIR Number:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['fir_number']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Police Station:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['police_station']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">SP Remarks:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['sp_instructions'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Current Status:</div>
                            <div class="detail-value">
                                <span class="status-badge status-active">Pending Social Welfare Review</span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Hearing Date:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['hearing_date'] ?? '-'); ?></div>
                    </div>
                    </div>
                </div>
            </div>
            <!-- Victim Details -->
            <div class="case-section">
                <h3 class="section-title">Victim Details</h3>
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Full Name:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_name']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Caste:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_caste']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Bank Details:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_bank_details'] ?? '-'); ?></div>
                    </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Injury Severity:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['injury_severity'] ?? '-'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Economic Loss:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['economic_loss'] ?? '-'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Evidence Section -->
            <div class="case-section">
                <h3 class="section-title">Evidence & Documents</h3>
                <div class="evidence-grid">
                    <?php $has_evidence = false; foreach ($documents as $doc) { if (!empty($doc['file_path']) && file_exists('uploads/' . $doc['file_path'])) { $has_evidence = true; ?>
                        <div class="evidence-item">
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" class="evidence-img" alt="Evidence">
                            <?php elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])): ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="evidence-doc"><i class="fas fa-file-pdf"></i></a>
                            <?php else: ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="evidence-doc"><i class="fas fa-file-alt"></i></a>
                            <?php endif; ?>
                            <div class="evidence-caption">
                                <?php echo htmlspecialchars($doc['document_type']); ?><br>
                                Uploaded: <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($doc['created_at']))); ?>
                            </div>
                        </div>
                    <?php }} if (!$has_evidence): ?>
                        <div class="evidence-item"><div class="evidence-caption">No Evidence uploaded.</div></div>
                            <?php endif; ?>
                </div>
            </div>
            <!-- Recommendation Section -->
            <div class="recommendation-section">
                <h3 class="recommendation-title">Recommendation</h3>
                <p style="margin-bottom:1rem;color:#1b5e20;font-weight:600;">
                    Please complete the <a href="recpdetails.html?case_id=<?php echo urlencode($case_id); ?>" target="_blank">Recipient Form</a> before forwarding. Forwarding is blocked until the form is submitted.
                </p>
                <?php if ($recommendation): ?>
                    <div class="form-group">
                        <label>Job Allocation</label>
                        <span class="badge" style="background-color:#2e7d32;color:white;font-size:1rem;padding:0.5em 1em;">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $recommendation['job_allocated'] ?? 'Not set'))); ?>
                        </span>
                    </div>
                    <div class="form-group">
                        <label>Recommendation</label>
                        <span class="badge bg-info text-dark" style="font-size:1rem;padding:0.5em 1em;">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $recommendation['recommendation'] ?? 'Not set'))); ?>
                        </span>
                    </div>
                    <div class="form-group">
                        <label>Comments</label>
                        <div style="background:#f1f8e9;padding:1em;border-radius:6px;min-height:60px;">
                            <?php echo nl2br(htmlspecialchars($recommendation['comments'] ?? 'No comments provided.')); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <form id="recommendationForm" method="post" action="handlers/process_social_welfare_case.php">
                        <input type="hidden" name="case_id" value="<?php echo htmlspecialchars($case_id); ?>">
                        <input type="hidden" name="action" id="actionInput" value="forward_to_c_section">
                        <div class="form-group">
                            <label for="job_allocated">Job Allocation</label>
                            <select name="job_allocated" id="job_allocated" required>
                                <option value="">Select Job</option>
                                <option value="clerk">Clerk</option>
                                <option value="peon">Peon</option>
                                <option value="typist">Typist</option>
                                <option value="driver">Driver</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="recommendation">Recommendation</label>
                            <input type="text" name="recommendation" id="recommendation" required placeholder="Enter recommendation">
                        </div>
                        <div class="form-group">
                            <label for="comments">Comments</label>
                            <textarea name="comments" id="comments" placeholder="Enter any comments..."></textarea>
                        </div>
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-success" id="forwardToCSectionBtn">Forward to C Section</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    window.onload = function() {
        <?php if ($successMsg): ?>
            showSuccessModal("<?php echo $successMsg; ?>");
        <?php elseif ($errorMsg): ?>
            showSuccessModal("<?php echo $errorMsg; ?>");
        <?php endif; ?>
        // Restore button logic for classic form submission
        var forwardBtn = document.getElementById('forwardToCSectionBtn');
        var rejectBtn = document.getElementById('rejectCaseBtn');
        var actionInput = document.getElementById('actionInput');
        var form = document.getElementById('recommendationForm');
        if (forwardBtn && actionInput && form) {
            forwardBtn.onclick = function(e) {
                e.preventDefault();
                actionInput.value = 'forward_to_c_section';
                form.submit();
            };
        }
        if (rejectBtn && actionInput && form) {
            rejectBtn.onclick = function(e) {
                e.preventDefault();
                actionInput.value = 'reject';
                form.submit();
            };
        }
    };
    // Modal for success messages
    function showSuccessModal(message) {
        let modal = document.getElementById('successModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'successModal';
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.left = '0';
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.background = 'rgba(0,0,0,0.3)';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.zIndex = '9999';
            modal.innerHTML = `<div style="background:#fff;border-radius:10px;padding:2em 2.5em;box-shadow:0 4px 24px rgba(0,0,0,0.18);text-align:center;min-width:300px;max-width:90vw;">
                <div style='font-size:2.5em;color:#2e7d32;margin-bottom:0.5em;'><i class="fas fa-check-circle"></i></div>
                <div id='modalMsg' style='font-size:1.2em;margin-bottom:1em;'></div>
                <button onclick='document.getElementById("successModal").remove()' style='background:#2e7d32;color:#fff;border:none;padding:0.7em 2em;border-radius:5px;font-size:1em;cursor:pointer;'>OK</button>
            </div>`;
            document.body.appendChild(modal);
        }
        document.getElementById('modalMsg').innerText = message;
    }
    </script>
</body>
</html> 
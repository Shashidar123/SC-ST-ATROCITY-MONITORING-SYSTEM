<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/auth.php';
requireRole('collector');
require_once 'includes/db.php';

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
if (!$case_id) {
    header('Location: collector_dashboard.php?error=No case selected');
    exit();
}

// Fetch case details
$stmt = $pdo->prepare("SELECT c.*, u.username as filed_by_name FROM cases c JOIN users u ON c.filed_by = u.id WHERE c.case_id = ?");
    $stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$case) {
        header('Location: collector_dashboard.php?error=Case not found');
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

// Fetch latest recommendation (if any)
$stmt = $pdo->prepare("SELECT * FROM recommendations WHERE case_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$case_id]);
$recommendation = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure $latest_collector_action is always defined before use
$collector_statuses = ['collector_approved', 'collector_reverify', 'collector_rejected', 'collector_allotted'];
$latest_collector_action = null;
foreach (array_reverse($status_history) as $status) {
    if (in_array($status['status'], $collector_statuses)) {
        $latest_collector_action = $status;
        break;
    }
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector Office | SC/ST Atrocity Case Review</title>
    <style>
        :root {
            --collector-green: #2e7d32;
            --collector-dark-green: #1b5e20;
            --collector-light: #e8f5e9;
            --collector-white: #ffffff;
            --collector-dark: #333333;
            --collector-gray: #e0e0e0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--collector-light); }
        .header { background: linear-gradient(135deg, var(--collector-dark-green), var(--collector-green)); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .header-title { display: flex; align-items: center; gap: 1rem; }
        .badge { width: 50px; height: 50px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--collector-green); font-weight: bold; font-size: 1.2rem; border: 2px solid gold; }
        .header-actions button { background-color: #d32f2f; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
        .container { display: flex; min-height: calc(100vh - 82px); }
        .sidebar { width: 250px; background-color: white; padding: 1.5rem; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-menu { list-style: none; margin-top: 2rem; }
        .sidebar-menu li { margin-bottom: 1rem; }
        .sidebar-menu a { display: flex; align-items: center; gap: 0.8rem; color: var(--collector-dark); text-decoration: none; padding: 0.8rem; border-radius: 4px; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--collector-light); color: var(--collector-green); }
        .main-content { flex: 1; padding: 2rem; }
        .case-header { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .case-id { background-color: var(--collector-green); color: white; padding: 0.5rem 1rem; border-radius: 4px; }
        .case-section { background-color: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .section-title { color: var(--collector-green); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--collector-gray); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: var(--collector-light); padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: var(--collector-dark); }
        .detail-value { flex: 1; }
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .evidence-item { border: 1px solid var(--collector-gray); border-radius: 4px; overflow: hidden; }
        .evidence-img { width: 100%; height: 150px; object-fit: cover; }
        .evidence-doc { width: 100%; height: 150px; background-color: var(--collector-light); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--collector-green); }
        .evidence-caption { padding: 0.5rem; text-align: center; background-color: var(--collector-gray); }
        .compensation-section { margin-top: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group select, .form-group input, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid var(--collector-gray); border-radius: 4px; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .action-buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background-color: var(--collector-green); color: white; }
        .btn-primary:hover { background-color: var(--collector-dark-green); }
        .btn-secondary { background-color: #0a4a7a; color: white; }
        .btn-secondary:hover { background-color: #08315a; }
        .status-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background-color: #fff3e0; color: #e65100; }
        .status-active { background-color: #e3f2fd; color: #1565c0; }
        .status-completed { background-color: #e8f5e9; color: var(--collector-green); }
        @media (max-width: 768px) { .container { flex-direction: column; } .sidebar { width: 100%; } .detail-grid { grid-template-columns: 1fr; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
            <div class="header-title">
            <div class="badge">CO</div>
            <div>
                <h1>Collector Office</h1>
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
                <h3>Collector Office</h3>
                <p>District: <?php echo htmlspecialchars($case['district'] ?? '-'); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="collector_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
                                <span class="status-badge status-active">Pending Collector Review</span>
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
            <div class="case-section compensation-section">
                <h3 class="section-title">C-Section Recommendation</h3>
                <?php if ($recommendation): ?>
                    <div class="form-group">
                        <label>Job Allocation</label>
                        <span class="badge" style="background-color:#2e7d32;color:white;font-size:1rem;padding:0.5em 1em;">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $recommendation['job_allocated'] ?? 'Not set'))); ?>
                        </span>
                            </div>
                    <div class="form-group">
                        <label>Comments</label>
                        <div style="background:#f1f8e9;padding:1em;border-radius:6px;min-height:60px;">
                            <?php echo nl2br(htmlspecialchars($recommendation['comments'] ?? 'No comments provided.')); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Status History Section -->
            <div class="case-section">
                <h3 class="section-title">Status & Comments History</h3>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Status</th>
                                <th>Comments</th>
                                <th>Updated By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($status_history as $status): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($status['created_at'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status['status'] ?? ''))); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($status['comments'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($status['updated_by_name'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            <!-- Collector Action Section -->
            <div class="case-section">
                <h3 class="section-title">Collector Action</h3>
                <?php if ($latest_collector_action): ?>
                    <div style="background:#e8f5e9;padding:1.5em;border-radius:8px;text-align:center;">
                        <h4 style="color:#2e7d32;margin-bottom:1em;">Decision Already Made</h4>
                        <div style="font-size:1.1em;margin-bottom:0.5em;"><b>Case ID:</b> SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></div>
                        <div style="font-size:1.1em;margin-bottom:0.5em;"><b>Victim Name:</b> <?php echo htmlspecialchars($case['victim_name']); ?></div>
                        <div style="font-size:1.1em;margin-bottom:0.5em;"><b>FIR Number:</b> <?php echo htmlspecialchars($case['fir_number']); ?></div>
                        <div style="font-size:1.1em;margin-bottom:0.5em;"><b>Decision:</b> <span style="color:#1565c0;font-weight:600;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $latest_collector_action['status']))); ?></span></div>
                        <div style="font-size:1.1em;"><b>Remarks:</b> <?php echo nl2br(htmlspecialchars($latest_collector_action['comments'] ?? 'No remarks.')); ?></div>
                    </div>
                <?php else: ?>
                    <form action="handlers/process_case.php" method="post" id="collectorActionForm">
                        <input type="hidden" name="case_id" value="<?php echo $case['case_id']; ?>">
                        <input type="hidden" name="action" id="collectorActionInput" value="">
                        <div class="form-group">
                            <label for="collector_comments">Collector Comments</label>
                            <textarea name="collector_comments" id="collector_comments" class="form-control" required placeholder="Enter your remarks or instructions..."></textarea>
                        </div>
                        <?php if ($recommendation && !empty($recommendation['job_allocated'])): ?>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-primary" onclick="setCollectorAction('collector_allot')">Allot</button>
                                <button type="button" class="btn btn-warning" onclick="setCollectorAction('collector_reverify')">Reverify</button>
                                <button type="button" class="btn btn-danger" onclick="setCollectorAction('collector_reject')">Reject</button>
                            </div>
                        <?php else: ?>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-primary" onclick="setCollectorAction('collector_approve')">Approve</button>
                                <button type="button" class="btn btn-warning" onclick="setCollectorAction('collector_reverify')">Reverify</button>
                                <button type="button" class="btn btn-danger" onclick="setCollectorAction('collector_reject')">Reject</button>
                            </div>
                        <?php endif; ?>
                    </form>
                    <script>
                        function setCollectorAction(action) {
                            document.getElementById('collectorActionInput').value = action;
                            document.getElementById('collectorActionForm').submit();
                        }
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 
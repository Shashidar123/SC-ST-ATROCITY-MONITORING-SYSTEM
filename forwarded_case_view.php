<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/auth.php';
requireRole('c_section');
require_once 'includes/db.php';

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
if (!$case_id) {
    header('Location: c_section_dashboard.php?error=No case selected');
    exit();
}

// Fetch case details
$stmt = $pdo->prepare("SELECT c.*, u.username as filed_by_name FROM cases c JOIN users u ON c.filed_by = u.id WHERE c.case_id = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$case) {
    header('Location: c_section_dashboard.php?error=Case not found');
    exit();
}

// Fetch all documents for this case
$stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at DESC");
$stmt->execute([$case_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest recommendation (with job allocation)
$stmt = $pdo->prepare("SELECT * FROM recommendations WHERE case_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$case_id]);
$recommendation = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch status history for audit trail
$stmt = $pdo->prepare("SELECT cs.*, u.username as updated_by_name FROM case_status cs JOIN users u ON cs.updated_by = u.id WHERE cs.case_id = ? ORDER BY cs.created_at ASC");
$stmt->execute([$case_id]);
$status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Find latest collector action in status history
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
    <title>Forwarded Case Details | SC/ST Case Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --c-section-blue: #0a4a7a;
            --c-section-green: #2e7d32;
            --c-section-light: #e9f2f9;
            --c-section-white: #ffffff;
            --c-section-dark: #333333;
            --c-section-gray: #e0e0e0;
        }
        body { background-color: var(--c-section-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { background: linear-gradient(135deg, var(--c-section-blue), var(--c-section-green)); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .header-title { display: flex; align-items: center; gap: 1rem; }
        .badge { background-color: #2e7d32; color: white; font-size: 1rem; padding: 0.3em 1em; border-radius: 16px; font-weight: 600; display: inline-block; }
        .main-content { max-width: 900px; margin: 2rem auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 2rem; }
        .section-title { color: var(--c-section-green); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--c-section-gray); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: var(--c-section-light); padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: var(--c-section-dark); }
        .detail-value { flex: 1; }
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .evidence-item { border: 1px solid var(--c-section-gray); border-radius: 4px; overflow: hidden; }
        .evidence-img { width: 100%; height: 150px; object-fit: cover; }
        .evidence-doc { width: 100%; height: 150px; background-color: var(--c-section-light); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--c-section-green); }
        .evidence-caption { padding: 0.5rem; text-align: center; background-color: var(--c-section-gray); }
        .recommendation-section { margin-top: 2rem; background: #e8f5e9; border-left: 4px solid #2e7d32; border-radius: 8px; padding: 1.5rem; }
        .recommendation-title { color: #2e7d32; margin-bottom: 1rem; }
        .approval-section { margin-top: 2rem; background: #e3f2fd; border-left: 4px solid #1565c0; border-radius: 8px; padding: 1.5rem; }
        .approval-title { color: #1565c0; margin-bottom: 1rem; font-size: 1.2rem; font-weight: 700; }
        .approval-choice { font-size: 1.1rem; font-weight: 600; color: #fff; background: linear-gradient(90deg, #1565c0, #42a5f5); padding: 0.5em 1.5em; border-radius: 20px; display: inline-block; margin-bottom: 0.5em; }
        .readonly-value { background: #f1f1f1; border-radius: 4px; padding: 0.5rem 1rem; color: #333; }
        .btn-back { margin-top: 2rem; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-title">
            <div class="badge">CO</div>
            <div>
                <h1>C-section</h1>
                <p>SC/ST Atrocity Case Management System</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="c_section_dashboard.php" class="btn btn-light">Back to Dashboard</a>
        </div>
    </header>
    <div class="main-content">
        <h2 class="section-title">Forwarded Case Details</h2>
        <div class="detail-grid">
            <div class="detail-card">
                <div class="detail-row">
                    <div class="detail-label">FIR Number:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['fir_number'] ?? ''); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Police Station:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['police_station'] ?? ''); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Complainant:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['victim_name'] ?? ''); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Filed By:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['filed_by_name'] ?? ''); ?></div>
                </div>
            </div>
            <div class="detail-card">
                <div class="detail-row">
                    <div class="detail-label">Case Type:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['case_type'] ?? ''); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Priority:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['priority'] ?? ''); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value"><span class="badge" style="background:#1565c0;">Forwarded to Collector</span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Assigned To:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($case['assigned_officer'] ?? '-'); ?></div>
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
                            <img src="uploads/<?php echo htmlspecialchars($doc['file_path'] ?? ''); ?>" class="evidence-img" alt="Evidence">
                        <?php elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])): ?>
                            <a href="uploads/<?php echo htmlspecialchars($doc['file_path'] ?? ''); ?>" target="_blank" class="evidence-doc"><i class="bi bi-file-earmark-pdf"></i></a>
                        <?php else: ?>
                            <a href="uploads/<?php echo htmlspecialchars($doc['file_path'] ?? ''); ?>" target="_blank" class="evidence-doc"><i class="bi bi-file-earmark"></i></a>
                        <?php endif; ?>
                        <div class="evidence-caption">
                            <?php echo htmlspecialchars($doc['document_type'] ?? ''); ?><br>
                            Uploaded: <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($doc['created_at'] ?? ''))); ?>
                        </div>
                    </div>
                <?php }} if (!$has_evidence): ?>
                    <div class="evidence-item"><div class="evidence-caption">No Evidence uploaded.</div></div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Recommendation Section -->
        <div class="recommendation-section">
            <h3 class="recommendation-title">Social Welfare Recommendation</h3>
            <?php if ($recommendation): ?>
                <div class="form-group" style="margin-bottom: 1em;">
                    <label>Job Allocation</label>
                    <span class="badge" style="background-color:#2e7d32;color:white;font-size:0.95rem;padding:0.3em 0.9em;vertical-align:middle;">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $recommendation['job_allocated'] ?? 'Not set'))); ?>
                    </span>
                </div>
                <div class="form-group" style="margin-bottom: 1em;">
                    <label>Recommendation</label>
                    <div style="font-weight: 500; color: #1565c0; background: #e3f2fd; display: inline-block; padding: 0.4em 1em; border-radius: 16px;">
                        <?php echo htmlspecialchars($recommendation['recommendation'] ?? 'Not set'); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Comments</label>
                    <div style="background:#f1f8e9;padding:0.7em 1em;border-radius:6px;min-height:40px;color:#333;">
                        <?php echo nl2br(htmlspecialchars($recommendation['comments'] ?? 'No comments provided.')); ?>
                    </div>
                </div>
            <?php else: ?>
                <div>No recommendation data available.</div>
            <?php endif; ?>
        </div>
        <!-- Collector's Approval Section -->
        <div class="approval-section">
            <div class="approval-title">Collector's Approval</div>
            <?php if ($latest_collector_action): ?>
                <div class="approval-choice">
                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $latest_collector_action['status']))); ?>
                </div>
                <div style="margin-top:0.5em; color:#333;">
                    <b>Remarks:</b> <?php echo nl2br(htmlspecialchars($latest_collector_action['comments'] ?? 'No remarks.')); ?>
                </div>
            <?php else: ?>
                <div style="color:#888;">No collector approval recorded yet.</div>
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
        <a href="c_section_dashboard.php" class="btn btn-secondary btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
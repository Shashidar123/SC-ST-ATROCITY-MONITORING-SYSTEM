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

// Fetch recipient details forwarded from Social Welfare
$recipient = null;
$recipient_documents = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM recipients WHERE case_id = ? LIMIT 1");
    $stmt->execute([$case_id]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($recipient) {
        $stmt = $pdo->prepare("SELECT document_type, file_path FROM recipient_documents WHERE recipient_id = ?");
        $stmt->execute([$recipient['recipient_id']]);
        $recipient_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Fail silently; sidebar will show fallback
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
function format_inr($amount) {
    if ($amount === null || $amount === '') return '-';
    return '₹' . number_format((float)$amount, 2, '.', ',');
}

// Fetch status bars data
$total_cases = 0;
$pending_cases = 0;
$from_swd_cases = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases");
    $total_cases = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases WHERE status = 'c_section_review'");
    $pending_cases = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases WHERE status = 'from_social_welfare'");
    $from_swd_cases = $stmt->fetchColumn();
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C Section Review (From Social Welfare) | SC/ST Case Management</title>
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
        .badge { width: 50px; height: 50px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--c-section-green); font-weight: bold; font-size: 1.2rem; border: 2px solid gold; }
        .header-actions button { background-color: #d32f2f; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
        .container { display: flex; min-height: calc(100vh - 82px); }
        .sidebar { width: 250px; background-color: white; padding: 1.5rem; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-menu { list-style: none; margin-top: 2rem; }
        .sidebar-menu li { margin-bottom: 1rem; }
        .sidebar-menu a { display: flex; align-items: center; gap: 0.8rem; color: var(--c-section-dark); text-decoration: none; padding: 0.8rem; border-radius: 4px; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--c-section-light); color: var(--c-section-green); }
        .main-content { flex: 1; padding: 2rem; }
        .case-header { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .case-id { background-color: var(--c-section-green); color: white; padding: 0.5rem 1rem; border-radius: 4px; }
        .case-section { background-color: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .section-title { color: var(--c-section-green); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--c-section-gray); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: var(--c-section-light); padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: var(--c-section-dark); }
        .detail-value { flex: 1; }
        .recipient-meta { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; margin-bottom: 0.75rem; }
        .pill-badge { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.75rem; border-radius: 999px; font-weight: 700; font-size: 0.95rem; color: white; }
        .pill-success { background-color: #2e7d32; }
        .pill-info { background-color: #1976d2; }
        .recipient-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .recipient-docs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .recipient-doc-card { border: 1px solid var(--c-section-gray); border-radius: 6px; background: #fdfdfd; padding: 0.75rem; text-align: center; }
        .recipient-doc-card a { text-decoration: none; color: var(--c-section-blue); font-weight: 600; }
        .recipient-doc-icon { font-size: 2.5rem; color: var(--c-section-green); margin-bottom: 0.5rem; display: block; }
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .evidence-item { border: 1px solid var(--c-section-gray); border-radius: 4px; overflow: hidden; }
        .evidence-img { width: 100%; height: 150px; object-fit: cover; }
        .evidence-doc { width: 100%; height: 150px; background-color: var(--c-section-light); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--c-section-green); }
        .evidence-caption { padding: 0.5rem; text-align: center; background-color: var(--c-section-gray); }
        .recommendation-section { margin-top: 2rem; background: #e8f5e9; border-left: 4px solid #2e7d32; border-radius: 8px; padding: 1.5rem; }
        .recommendation-title { color: #2e7d32; margin-bottom: 1rem; }
        .readonly-value { background: #f1f1f1; border-radius: 4px; padding: 0.5rem 1rem; color: #333; }
        .btn-forward { margin-top: 2rem; }
        @media (max-width: 768px) { .container { flex-direction: column; } .sidebar { width: 100%; } .detail-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <div class="badge">CO</div>
            <div>
                <h1>C-section</h1>
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
                <li><a href="c_section_dashboard.php" class="active"><i class="bi bi-tachometer"></i> Dashboard</a></li>
                <li><a href="#recipient-details"><i class="bi bi-person-lines-fill"></i> Recipient Details</a></li>
            </ul>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Case Header -->
            <div class="case-header">
                <h2>Case Review (From Social Welfare)</h2>
                <div class="case-id">Case ID: SC/ST/<?php echo date('Y', strtotime($case['created_at'] ?? '')); ?>/<?php echo str_pad($case['case_id'] ?? '', 4, '0', STR_PAD_LEFT); ?></div>
            </div>
            <!-- Recipient Details (full view) -->
            <div class="case-section" id="recipient-details">
                <h3 class="section-title">Recipient Details (Forwarded by Social Welfare)</h3>
                <div style="color:#2e7d32;font-weight:700;">Compensation allotted by Social Welfare</div>
                <div class="recipient-meta">
                    <?php if ($recipient): ?>
                        <span class="pill-badge pill-success">Recipient record found</span>
                        <span class="pill-badge pill-info">Docs: <?php echo count($recipient_documents); ?></span>
                    <?php else: ?>
                        <span class="badge bg-danger" style="font-size:0.95rem;">No recipient record for this case_id</span>
                        <span style="color:#555;">Re-submit the Social Welfare recipient form for this case to populate data.</span>
                    <?php endif; ?>
                </div>
                <?php if ($recipient): ?>
                    <div class="recipient-grid">
                        <div class="detail-card">
                            <div class="detail-row"><div class="detail-label">Name</div><div class="detail-value"><?php echo htmlspecialchars($recipient['recipient_name'] ?? ''); ?></div></div>
                            <div class="detail-row"><div class="detail-label">Relationship</div><div class="detail-value"><?php echo htmlspecialchars($recipient['relationship_to_victim'] ?? ''); ?></div></div>
                            <div class="detail-row"><div class="detail-label">Age / Gender</div><div class="detail-value"><?php echo htmlspecialchars(($recipient['age'] ?? '-') . ' / ' . ($recipient['gender'] ?? '-')); ?></div></div>
                            <div class="detail-row"><div class="detail-label">Contact</div><div class="detail-value"><?php echo htmlspecialchars($recipient['contact_number'] ?? ''); ?></div></div>
                            <div class="detail-row"><div class="detail-label">Address</div><div class="detail-value"><?php echo htmlspecialchars($recipient['address'] ?? ''); ?></div></div>
                        </div>
                        <div class="detail-card">
                            <div class="detail-row"><div class="detail-label">Education</div><div class="detail-value"><?php echo htmlspecialchars($recipient['education_qualification'] ?? ''); ?></div></div>
                            <div class="detail-row"><div class="detail-label">Job Assigned</div><div class="detail-value"><?php echo htmlspecialchars($recipient['job_assigned'] ?? ''); ?></div></div>
                            <div class="detail-row"><div class="detail-label">Compensation (Social Welfare)</div><div class="detail-value"><?php echo htmlspecialchars(format_inr($recipient['compensation_amount'] ?? null)); ?></div></div>
                            <div class="detail-row">
                                <div class="detail-label">Bank</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($recipient['bank_name'] ?? ''); ?><br>
                                    <?php echo htmlspecialchars($recipient['account_number'] ?? ''); ?><br>
                                    IFSC: <?php echo htmlspecialchars($recipient['ifsc_code'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="recipient-docs-grid">
                        <?php if (!empty($recipient_documents)): ?>
                            <?php foreach ($recipient_documents as $doc): ?>
                                <div class="recipient-doc-card">
                                    <span class="recipient-doc-icon"><i class="bi bi-file-earmark-text"></i></span>
                                    <a href="<?php echo htmlspecialchars($doc['file_path'] ?? '#'); ?>" target="_blank">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type'] ?? 'Document'))); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="recipient-doc-card">No uploaded recipient documents.</div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div>No recipient details forwarded with this case.</div>
                <?php endif; ?>
            </div>
            <!-- Case Information -->
            <div class="case-section">
                <h3 class="section-title">Case Summary</h3>
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
                            <div class="detail-label">SP Remarks:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['sp_instructions'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Complainant:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_name'] ?? ''); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Filed By:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['filed_by_name'] ?? ''); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Case Type:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['case_type'] ?? ''); ?></div>
                        </div>
                    </div>
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
            <!-- C Section Comments Form -->
            <form action="handlers/process_case.php" method="post" class="btn-forward">
                <input type="hidden" name="case_id" value="<?php echo $case['case_id'] ?? ''; ?>">
                <input type="hidden" name="action" value="forward_to_collector">
                <div class="mb-3">
                    <label for="collector_remarks" class="form-label"><b>Your Comments/Verification (required before forwarding):</b></label>
                    <textarea class="form-control" id="collector_remarks" name="collector_remarks" rows="4" required placeholder="Enter your verification, remarks, or summary..."></textarea>
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit"><i class="bi bi-arrow-right-circle"></i> Forward to Collector</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html> 
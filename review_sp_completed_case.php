<?php
require_once 'includes/auth.php';
requireRole('rdo');
require_once 'includes/db.php';

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
if (!$case_id) {
    header('Location: sp_dashboard.php?error=No case selected');
    exit();
}

// Fetch case details
$stmt = $pdo->prepare("SELECT c.*, u.username as filed_by_name FROM cases c JOIN users u ON c.filed_by = u.id WHERE c.case_id = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$case) {
    header('Location: sp_dashboard.php?error=Case not found');
    exit();
}

// Fetch all documents for this case
$stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at ASC");
$stmt->execute([$case_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch status history
$stmt = $pdo->prepare("SELECT cs.*, u.username as updated_by_name FROM case_status cs JOIN users u ON cs.updated_by = u.id WHERE cs.case_id = ? ORDER BY cs.created_at ASC");
$stmt->execute([$case_id]);
$status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Office | Completed Case Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #e9f2f9; }
        .main-content { max-width: 1000px; margin: 2rem auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 2rem; }
        .section-title { color: #0a4a7a; margin-bottom: 1.5rem; border-bottom: 1px solid #e0e0e0; padding-bottom: 0.5rem; }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: #f8f9fa; padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: #333; }
        .detail-value { flex: 1; }
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .evidence-item { border: 1px solid #e0e0e0; border-radius: 4px; overflow: hidden; }
        .evidence-img { width: 100%; height: 150px; object-fit: cover; }
        .evidence-doc { width: 100%; height: 150px; background-color: #e9f2f9; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #0a4a7a; }
        .evidence-caption { padding: 0.5rem; text-align: center; background-color: #e0e0e0; }
        .status-history { margin-top: 2rem; }
        .status-entry { background: #f1f8e9; border-left: 4px solid #0a4a7a; margin-bottom: 1rem; padding: 1rem; border-radius: 6px; }
        .status-label { font-weight: 600; color: #0a4a7a; }
    </style>
</head>
<body>
    <div class="main-content">
        <h2 class="section-title">Completed Case Review</h2>
        <div class="detail-grid">
            <div class="detail-card">
                <div class="detail-row"><div class="detail-label">Case ID:</div><div class="detail-value">SC/ST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></div></div>
                <div class="detail-row"><div class="detail-label">FIR Number:</div><div class="detail-value"><?php echo htmlspecialchars($case['fir_number']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Police Station:</div><div class="detail-value"><?php echo htmlspecialchars($case['police_station']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Filed By:</div><div class="detail-value"><?php echo htmlspecialchars($case['filed_by_name']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Created At:</div><div class="detail-value"><?php echo htmlspecialchars($case['created_at']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Current Status:</div><div class="detail-value"><span class="badge bg-success">Completed (Forwarded to Collector)</span></div></div>
            </div>
            <div class="detail-card">
                <div class="detail-row"><div class="detail-label">Victim Name:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_name']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Caste:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_caste']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Contact:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_contact']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_address']); ?></div></div>
            </div>
        </div>
        <h3 class="section-title">Evidence & Documents</h3>
        <div class="evidence-grid">
            <?php $has_evidence = false; foreach ($documents as $doc) { if (!empty($doc['file_path']) && file_exists('uploads/' . $doc['file_path'])) { $has_evidence = true; ?>
                <div class="evidence-item">
                    <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" class="evidence-img" alt="Evidence">
                    <?php elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])): ?>
                        <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="evidence-doc"><i class="bi bi-file-earmark-pdf"></i></a>
                    <?php else: ?>
                        <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="evidence-doc"><i class="bi bi-file-earmark"></i></a>
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
        <div class="status-history">
            <h3 class="section-title">Case Status History</h3>
            <?php foreach ($status_history as $status): ?>
                <div class="status-entry">
                    <span class="status-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status['status']))); ?></span>
                    <span style="margin-left:1em;color:#333;font-weight:600;">by <?php echo htmlspecialchars($status['updated_by_name']); ?> on <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($status['created_at']))); ?></span>
                    <div style="margin-top:0.5em;background:#f1f8e9;padding:1em;border-radius:6px;min-height:40px;">
                        <?php echo nl2br(htmlspecialchars($status['comments'] ?? '')); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4">
            <a href="sp_dashboard.php#completed-cases" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Completed Cases</a>
        </div>
    </div>
</body>
</html> 
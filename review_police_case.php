<?php
require_once 'includes/auth.php';
requireRole('police');
require_once 'includes/db.php';

$case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
if (!$case_id) {
    header('Location: police.php?error=No case selected');
    exit();
}

// Fetch case details
$stmt = $pdo->prepare("SELECT c.*, u.username as filed_by_name FROM cases c JOIN users u ON c.filed_by = u.id WHERE c.case_id = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$case) {
    header('Location: police.php?error=Case not found');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Case Review | SC/ST Case Management</title>
    <style>
        :root {
            --police-blue: #0a4a7a;
            --police-light: #e9f2f9;
            --police-dark: #333333;
            --police-gray: #e0e0e0;
        }
        body { background-color: var(--police-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { background: linear-gradient(135deg, var(--police-blue), #1976d2); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .header-title { display: flex; align-items: center; gap: 1rem; }
        .badge { width: 50px; height: 50px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--police-blue); font-weight: bold; font-size: 1.2rem; border: 2px solid gold; }
        .main-content { max-width: 900px; margin: 2rem auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 2rem; }
        .case-header { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .case-id { background-color: var(--police-blue); color: white; padding: 0.5rem 1rem; border-radius: 4px; }
        .case-section { background-color: var(--police-light); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .section-title { color: var(--police-blue); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--police-gray); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: #f5f6fa; padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: var(--police-dark); }
        .detail-value { flex: 1; }
        .evidence-list { list-style: none; padding: 0; }
        .evidence-list li { margin-bottom: 0.7em; }
        .evidence-link { color: #1976d2; text-decoration: underline; font-weight: 500; }
        .status-history-block { background: #f1f8e9; border-radius: 8px; padding: 1.2em 1.5em; margin-top: 2em; }
        .status-history-title { color: #0a4a7a; font-size: 1.1em; font-weight: 700; margin-bottom: 1em; }
        .status-history-item { margin-bottom: 1em; padding-bottom: 0.7em; border-bottom: 1px solid #e0e0e0; }
        .status-history-item:last-child { border-bottom: none; }
        .status-label { font-weight: 600; color: #1976d2; }
        .status-meta { color: #555; font-size: 0.95em; margin-bottom: 0.2em; }
        .status-comments { color: #333; font-size: 1em; margin-top: 0.2em; }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-title">
            <div class="badge">POL</div>
            <div>
                <h1>Police Review</h1>
                <p>SC/ST Atrocity Case Management System</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="police.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </header>
    <div class="main-content">
        <div class="case-header">
            <h2>Case Details</h2>
            <div class="case-id">Case ID: SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></div>
        </div>
        <!-- Case Summary -->
        <div class="case-section">
            <h3 class="section-title">Case Summary</h3>
            <div class="detail-grid">
                <div class="detail-card">
                    <?php if (!empty($case['fir_number'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">FIR Number:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['fir_number']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($case['police_station'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Police Station:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['police_station']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <div class="detail-label">Filed By:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['filed_by_name']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Filed Date:</div>
                        <div class="detail-value"><?php echo date('Y-m-d', strtotime($case['created_at'])); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Current Status:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($status_history[0]['status'] ?? '-'); ?></div>
                    </div>
                </div>
                <div class="detail-card">
                    <?php if (!empty($case['victim_name'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Victim Name:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['victim_name']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($case['victim_caste'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Caste:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['victim_caste']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($case['victim_address'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Address:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['victim_address']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($case['victim_contact'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Contact:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['victim_contact']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Incident Description -->
        <?php if (!empty($case['incident_description'])): ?>
        <div class="case-section">
            <h3 class="section-title">Incident Description</h3>
            <div style="background:#f5f6fa;padding:1em;border-radius:6px;">
                <?php echo nl2br(htmlspecialchars($case['incident_description'])); ?>
            </div>
        </div>
        <?php endif; ?>
        <!-- Case Documents & Evidence -->
        <div class="case-section">
            <h3 class="section-title">Case Documents & Evidence</h3>
            <?php if (!empty($documents)): ?>
            <ul class="evidence-list">
                <?php foreach ($documents as $doc): ?>
                <li>
                    <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="evidence-link">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type']))); ?>
                    </a>
                    <span style="color:#888;font-size:0.95em;">(Uploaded: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($doc['created_at']))); ?>)</span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div style="color:#888;">No documents uploaded for this case.</div>
            <?php endif; ?>
        </div>
        <!-- Status History -->
        <div class="status-history-block">
            <div class="status-history-title">Case Status History</div>
            <?php foreach ($status_history as $status): ?>
            <div class="status-history-item">
                <div class="status-label"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status['status']))); ?></div>
                <div class="status-meta">By: <?php echo htmlspecialchars($status['updated_by_name']); ?> on <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($status['created_at']))); ?></div>
                <?php if (!empty($status['comments'])): ?>
                <div class="status-comments">Comments: <?php echo nl2br(htmlspecialchars($status['comments'])); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html> 
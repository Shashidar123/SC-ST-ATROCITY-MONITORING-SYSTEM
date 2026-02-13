<?php
require_once 'includes/auth.php';
requireRole('dcr');
require_once 'includes/db.php';

if (!isset($_GET['case_id'])) {
    header('Location: dcr_dashboard.php?error=No case ID provided');
    exit();
}
$case_id = $_GET['case_id'];

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as filed_by_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.case_id = ?
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$case) {
        throw new Exception('Case not found');
    }
    // Get case documents
    $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at DESC");
    $stmt->execute([$case_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    header('Location: dcr_dashboard.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCR Review Case</title>
    <style>
        :root { --dcr-blue: #1565c0; --dcr-light: #e3f2fd; --dcr-dark-blue: #0d47a1; --dcr-white: #ffffff; --dcr-dark: #333333; --dcr-gray: #e0e0e0; --collector-green: #2e7d32; }
        body { background-color: var(--dcr-light); margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { background: linear-gradient(135deg, var(--dcr-dark-blue), var(--dcr-blue)); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .header-title { display: flex; align-items: center; gap: 1rem; }
        .badge { width: 50px; height: 50px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--dcr-blue); font-weight: bold; font-size: 1.2rem; border: 2px solid gold; }
        .main-content { max-width: 900px; margin: 2rem auto; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 2rem; }
        .section-title { color: var(--dcr-blue); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--dcr-gray); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: var(--dcr-light); padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: var(--dcr-dark); }
        .detail-value { flex: 1; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid var(--dcr-gray); border-radius: 4px; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .action-buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background-color: var(--dcr-blue); color: white; }
        .btn-primary:hover { background-color: var(--dcr-dark-blue); }
        .btn-success { background-color: var(--collector-green); color: white; }
        .btn-success:hover { background-color: #1b5e20; }
        .alert { padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">
            <div class="badge">DCR</div>
            <div>
                <h1>DCR Case Review</h1>
                <p>SC/ST Atrocity Case Management System</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="logout.php" class="btn btn-primary">Logout</a>
        </div>
    </div>
    <div class="main-content">
        <h2 class="section-title">Case Details</h2>
        <div class="detail-grid">
            <div class="detail-card">
                <div class="detail-row"><div class="detail-label">Case ID:</div><div class="detail-value">SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo $case['case_id']; ?></div></div>
                <div class="detail-row"><div class="detail-label">FIR Number:</div><div class="detail-value"><?php echo htmlspecialchars($case['fir_number']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Police Station:</div><div class="detail-value"><?php echo htmlspecialchars($case['police_station']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Registration Date:</div><div class="detail-value"><?php echo htmlspecialchars($case['fir_date']); ?></div></div>
            </div>
            <div class="detail-card">
                <div class="detail-row"><div class="detail-label">Current Status:</div><div class="detail-value"><span class="status-badge status-pending">Pending DCR Verification</span></div></div>
                <div class="detail-row"><div class="detail-label">Assigned IO:</div><div class="detail-value"><?php echo htmlspecialchars($case['investigating_officer']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Filed By:</div><div class="detail-value"><?php echo htmlspecialchars($case['filed_by_name']); ?></div></div>
            </div>
        </div>
        <h3 class="section-title">Victim Details</h3>
        <div class="detail-grid">
            <div class="detail-card">
                <div class="detail-row"><div class="detail-label">Full Name:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_name']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Age/Gender:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_age']) . ' / ' . htmlspecialchars($case['victim_gender']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Caste:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_caste']); ?></div></div>
            </div>
            <div class="detail-card">
                <div class="detail-row"><div class="detail-label">Contact:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_contact']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Aadhaar:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_aadhaar']); ?></div></div>
                <div class="detail-row"><div class="detail-label">Address:</div><div class="detail-value"><?php echo htmlspecialchars($case['victim_address']); ?></div></div>
            </div>
        </div>
        <h3 class="section-title">FIR Document</h3>
        <div class="detail-card">
            <?php if (!empty($documents)): ?>
                <?php foreach ($documents as $doc): if ($doc['document_type'] === 'fir'): ?>
                    <div class="fir-viewer">
                        <p><i class="fas fa-file-pdf"></i> <?php echo htmlspecialchars($doc['file_path']); ?></p>
                        <div class="fir-actions">
                            <a class="btn btn-primary" href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" download><i class="fas fa-download"></i> Download</a>
                            <a class="btn" href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank"><i class="fas fa-print"></i> Print</a>
                        </div>
                    </div>
                <?php endif; endforeach; ?>
            <?php else: ?>
                <p>No FIR document uploaded.</p>
            <?php endif; ?>
        </div>
        <h3 class="section-title">DCR Verification</h3>
        <form method="POST" action="handlers/process_dcr_case.php">
            <input type="hidden" name="case_id" value="<?php echo $case['case_id']; ?>">
            <div class="form-group">
                <label for="verification_status">FIR Verification Status</label>
                <select id="verification_status" name="verification_status" required>
                    <option value="pending" selected>Pending Verification</option>
                    <option value="verified">Verified - No Issues</option>
                    <option value="discrepancy">Discrepancy Found</option>
                </select>
            </div>
            <div class="form-group">
                <label for="dcr_remarks">DCR Remarks</label>
                <textarea id="dcr_remarks" name="dcr_remarks" placeholder="Enter your verification remarks..." required></textarea>
            </div>
            <div class="action-buttons">
                <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> Verify & Forward to SP</button>
            </div>
        </form>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html> 
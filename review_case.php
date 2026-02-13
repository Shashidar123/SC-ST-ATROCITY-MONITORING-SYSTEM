<?php
require_once 'includes/auth.php';
requireRole('rdo');
require_once 'includes/db.php';

if (!isset($_GET['case_id'])) {
    header('Location: sp_dashboard.php?error=No case specified');
    exit();
}
$case_id = $_GET['case_id'];

try {
    // Get main case details
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as filed_by_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.case_id = ?
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$case) {
        header('Location: sp_dashboard.php?error=Case not found');
        exit();
    }
    // Get DCR remarks and date
    $stmt = $pdo->prepare("SELECT * FROM case_status WHERE case_id = ? AND status = 'sp_review' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([$case_id]);
    $dcr_status = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get FIR document
    $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? AND document_type = 'fir' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$case_id]);
    $fir_doc = $stmt->fetch(PDO::FETCH_ASSOC);
    // Get all documents
    $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at DESC");
    $stmt->execute([$case_id]);
    $case_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    header('Location: sp_dashboard.php?error=' . urlencode('Error loading case details'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Office | SC/ST Atrocity Case Management</title>
    <style>
        :root {
            --police-blue: #0a4a7a;
            --police-dark-blue: #08315a;
            --police-light: #e9f2f9;
            --police-white: #ffffff;
            --police-dark: #333333;
            --police-gray: #e0e0e0;
            --collector-green: #2e7d32;
            --dcr-purple: #6a1b9a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: var(--police-light); }
        .header { background: linear-gradient(135deg, var(--police-dark-blue), var(--police-blue)); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .header-title { display: flex; align-items: center; gap: 1rem; }
        .badge { width: 50px; height: 50px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--police-blue); font-weight: bold; font-size: 1.2rem; border: 2px solid gold; }
        .header-actions button { background-color: #d32f2f; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
        .container { display: flex; min-height: calc(100vh - 82px); }
        .sidebar { width: 250px; background-color: white; padding: 1.5rem; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar-menu { list-style: none; margin-top: 2rem; }
        .sidebar-menu li { margin-bottom: 1rem; }
        .sidebar-menu a { display: flex; align-items: center; gap: 0.8rem; color: var(--police-dark); text-decoration: none; padding: 0.8rem; border-radius: 4px; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: var(--police-light); color: var(--police-blue); }
        .main-content { flex: 1; padding: 2rem; }
        .case-header { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .case-id { background-color: var(--police-blue); color: white; padding: 0.5rem 1rem; border-radius: 4px; }
        .case-section { background-color: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .section-title { color: var(--police-blue); margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--police-gray); }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .detail-card { background-color: var(--police-light); padding: 1rem; border-radius: 6px; }
        .detail-row { display: flex; margin-bottom: 0.8rem; }
        .detail-label { font-weight: 600; width: 150px; color: var(--police-dark); }
        .detail-value { flex: 1; }
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .evidence-item { border: 1px solid var(--police-gray); border-radius: 4px; overflow: hidden; }
        .evidence-img { width: 100%; height: 150px; object-fit: cover; }
        .evidence-doc { width: 100%; height: 150px; background-color: var(--police-light); display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--police-blue); }
        .evidence-caption { padding: 0.5rem; text-align: center; background-color: var(--police-gray); }
        .action-section { margin-top: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group select, .form-group textarea, .form-group input { width: 100%; padding: 0.8rem; border: 1px solid var(--police-gray); border-radius: 4px; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .action-buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background-color: var(--police-blue); color: white; }
        .btn-primary:hover { background-color: var(--police-dark-blue); }
        .btn-success { background-color: var(--collector-green); color: white; }
        .btn-success:hover { background-color: #1b5e20; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .dcr-section { background-color: #f3e5f5; border-left: 4px solid var(--dcr-purple); }
        .collector-section { background-color: #e8f5e9; border-left: 4px solid var(--collector-green); }
        .investigation-section { background-color: #fff3e0; border-left: 4px solid #fd7e14; }
        .status-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background-color: #fff3e0; color: #e65100; }
        .status-active { background-color: #e3f2fd; color: #1565c0; }
        .status-completed { background-color: #e8f5e9; color: var(--collector-green); }
        .status-received { background-color: #f3e5f5; color: var(--dcr-purple); }
        .file-upload { border: 2px dashed var(--police-gray); padding: 1.5rem; text-align: center; border-radius: 4px; margin-top: 1rem; cursor: pointer; }
        .file-upload:hover { border-color: var(--police-blue); }
        .fir-viewer { display: none; margin-top: 1rem; }
        .fir-viewer-content { height: 600px; background-color: #f5f5f5; border: 1px solid #ddd; display: flex; justify-content: center; align-items: center; }
        .fir-document { text-align: center; }
        .fir-document i { font-size: 3rem; color: #d32f2f; }
        .fir-document p { margin-top: 1rem; }
        .fir-details { margin-top: 2rem; text-align: left; padding: 1rem; background: white; max-height: 300px; overflow-y: auto; }
        @media (max-width: 768px) { .container { flex-direction: column; } .sidebar { width: 100%; } .detail-grid { grid-template-columns: 1fr; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <div class="badge">SP</div>
            <div>
                <h1>Superintendent of Police Dashboard</h1>
                <p>SC/ST Atrocity Case Management System</p>
            </div>
        </div>
        <div class="header-actions">
            <a href="logout.php" class="btn btn-primary"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="sp_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            </ul>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Case Header -->
            <div class="case-header">
                <h2>Case Details</h2>
                <div class="case-id">Case ID: SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></div>
            </div>
            <!-- DCR Submission Section -->
            <div class="case-section dcr-section">
                <h3 class="section-title">DCR Submission</h3>
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Received From DCR:</div>
                            <div class="detail-value"><?php echo $dcr_status ? date('d-m-Y H:i', strtotime($dcr_status['created_at'])) : '-'; ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">DCR Remarks:</div>
                            <div class="detail-value"><?php echo $dcr_status ? htmlspecialchars($dcr_status['comments']) : '-'; ?></div>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Current Status:</div>
                            <div class="detail-value">
                                <span class="status-badge status-received">Received from DCR</span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Priority:</div>
                            <div class="detail-value"><?php echo ucfirst($case['priority']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- FIR Copy from DCR -->
            <div class="case-section">
                <h3 class="section-title">FIR Copy from DCR</h3>
                <div class="detail-card">
                    <div class="detail-row">
                        <div class="detail-label">FIR Document:</div>
                        <div class="detail-value">
                            <?php if ($fir_doc): ?>
                                <a href="uploads/<?php echo htmlspecialchars($fir_doc['file_path']); ?>" class="btn btn-secondary" target="_blank">
                                    <i class="fas fa-file-pdf"></i> View FIR Copy
                                </a>
                            <?php else: ?>
                                <span>No FIR uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Case Information -->
            <div class="case-section">
                <h3 class="section-title">Case Information</h3>
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
                            <div class="detail-label">Registration Date:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['fir_date']); ?></div>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Sections Applied:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['case_sections']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Incident Date:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['incident_date'] ?? '-'); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Incident Location:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_address']); ?></div>
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
                            <div class="detail-label">Age/Gender:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_age']) . ' / ' . htmlspecialchars($case['victim_gender']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Caste:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_caste']); ?></div>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Contact:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_contact']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Aadhaar:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_aadhaar']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Address:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['victim_address']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- SP Assignment Section -->
            <div class="case-section">
                <h3 class="section-title">Investigation Assignment</h3>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                <form method="POST" action="handlers/assign_io.php" class="form-group">
                    <input type="hidden" name="case_id" value="<?php echo $case['case_id']; ?>">
                    <label>Investigating Officer Username</label>
                    <input type="text" name="io_username" class="form-control" placeholder="Enter IO username (e.g. io_user1)" required>
                    <label class="mt-3">SP Instructions</label>
                    <textarea class="form-control" name="sp_instructions" placeholder="Enter specific instructions for the investigating officer..." rows="3">Please prioritize this case due to severity of allegations. Collect all available CCTV footage from the incident location.</textarea>
                    <div class="action-buttons mt-3">
                        <button type="submit" class="btn btn-success"><i class="fas fa-user-check"></i> Assign Investigator</button>
                    </div>
                </form>
            </div>
            <!-- Investigation Report Section (Initially hidden) -->
            <div class="case-section investigation-section" id="investigationSection" style="display: none;">
                <h3 class="section-title">Investigation Report</h3>
                <!-- Investigation report details will go here in the future -->
                <div class="action-buttons mt-3">
                    <a href="recpdetails.html" class="btn btn-primary"><i class="fas fa-user-edit"></i> Recipient Form</a>
                </div>
            </div>
            <!-- SP Final Action Section (Initially hidden) -->
            <div class="case-section" id="spFinalAction" style="display: none;">
                <h3 class="section-title">SP Final Action</h3>
                <!-- SP final action form will go here in the future -->
            </div>
        </div>
    </div>
</body>
</html>
<?php
ob_start();
file_put_contents(__DIR__.'/debug_ajax.txt', 'START: '.date('c').PHP_EOL, FILE_APPEND);
file_put_contents(__DIR__.'/debug_ajax.txt', 'AFTER OB_START: '.date('c').PHP_EOL, FILE_APPEND);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
file_put_contents(__DIR__.'/debug_ajax.txt', 'BEFORE AUTH: '.date('c').PHP_EOL, FILE_APPEND);
// Handle forward to collector FIRST, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['forward_to_collector']) || isset($_POST['forward_to_c_section']))) {
    file_put_contents(__DIR__.'/debug_ajax.txt', 'IN AJAX HANDLER: '.date('c').PHP_EOL, FILE_APPEND);
    require_once 'includes/auth.php';
    requireRole('rdo'); // SP role
    file_put_contents(__DIR__.'/debug_ajax.txt', 'AFTER AUTH: '.date('c').PHP_EOL, FILE_APPEND);
    require_once 'includes/db.php';
    file_put_contents(__DIR__.'/debug_ajax.txt', 'AFTER DB: '.date('c').PHP_EOL, FILE_APPEND);
    $case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;
    $sp_comment = trim($_POST['collector_remarks'] ?? '');
    $user_id = $_SESSION['user_id'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE cases SET status = 'c_section_review', updated_at = NOW() WHERE case_id = ?");
        $stmt->execute([$case_id]);
        $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by) VALUES (?, 'c_section_review', ?, ?)");
        $stmt->execute([$case_id, $sp_comment ?: 'Forwarded to C Section by SP', $user_id]);
        $pdo->commit();
        if (isset($_POST['ajax'])) {
            file_put_contents(__DIR__.'/debug_ajax.txt', 'BEFORE JSON: '.ob_get_contents().PHP_EOL, FILE_APPEND);
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'case_id' => $case_id,
                'forwarded_to' => 'C Section',
                'sp_comment' => $sp_comment
            ]);
            exit;
        }
        header('Location: c_section_dashboard.php?success=Case forwarded to C Section');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Database error: ' . $e->getMessage();
        if (isset($_POST['ajax'])) {
            file_put_contents(__DIR__.'/debug_ajax.txt', 'BEFORE JSON ERROR: '.ob_get_contents().PHP_EOL, FILE_APPEND);
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

require_once 'includes/auth.php';
requireRole('rdo'); // SP role
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
$stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at DESC");
$stmt->execute([$case_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch investigation report
$stmt = $pdo->prepare("SELECT * FROM investigation_reports WHERE case_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$case_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch case status history
$stmt = $pdo->prepare("SELECT cs.*, u.username as updated_by_name FROM case_status cs JOIN users u ON cs.updated_by = u.id WHERE cs.case_id = ? ORDER BY cs.created_at ASC");
$stmt->execute([$case_id]);
$status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: get document by type
function get_documents($documents, $type) {
    return array_filter($documents, function($doc) use ($type) {
        return $doc['document_type'] === $type;
    });
}

// Helper: get first document path by type
function get_first_doc_path($documents, $type) {
    foreach ($documents as $doc) {
        if ($doc['document_type'] === $type) {
            return 'uploads/' . $doc['file_path'];
        }
    }
    return '';
}
?>
<!-- HTML below will be adapted to use PHP variables for dynamic data -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Dashboard | SC/ST Atrocity Case Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --io-orange: #ff6d00;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: var(--police-light);
        }
        .header {
            background: linear-gradient(135deg, var(--police-dark-blue), var(--police-blue));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .badge {
            width: 50px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--police-blue);
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid gold;
        }
        .header-actions button {
            background-color: #d32f2f;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .container {
            display: flex;
            min-height: calc(100vh - 82px);
        }
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 1.5rem;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar-menu {
            list-style: none;
            margin-top: 2rem;
        }
        .sidebar-menu li {
            margin-bottom: 1rem;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: var(--police-dark);
            text-decoration: none;
            padding: 0.8rem;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--police-light);
            color: var(--police-blue);
        }
        .main-content {
            flex: 1;
            padding: 2rem;
        }
        .case-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .case-id {
            background-color: var(--police-blue);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        .case-section {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .section-title {
            color: var(--police-blue);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--police-gray);
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .detail-card {
            background-color: var(--police-light);
            padding: 1rem;
            border-radius: 6px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 0.8rem;
        }
        .detail-label {
            font-weight: 600;
            width: 150px;
            color: var(--police-dark);
        }
        .detail-value {
            flex: 1;
        }
        .evidence-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .evidence-item {
            border: 1px solid var(--police-gray);
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .evidence-item:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .evidence-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .evidence-caption {
            padding: 0.5rem;
            text-align: center;
            background-color: var(--police-gray);
        }
        .action-section {
            margin-top: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group select, 
        .form-group textarea,
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--police-gray);
            border-radius: 4px;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: var(--police-blue);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--police-dark-blue);
        }
        .btn-success {
            background-color: var(--collector-green);
            color: white;
        }
        .btn-success:hover {
            background-color: #1b5e20;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-orange {
            background-color: var(--io-orange);
            color: white;
        }
        .btn-orange:hover {
            background-color: #e65100;
        }
        .dcr-section {
            background-color: #f3e5f5;
            border-left: 4px solid var(--dcr-purple);
        }
        .collector-section {
            background-color: #e8f5e9;
            border-left: 4px solid var(--collector-green);
        }
        .investigation-section {
            background-color: #fff3e0;
            border-left: 4px solid var(--io-orange);
        }
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        .status-active {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        .status-completed {
            background-color: #e8f5e9;
            color: var(--collector-green);
        }
        .status-received {
            background-color: #f3e5f5;
            color: var(--dcr-purple);
        }
        .status-investigation {
            background-color: #fff3e0;
            color: var(--io-orange);
        }
        .file-upload {
            border: 2px dashed var(--police-gray);
            padding: 1.5rem;
            text-align: center;
            border-radius: 4px;
            margin-top: 1rem;
            cursor: pointer;
        }
        .file-upload:hover {
            border-color: var(--police-blue);
        }
        .fir-viewer {
            display: none;
            margin-top: 1rem;
        }
        .fir-viewer-content {
            height: 600px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .fir-document {
            text-align: center;
        }
        .fir-document i {
            font-size: 3rem;
            color: #d32f2f;
        }
        .fir-document p {
            margin-top: 1rem;
        }
        .fir-details {
            margin-top: 2rem;
            text-align: left;
            padding: 1rem;
            background: white;
            max-height: 300px;
            overflow-y: auto;
        }
        .evidence-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
        }
        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
            }
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
        .btn-nostyle {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }
        .btn-nostyle:hover {
            background: #495057;
            color: #fff;
            text-decoration: none !important;
        }
        .evidence-caption a, .detail-value a {
            text-decoration: none !important;
            color: inherit;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <div class="badge">IO</div>
            <div>
                <h1>SP Officer Dashboard</h1>
                <p>SC/ST Atrocity Case Management System</p>
            </div>
        </div>
        <div class="header-actions">
            <form method="post" action="logout.php" style="display:inline;"><button type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button></form>
        </div>
    </header>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="sp_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="sp_dashboard.php"><i class="fas fa-clipboard-list"></i> My Cases</a></li>
            </ul>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Case Header -->
            <div class="case-header">
                <h2>Case Details</h2>
                <div class="case-id">Case ID: SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></div>
            </div>
            <!-- SP Assignment Section -->
            <div class="case-section investigation-section">
                <h3 class="section-title">SP Assignment</h3>
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Assigned By:</div>
                            <div class="detail-value">SP <?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Assignment Date:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['created_at']); ?></div>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Current Status:</div>
                            <div class="detail-value">
                                <span class="status-badge status-completed">SP Approved</span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Priority:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($case['priority']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>SP Instructions</label>
                    <textarea readonly><?php echo htmlspecialchars($case['sp_instructions'] ?? ''); ?></textarea>
                </div>
            </div>
            <!-- FIR Copy from DCR -->
            <div class="case-section">
                <h3 class="section-title">FIR Copy</h3>
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
                        <div class="detail-label">View FIR:</div>
                        <div class="detail-value">
                            <?php $fir_doc = get_first_doc_path($documents, 'fir'); if ($fir_doc): ?>
                                <a href="<?php echo $fir_doc; ?>" target="_blank" class="btn-nostyle"><i class="fas fa-file-pdf"></i> View FIR Document</a>
                            <?php else: ?>
                                <span>No FIR document uploaded.</span>
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
                            <div class="detail-value"><?php echo htmlspecialchars($case['incident_date']); ?></div>
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
            <!-- Medical Report Section -->
            <div class="case-section">
                <h3 class="section-title"><i class="fas fa-file-medical"></i> Medical Examination Report</h3>
                <div class="evidence-grid">
                    <?php $has_medical = false; foreach ($documents as $doc) { if ($doc['document_type'] === 'medical' && !empty($doc['file_path'])) { $has_medical = true; ?>
                        <div class="evidence-item">
                            <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn-nostyle">
                                <i class="fas fa-file-medical"></i> Medical Report
                            </a>
                            <div class="evidence-caption">
                                Uploaded by: <?php echo htmlspecialchars($doc['uploaded_by']); ?> on <?php echo date('d-m-Y H:i', strtotime($doc['created_at'])); ?>
                            </div>
                        </div>
                    <?php }} if (!$has_medical): ?>
                        <div class="evidence-item"><div class="evidence-caption">No Medical Report uploaded.</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- FIR Section -->
            <div class="case-section">
                <h3 class="section-title"><i class="fas fa-file-pdf"></i> FIR Copy</h3>
                <div class="evidence-grid">
                    <?php $has_fir = false; foreach ($documents as $doc) { if ($doc['document_type'] === 'fir' && !empty($doc['file_path'])) { $has_fir = true; ?>
                        <div class="evidence-item">
                            <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn-nostyle">
                                <i class="fas fa-file-pdf"></i> FIR Document
                            </a>
                            <div class="evidence-caption">
                                Uploaded by: <?php echo htmlspecialchars($doc['uploaded_by']); ?> on <?php echo date('d-m-Y H:i', strtotime($doc['created_at'])); ?>
                            </div>
                        </div>
                    <?php }} if (!$has_fir): ?>
                        <div class="evidence-item"><div class="evidence-caption">No FIR uploaded.</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- CCTV Footage Section -->
            <div class="case-section">
                <h3 class="section-title"><i class="fas fa-video"></i> CCTV Footage</h3>
                <div class="evidence-grid">
                    <?php $has_cctv = false; foreach ($documents as $doc) { if ($doc['document_type'] === 'cctv' && !empty($doc['file_path']) && file_exists('uploads/' . $doc['file_path'])) { $has_cctv = true; ?>
                        <div class="evidence-item">
                            <?php if (preg_match('/\.(mp4|webm|ogg)$/i', $doc['file_path'])): ?>
                                <video controls class="evidence-img"><source src="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" type="video/mp4"></video>
                            <?php else: ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                    <i class="fas fa-file-video fa-3x"></i> <?php echo htmlspecialchars($doc['file_path']); ?>
                                </a>
                            <?php endif; ?>
                            <div class="evidence-caption">
                                CCTV Footage<br>Uploaded by: <?php echo htmlspecialchars($doc['uploaded_by']); ?> on <?php echo date('d-m-Y H:i', strtotime($doc['created_at'])); ?>
                            </div>
                        </div>
                    <?php }} if (!$has_cctv): ?>
                        <div class="evidence-item"><div class="evidence-caption">No CCTV Footage uploaded.</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Crime Scene Photos Section -->
            <div class="case-section">
                <h3 class="section-title"><i class="fas fa-image"></i> Crime Scene Photos</h3>
                <div class="evidence-grid">
                    <?php $has_photo = false; foreach ($documents as $doc) { if ($doc['document_type'] === 'photo' && !empty($doc['file_path']) && file_exists('uploads/' . $doc['file_path'])) { $has_photo = true; ?>
                        <div class="evidence-item">
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])): ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                    <img src="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" class="evidence-img" alt="Crime Scene Photo">
                                </a>
                            <?php else: ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                    <i class="fas fa-file-image fa-3x"></i> <?php echo htmlspecialchars($doc['file_path']); ?>
                                </a>
                            <?php endif; ?>
                            <div class="evidence-caption">
                                Crime Scene Photo<br>Uploaded by: <?php echo htmlspecialchars($doc['uploaded_by']); ?> on <?php echo date('d-m-Y H:i', strtotime($doc['created_at'])); ?>
                            </div>
                        </div>
                    <?php }} if (!$has_photo): ?>
                        <div class="evidence-item"><div class="evidence-caption">No Crime Scene Photos uploaded.</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Additional Evidence Section -->
            <div class="case-section">
                <h3 class="section-title"><i class="fas fa-file-alt"></i> Additional Evidence</h3>
                <div class="evidence-grid">
                    <?php $has_evidence = false; foreach ($documents as $doc) { 
                        if ($doc['document_type'] === 'evidence' && !empty($doc['file_path']) && file_exists('uploads/' . $doc['file_path'])) { $has_evidence = true; ?>
                        <div class="evidence-item">
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])): ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                    <img src="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" class="evidence-img" alt="Evidence">
                                </a>
                            <?php elseif (preg_match('/\.(mp3|wav|ogg)$/i', $doc['file_path'])): ?>
                                <audio controls class="evidence-img"><source src="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" type="audio/mpeg"></audio>
                            <?php elseif (preg_match('/\.(mp4|webm|ogg)$/i', $doc['file_path'])): ?>
                                <video controls class="evidence-img"><source src="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" type="video/mp4"></video>
                            <?php elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])): ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                    <i class="fas fa-file-pdf fa-3x"></i> <?php echo htmlspecialchars($doc['file_path']); ?>
                                </a>
                            <?php else: ?>
                                <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                    <i class="fas fa-file-alt fa-3x"></i> <?php echo htmlspecialchars($doc['file_path']); ?>
                                </a>
                            <?php endif; ?>
                            <div class="evidence-caption">
                                Evidence<br>Uploaded by: <?php echo htmlspecialchars($doc['uploaded_by']); ?> on <?php echo date('d-m-Y H:i', strtotime($doc['created_at'])); ?>
                            </div>
                        </div>
                    <?php }} if (!$has_evidence): ?>
                        <div class="evidence-item"><div class="evidence-caption">No Additional Evidence uploaded.</div></div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Status History Timeline -->
            <div class="case-section">
                <h3 class="section-title">Status History</h3>
                <div class="detail-card">
                    <ul style="list-style:none;padding-left:0;">
                        <?php foreach ($status_history as $status): ?>
                        <li style="margin-bottom:1rem;">
                            <span class="status-badge status-<?php echo htmlspecialchars($status['status']); ?>">
                                <?php echo htmlspecialchars($status['status']); ?>
                            </span>
                            <br>
                            <small>By: <?php echo htmlspecialchars($status['updated_by_name']); ?> on <?php echo date('d-m-Y H:i', strtotime($status['created_at'])); ?></small>
                            <?php if (!empty($status['comments'])): ?>
                                <br><em>Comments: <?php echo htmlspecialchars($status['comments']); ?></em>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <!-- Investigation Report Section -->
            <div class="case-section investigation-section">
                <h3 class="section-title">Investigation Report</h3>
                <div class="form-group">
                    <label>Investigation Findings</label>
                    <textarea readonly><?php echo htmlspecialchars($report['findings'] ?? $case['io_report'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Witness Statements</label>
                    <textarea readonly><?php echo htmlspecialchars($report['witness_summary'] ?? $case['io_witness_statements'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>IO Recommendations</label>
                    <textarea readonly><?php echo htmlspecialchars($report['recommendations'] ?? $case['io_recommendations'] ?? ''); ?></textarea>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-check-circle"></i> Submitted to SP on <?php echo date('d-m-Y', strtotime($report['submitted_at'] ?? $case['updated_at'] ?? 'now')); ?>
                    </button>
                    <a href="recpdetails.html" class="btn btn-primary">
                        <i class="fas fa-user-edit"></i> Recipient Form
                    </a>
                </div>
            </div>
            <!-- Forward to C Section Section (was Forward to Collector) -->
            <div class="case-section collector-section">
                <h3 class="section-title">Forward to C Section</h3>
                <div class="detail-card">
                    <div class="detail-row">
                        <div class="detail-label">Current Status:</div>
                        <div class="detail-value">
                            <span class="status-badge status-active">Ready for Forwarding</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">SP Approval Date:</div>
                        <div class="detail-value"><?php echo date('d-m-Y H:i', strtotime($case['updated_at'] ?? 'now')); ?></div>
                    </div>
                </div>
                <form method="post" id="forwardForm">
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>C Section Remarks (Optional)</label>
                        <textarea name="collector_remarks" placeholder="Enter any specific remarks for C Section..."></textarea>
                    </div>
                    <div class="action-buttons">
                        <button class="btn btn-success" id="forwardToCSectionBtn" type="submit" name="forward_to_c_section">
                            <i class="fas fa-paper-plane"></i> Forward to C Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Success Modal and AJAX removed -->
</body>
</html>
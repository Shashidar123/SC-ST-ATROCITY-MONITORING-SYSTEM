<?php
require_once 'includes/auth.php';
requireRole('io');
require_once 'includes/db.php';

$case_id = $_GET['case_id'] ?? null;
$io_username = $_SESSION['username'];
$error = $success = '';

if (!$case_id) {
    die('No case selected.');
}

// Handle evidence upload via AJAX (prevent page refresh)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_evidence'])) {
    header('Content-Type: application/json');
    if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['evidence_file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_name = 'evidence_' . uniqid() . '.' . $ext;
        $target = 'uploads/' . $new_name;
        $doc_type = $_POST['document_type'] ?? 'evidence';
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $pdo->prepare("INSERT INTO case_documents (case_id, document_type, file_path, uploaded_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$case_id, $doc_type, $new_name, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => ucfirst($doc_type) . ' uploaded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'File upload failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file selected or upload error']);
    }
    exit;
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $findings = $_POST['io_report'] ?? '';
    $witnesses = $_POST['io_witness_statements'] ?? '';
    $recommend = $_POST['io_recommendations'] ?? '';
    try {
        // Update case with IO report
        $stmt = $pdo->prepare("UPDATE cases SET io_report = ?, io_witness_statements = ?, io_recommendations = ?, status = 'sp_review_from_io' WHERE case_id = ? AND io_username = ?");
        $stmt->execute([$findings, $witnesses, $recommend, $case_id, $io_username]);
        
        // Add status entry
        $stmt = $pdo->prepare("INSERT INTO case_status (case_id, status, comments, updated_by) VALUES (?, 'sp_review_from_io', 'IO submitted investigation report', ?)");
        $stmt->execute([$case_id, $_SESSION['user_id']]);
        
        $success = 'Report submitted to SP successfully.';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

$stmt = $pdo->prepare("SELECT * FROM cases WHERE case_id = ? AND io_username = ?");
$stmt->execute([$case_id, $io_username]);
$case = $stmt->fetch();
if (!$case) {
    die('Case not found or not assigned to you.');
}

$stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? AND (uploaded_by = ? OR uploaded_by IS NULL)");
$stmt->execute([$case_id, $_SESSION['user_id']]);
$evidence = $stmt->fetchAll();

$locked = ($case['status'] !== 'io_investigation');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IO Dashboard | SC/ST Atrocity Case Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --io-blue: #1a73e8;
            --io-light-blue: #e8f0fe;
            --io-dark-blue: #0d47a1;
            --io-secondary: #5f6368;
            --io-green: #34a853;
            --io-red: #ea4335;
        }
        body { background-color: var(--io-light-blue); padding: 20px; }
        .header { background: linear-gradient(135deg, var(--io-dark-blue), var(--io-blue)); color: white; padding: 1rem 2rem; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .badge-status { font-size: 0.85rem; padding: 0.35em 0.65em; }
        .badge-new { background-color: var(--io-blue); }
        .badge-inprogress { background-color: var(--io-blue); }
        .badge-completed { background-color: var(--io-green); }
        .badge-forwarded { background-color: var(--io-secondary); }
        .case-card { border-left: 4px solid var(--io-blue); margin-bottom: 20px; transition: transform 0.3s; }
        .case-card:hover { transform: translateY(-3px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .evidence-thumbnail { width: 120px; height: 120px; object-fit: cover; border: 2px solid #dee2e6; border-radius: 6px; cursor: pointer; transition: all 0.3s; }
        .evidence-thumbnail:hover { border-color: var(--io-blue); transform: scale(1.05); }
        .nav-pills .nav-link.active { background-color: var(--io-blue); }
        .nav-pills .nav-link { color: var(--io-dark-blue); }
        .detail-label { font-weight: 600; color: var(--io-dark-blue); }
        .medical-report { background-color: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; border-left: 3px solid var(--io-blue); }
        .evidence-card { border: 1px dashed var(--io-blue); border-radius: 8px; padding: 15px; margin-bottom: 15px; background-color: rgba(26, 115, 232, 0.05); }
        .form-section { background-color: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .dropzone { border: 2px dashed var(--io-blue) !important; border-radius: 8px; background: rgba(26, 115, 232, 0.05); }
        .dropzone .dz-message { color: var(--io-dark-blue); font-weight: 500; }
        .btn-io-primary { background-color: var(--io-blue); color: white; }
        .btn-io-primary:hover { background-color: var(--io-dark-blue); color: white; }
        .btn-io-outline { border-color: var(--io-blue); color: var(--io-blue); }
        .btn-io-outline:hover { background-color: var(--io-light-blue); }
        .evidence-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-top: 15px; }
        .evidence-item { position: relative; }
        .evidence-actions { position: absolute; top: 5px; right: 5px; display: none; }
        .evidence-item:hover .evidence-actions { display: block; }
        .evidence-actions .btn { padding: 0.25rem 0.4rem; font-size: 0.7rem; }
        .witness-card { border-left: 4px solid var(--io-green); margin-bottom: 15px; }
        .cctv-card { border-left: 4px solid var(--io-secondary); margin-bottom: 15px; }
        .alert-fixed { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px; }
        .upload-form { display: none; }
        .upload-form.show { display: block; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-clipboard2-data-fill"></i> Investigating Officer Dashboard</h2>
                    <p class="mb-0">SC/ST Atrocity Case Management System</p>
                </div>
                <div>
                    <span class="badge bg-dark me-2">IO: <?php echo htmlspecialchars($io_username); ?></span>
                    <a href="io_dashboard.php" class="btn btn-sm btn-outline-light" id="backToDashboardBtn">
                        <i class="bi bi-box-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <div id="alertArea"></div>

        <!-- Case Details -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Case Details: SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></h5>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($case['fir_number']); ?></span>
                </div>
            </div>
            <div class="card-body">
                <ul class="nav nav-pills mb-4" id="caseTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" role="tab">Case Details</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="evidence-tab" data-bs-toggle="pill" data-bs-target="#evidence" type="button" role="tab">Evidence</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="report-tab" data-bs-toggle="pill" data-bs-target="#report" type="button" role="tab">Investigation Report</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="recipient-tab" href="recpdetails.html" role="tab">Recipient Form</a>
                    </li>
                </ul>
                <div class="tab-content" id="caseTabContent">
                    <!-- Case Details Tab -->
                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6><i class="bi bi-file-text"></i> FIR Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">FIR Number:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['fir_number']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Police Station:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['police_station']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Incident Date:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['incident_date']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Location:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['victim_address']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Sections:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['case_sections']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6><i class="bi bi-person-vcard"></i> Victim Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Name:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['victim_name']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Age/Gender:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['victim_age']) . ' / ' . htmlspecialchars($case['victim_gender']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Caste:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['victim_caste']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Contact:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['victim_contact']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Address:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['victim_address']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6><i class="bi bi-clipboard2-pulse"></i> Investigation Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Assigned On:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['created_at']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Assigned By:</div>
                                            <div class="col-8">SP Office</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Due Date:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['fir_date']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Priority:</div>
                                            <div class="col-8"><?php echo htmlspecialchars($case['priority']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4 detail-label">Status:</div>
                                            <div class="col-8"><span class="badge badge-status badge-inprogress"><?php echo htmlspecialchars($case['status']); ?></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6><i class="bi bi-journal-text"></i> SP Instructions</h6>
                                    </div>
                                    <div class="card-body">
                                        <p><?php echo htmlspecialchars($case['sp_instructions'] ?? ''); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Evidence Tab -->
                    <div class="tab-pane fade" id="evidence" role="tabpanel">
                        <div class="form-section">
                            <h5><i class="bi bi-file-earmark-medical"></i> Medical Examination Report</h5>
                            <div class="evidence-grid">
                                <?php foreach ($evidence as $doc): if ($doc['document_type'] === 'medical') {
                                    $fileUrl = 'uploads/' . $doc['file_path'];
                                    if (empty($doc['file_path']) || !file_exists($fileUrl)) { continue; }
                                    if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])) { ?>
                                        <div class="evidence-item">
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($fileUrl); ?>" class="evidence-thumbnail" alt="Medical Report">
                                            </a>
                                        </div>
                                    <?php } elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])) { ?>
                                        <div class="evidence-item">
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-file-earmark-pdf-fill"></i> <span>Medical Report (PDF)</span>
                                            </a>
                                        </div>
                                    <?php } else { ?>
                                        <div class="evidence-item">
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-file-earmark-text"></i> <span>Medical Report</span>
                                            </a>
                                        </div>
                                    <?php } } endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-io-outline btn-sm" id="addMedicalBtn">
                                    <i class="bi bi-plus-circle"></i> Add Medical Report
                                </button>
                            </div>
                        </div>
                        <div class="form-section">
                            <h5><i class="bi bi-camera-video"></i> CCTV Footage</h5>
                            <div class="evidence-grid">
                                <?php foreach ($evidence as $doc): if ($doc['document_type'] === 'cctv') {
                                    $fileUrl = 'uploads/' . $doc['file_path'];
                                    if (empty($doc['file_path']) || !file_exists($fileUrl)) { continue; } ?>
                                    <div class="evidence-item">
                                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($fileUrl); ?>" class="evidence-thumbnail" alt="CCTV Footage">
                                            </a>
                                        <?php elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-file-earmark-pdf-fill"></i> <span>View PDF</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-film"></i> <span>Open File</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php } endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-io-outline btn-sm" id="addCCTVBtn">
                                    <i class="bi bi-plus-circle"></i> Add CCTV Footage
                                </button>
                            </div>
                        </div>
                        <div class="form-section">
                            <h5><i class="bi bi-image"></i> Crime Scene Photos</h5>
                            <div class="evidence-grid">
                                <?php foreach ($evidence as $doc): if ($doc['document_type'] === 'photo') {
                                    $fileUrl = 'uploads/' . $doc['file_path'];
                                    if (empty($doc['file_path']) || !file_exists($fileUrl)) { continue; } ?>
                                    <div class="evidence-item">
                                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($fileUrl); ?>" class="evidence-thumbnail" alt="Crime Scene Photo">
                                            </a>
                                        <?php elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-file-earmark-pdf-fill"></i> <span>View PDF</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-file-earmark-text"></i> <span>Open File</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php } endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-io-outline btn-sm" id="addPhotoBtn">
                                    <i class="bi bi-plus-circle"></i> Add Crime Scene Photo
                                </button>
                            </div>
                        </div>
                        <div class="form-section">
                            <h5><i class="bi bi-file-earmark-text"></i> Additional Evidence</h5>
                            <div class="evidence-grid">
                                <?php foreach ($evidence as $doc): if ($doc['document_type'] === 'evidence') {
                                    $fileUrl = 'uploads/' . $doc['file_path'];
                                    if (empty($doc['file_path']) || !file_exists($fileUrl)) { continue; } ?>
                                    <div class="evidence-item">
                                        <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $doc['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($fileUrl); ?>" class="evidence-thumbnail" alt="Evidence">
                                            </a>
                                        <?php elseif (preg_match('/\.(pdf)$/i', $doc['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-file-earmark-pdf-fill"></i> <span>View PDF</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-file-earmark-text"></i> <span>Open File</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php } endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-io-outline btn-sm" id="addEvidenceBtn">
                                    <i class="bi bi-plus-circle"></i> Add Evidence
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Investigation Report Tab -->
                    <div class="tab-pane fade" id="report" role="tabpanel">
                        <div class="form-section">
                            <h5><i class="bi bi-pencil-square"></i> Investigation Report</h5>
                            <div class="mb-4">
                                <label class="form-label detail-label">Investigation Findings *</label>
                                <textarea class="form-control" id="investigationFindings" rows="5" placeholder="Enter your investigation findings..."><?php echo htmlspecialchars($case['io_report'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label detail-label">Witness Statements Summary</label>
                                <textarea class="form-control" id="witnessSummary" rows="4" placeholder="Summarize witness statements..."><?php echo htmlspecialchars($case['io_witness_statements'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label detail-label">IO Recommendations *</label>
                                <textarea class="form-control" id="ioRecommendations" rows="3" placeholder="Enter your recommendations..."><?php echo htmlspecialchars($case['io_recommendations'] ?? ''); ?></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-io-outline" id="saveDraftBtn">
                                    <i class="bi bi-save"></i> Save Draft
                                </button>
                                <button class="btn btn-io-primary" id="submitToSPBtn">
                                    <i class="bi bi-send-check"></i> Submit to SP
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <a href="io_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Upload Modals -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="uploadModalTitle"><i class="bi bi-upload"></i> Upload File</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                        <input type="hidden" name="document_type" id="documentType">
                        <div class="mb-3">
                            <label for="evidenceFile" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="evidenceFile" name="evidence_file" required>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle"></i> Report Submitted</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Your investigation report has been successfully submitted to the <b>SP Office</b>.</p>
                    <p><strong>Case ID:</strong> SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></p>
                    <p><strong>Victim Name:</strong> <?php echo htmlspecialchars($case['victim_name']); ?></p>
                    <p><strong>Submitted By:</strong> <?php echo htmlspecialchars($io_username); ?></p>
                    <p><strong>Submitted On:</strong> <span id="submissionDate"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal" id="successOkBtn">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAlert(msg, type = 'success') {
            var alertHtml = `<div class="alert alert-${type} alert-dismissible fade show alert-fixed" role="alert">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
            $('#alertArea').html(alertHtml);
            setTimeout(function(){ $('.alert').alert('close'); }, 5000);
        }

        function reloadEvidence() {
            location.reload();
        }

        $(document).ready(function() {
            // Set current date/time for submission
            const now = new Date();
            const formattedDate = now.toLocaleDateString('en-GB') + ' ' + now.toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'});
            $('#submissionDate').text(formattedDate);

            // Back to Dashboard
            $('#backToDashboardBtn').click(function(e) {
                e.preventDefault();
                window.location.href = 'io_dashboard.php';
            });

            // Success modal OK
            $('#successOkBtn').click(function() {
                window.location.href = 'io_dashboard.php';
            });

            // Upload buttons
            $('#addMedicalBtn').click(function() {
                $('#uploadModalTitle').html('<i class="bi bi-file-earmark-medical"></i> Add Medical Report');
                $('#documentType').val('medical');
                $('#uploadModal').modal('show');
            });
            $('#addCCTVBtn').click(function() {
                $('#uploadModalTitle').html('<i class="bi bi-camera-video"></i> Add CCTV Footage');
                $('#documentType').val('cctv');
                $('#uploadModal').modal('show');
            });
            $('#addPhotoBtn').click(function() {
                $('#uploadModalTitle').html('<i class="bi bi-image"></i> Add Crime Scene Photo');
                $('#documentType').val('photo');
                $('#uploadModal').modal('show');
            });
            $('#addEvidenceBtn').click(function() {
                $('#uploadModalTitle').html('<i class="bi bi-file-earmark-text"></i> Add Evidence');
                $('#documentType').val('evidence');
                $('#uploadModal').modal('show');
            });

            // Handle file upload via AJAX
            $('#uploadForm').submit(function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('upload_evidence', '1');

                $.ajax({
                    url: 'review_io_case.php?case_id=<?php echo $case_id; ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            var result = JSON.parse(response);
                            if (result.success) {
                                showAlert(result.message);
                                $('#uploadModal').modal('hide');
                                setTimeout(reloadEvidence, 1500);
                            } else {
                                showAlert(result.message, 'danger');
                            }
                        } catch (e) {
                            showAlert('Upload completed successfully!');
                            $('#uploadModal').modal('hide');
                            setTimeout(reloadEvidence, 1500);
                        }
                    },
                    error: function() {
                        showAlert('Error uploading file!', 'danger');
                    }
                });
            });

            // Submit to SP
            $('#submitToSPBtn').click(function() {
                var caseId = <?php echo json_encode($case_id); ?>;
                var findings = $('#investigationFindings').val();
                var witnessSummary = $('#witnessSummary').val();
                var recommendations = $('#ioRecommendations').val();

                if (!findings.trim() || !recommendations.trim()) {
                    showAlert('Please fill in all required fields (Investigation Findings and IO Recommendations) before submitting', 'danger');
                    return;
                }

                // Show loading state
                var btn = $(this);
                var originalText = btn.html();
                btn.html('<i class="bi bi-hourglass-split"></i> Submitting...');
                btn.prop('disabled', true);

                $.post('handlers/investigation_report.php', {
                    case_id: caseId,
                    findings: findings,
                    witness_summary: witnessSummary,
                    recommendations: recommendations,
                    status: 'submitted'
                }, function(reportRes) {
                    $.post('handlers/update_case_status.php', {
                        case_id: caseId,
                        status: 'sp_review_from_io'
                    }, function(statusRes) {
                        btn.html(originalText);
                        btn.prop('disabled', false);
                        // Show success modal with details
                        $('#successModal').modal('show');
                        // Optionally, update dashboard stats via AJAX
                        $.get('io_dashboard.php?ajax=1', function(data) {
                            // You can parse and update stats here if needed
                        });
                    }).fail(function() {
                        btn.html(originalText);
                        btn.prop('disabled', false);
                        showAlert('Error updating case status!', 'danger');
                    });
                }).fail(function() {
                    btn.html(originalText);
                    btn.prop('disabled', false);
                    showAlert('Error submitting report!', 'danger');
                });
            });

            // Save Draft
            $('#saveDraftBtn').click(function() {
                var caseId = <?php echo json_encode($case_id); ?>;
                var findings = $('#investigationFindings').val();
                var witnessSummary = $('#witnessSummary').val();
                var recommendations = $('#ioRecommendations').val();

                var btn = $(this);
                var originalText = btn.html();
                btn.html('<i class="bi bi-hourglass-split"></i> Saving...');
                btn.prop('disabled', true);

                $.post('handlers/investigation_report.php', {
                    case_id: caseId,
                    findings: findings,
                    witness_summary: witnessSummary,
                    recommendations: recommendations,
                    status: 'draft'
                }, function(reportRes) {
                    btn.html(originalText);
                    btn.prop('disabled', false);
                    showAlert('Draft saved successfully!');
                    // Optionally, update dashboard stats via AJAX
                    $.get('io_dashboard.php?ajax=1', function(data) {
                        // You can parse and update stats here if needed
                    });
                }).fail(function() {
                    btn.html(originalText);
                    btn.prop('disabled', false);
                    showAlert('Error saving draft!', 'danger');
                });
            });
        });
    </script>
</body>
</html>
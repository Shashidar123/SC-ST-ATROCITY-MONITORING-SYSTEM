<?php
require_once 'includes/auth.php';

// Check if case ID is provided
if (!isset($_GET['id'])) {
    header('Location: ' . ($_SESSION['role'] === 'police' ? 'police.php' : 'sp_dashboard.php'));
    exit;
}

$case_id = $_GET['id'];

try {
    // Get case details with compensation info
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            cs.status as current_status,
            cs.comments as status_comments,
            cs.created_at as status_date,
            u.username as filed_by_name,
            comp.amount as compensation_amount,
            comp.status as compensation_status
        FROM cases c
        JOIN case_status cs ON c.case_id = cs.case_id
        JOIN users u ON c.filed_by = u.id
        LEFT JOIN compensation comp ON c.case_id = comp.case_id
        WHERE c.case_id = ?
        AND cs.created_at = (
            SELECT MAX(created_at)
            FROM case_status
            WHERE case_id = c.case_id
        )
    ");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();

    if (!$case) {
        throw new Exception('Case not found');
    }

    // Get case history
    $stmt = $pdo->prepare("
        SELECT 
            cs.*,
            u.username as updated_by_name
        FROM case_status cs
        JOIN users u ON cs.updated_by = u.id
        WHERE cs.case_id = ?
        ORDER BY cs.created_at DESC
    ");
    $stmt->execute([$case_id]);
    $case_history = $stmt->fetchAll();

    // Fetch all documents for this case
    $stmt = $pdo->prepare("SELECT * FROM case_documents WHERE case_id = ? ORDER BY created_at DESC");
    $stmt->execute([$case_id]);
    $case_documents = $stmt->fetchAll();

} catch (Exception $e) {
    header('Location: ' . ($_SESSION['role'] === 'police' ? 'police.php' : 'sp_dashboard.php') . '?error=' . urlencode($e->getMessage()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Case - SC/ST Case Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f6fa;
        }
        
        .nav-bar {
            background-color: var(--primary-color);
            padding: 15px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-bar h2 {
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-bar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            background-color: var(--secondary-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .case-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .case-details h2 {
            color: var(--primary-color);
            margin-top: 0;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #2c3e50;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .status-sp_review {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-c_section {
            background-color: #d4edda;
            color: #155724;
        }
        
        .case-history {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .history-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .history-date {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .history-status {
            margin: 10px 0;
        }
        
        .history-comments {
            color: #2c3e50;
            margin-top: 5px;
        }
        
        .action-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="nav-bar">
        <h2>Case Details</h2>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="<?php echo $_SESSION['role'] === 'police' ? 'police.php' : 'sp_dashboard.php'; ?>">Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Case has been successfully processed and forwarded.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="case-details">
            <h2>Case #<?php echo htmlspecialchars($case['case_id']); ?></h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Victim Name</h5>
                            <p class="card-text"><?php echo htmlspecialchars($case['victim_name']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Case Type</h5>
                            <p class="card-text"><?php echo htmlspecialchars($case['case_type']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Filed By</h5>
                            <p class="card-text"><?php echo htmlspecialchars($case['filed_by_name']); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Filed Date</h5>
                            <p class="card-text"><?php echo date('Y-m-d', strtotime($case['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Current Status</h5>
                            <p class="card-text">
                                <span class="badge bg-<?php echo strtolower($case['current_status']) === 'sp_review' ? 'warning' : (strtolower($case['current_status']) === 'c_section' ? 'success' : (strtolower($case['current_status']) === 'collector' ? 'info' : (strtolower($case['current_status']) === 'social_welfare' ? 'primary' : 'secondary'))); ?>">
                                    <?php echo htmlspecialchars($case['current_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Victim Address</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($case['victim_address'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php if (!empty($case['incident_description'])): ?>
                <div class="col-md-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Incident Description</h5>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($case['incident_description'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($case['compensation_amount']): ?>
                <div class="col-md-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Compensation Details</h5>
                            <p class="card-text">
                                Amount: ₹<?php echo number_format($case['compensation_amount'], 2); ?><br>
                                Status: <span class="badge bg-<?php echo strtolower($case['compensation_status']) === 'approved' ? 'success' : 'secondary'; ?>">
                                    <?php echo htmlspecialchars($case['compensation_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Case Documents Section -->
        <div class="card mb-4">
            <div class="card-header bg-light"><h5 class="mb-0">Case Documents & Evidence</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if (!empty($case_documents)): ?>
                        <?php foreach ($case_documents as $doc): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="d-flex align-items-center gap-2 p-2 bg-body-secondary rounded shadow-sm">
                                    <?php
                                        $icon = 'bi-file-earmark';
                                        $label = 'View Document';
                                        $type = strtolower($doc['document_type']);
                                        if (strpos($doc['file_path'], '.pdf') !== false) {
                                            $icon = 'bi-file-earmark-pdf';
                                        } elseif (strpos($doc['file_path'], '.png') !== false || strpos($doc['file_path'], '.jpg') !== false || strpos($doc['file_path'], '.jpeg') !== false) {
                                            $icon = 'bi-file-earmark-image';
                                        } elseif (strpos($doc['file_path'], '.log') !== false) {
                                            $icon = 'bi-file-earmark-text';
                                        }
                                        if ($type === 'fir') $label = 'View FIR Document';
                                        elseif ($type === 'medical') $label = 'View Medical Report';
                                        elseif ($type === 'evidence') $label = 'View Evidence';
                                        elseif ($type === 'digital_signature') $label = 'View Digital Signature';
                                        else $label = 'View ' . ucwords(str_replace('_', ' ', $type));
                                    ?>
                                    <i class="bi <?php echo $icon; ?> fs-3 text-secondary"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-capitalize"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type']))); ?></div>
                                        <a href="uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm mt-1 w-100">
                                            <i class="bi <?php echo $icon; ?> me-1"></i> <?php echo $label; ?>
                                        </a>
                                        <div class="small text-muted mt-1">Uploaded: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($doc['created_at']))); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-muted">No documents uploaded for this case.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($_SESSION['role'] === 'sp' && $case['current_status'] === 'sp_review'): ?>
            <div class="action-form">
                <h3>Forward to C-Section</h3>
                <form action="handlers/process_case.php" method="POST">
                    <input type="hidden" name="case_id" value="<?php echo htmlspecialchars($case['case_id']); ?>">
                    <input type="hidden" name="new_status" value="c_section">
                    
                    <div class="form-group">
                        <label for="comments">Comments/Observations</label>
                        <textarea name="comments" id="comments" required class="form-control"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Forward to C-Section</button>
                </form>
            </div>
        <?php elseif ($_SESSION['role'] === 'c_section' && $case['current_status'] === 'c_section'): ?>
            <div class="action-form">
                <h3>Forward to Collector</h3>
                <form action="handlers/process_case.php" method="POST">
                    <input type="hidden" name="case_id" value="<?php echo htmlspecialchars($case['case_id']); ?>">
                    <input type="hidden" name="new_status" value="collector">
                    
                    <div class="form-group">
                        <label for="compensation_amount">Recommended Compensation Amount (₹)</label>
                        <input type="number" id="compensation_amount" name="compensation_amount" min="0" step="1000" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Comments/Observations</label>
                        <textarea name="comments" id="comments" required class="form-control"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Forward to Collector</button>
                </form>
            </div>
        <?php elseif ($_SESSION['role'] === 'collector' && $case['current_status'] === 'collector'): ?>
            <div class="action-form">
                <h3>Review Compensation</h3>
                <form action="handlers/process_case.php" method="POST">
                    <input type="hidden" name="case_id" value="<?php echo htmlspecialchars($case['case_id']); ?>">
                    <input type="hidden" name="new_status" value="social_welfare">
                    
                    <div class="form-group">
                        <label for="compensation_amount">Approved Compensation Amount (₹)</label>
                        <input type="number" id="compensation_amount" name="compensation_amount" 
                               min="0" step="1000" required
                               value="<?php echo htmlspecialchars($case['compensation_amount']); ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Comments/Observations</label>
                        <textarea name="comments" id="comments" required class="form-control"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Approve & Forward to Social Welfare</button>
                </form>
            </div>
        <?php elseif ($_SESSION['role'] === 'social_welfare' && $case['current_status'] === 'social_welfare' && $case['compensation_status'] === 'approved'): ?>
            <div class="action-form">
                <h3>Process Compensation Disbursement</h3>
                <form action="handlers/process_case.php" method="POST">
                    <input type="hidden" name="case_id" value="<?php echo htmlspecialchars($case['case_id']); ?>">
                    <input type="hidden" name="new_status" value="completed">
                    
                    <div class="form-group">
                        <label for="disbursement_details">Disbursement Details</label>
                        <textarea name="disbursement_details" id="disbursement_details" required
                                placeholder="Enter payment reference number, bank details, or any other relevant disbursement information" class="form-control"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="comments">Additional Comments</label>
                        <textarea name="comments" id="comments" required class="form-control"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Mark as Disbursed & Complete Case</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="case-history">
            <h3>Case History</h3>
            <div class="list-group">
                <?php foreach ($case_history as $history): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1">
                                <?php echo date('Y-m-d H:i:s', strtotime($history['created_at'])); ?>
                                by <?php echo htmlspecialchars($history['updated_by_name']); ?>
                            </p>
                            <span class="badge bg-<?php echo strtolower($history['status']) === 'sp_review' ? 'warning' : (strtolower($history['status']) === 'c_section' ? 'success' : (strtolower($history['status']) === 'collector' ? 'info' : (strtolower($history['status']) === 'social_welfare' ? 'primary' : 'secondary'))); ?>">
                                <?php echo htmlspecialchars($history['status']); ?>
                            </span>
                        </div>
                        <?php if ($history['comments']): ?>
                            <p class="mb-1 text-muted">
                                <?php echo nl2br(htmlspecialchars($history['comments'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    });
    </script>
</body>
</html> 
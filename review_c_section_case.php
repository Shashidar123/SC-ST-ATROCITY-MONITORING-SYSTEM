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

// Fetch status history
$stmt = $pdo->prepare("SELECT cs.*, u.username as updated_by_name FROM case_status cs JOIN users u ON cs.updated_by = u.id WHERE cs.case_id = ? ORDER BY cs.created_at ASC");
$stmt->execute([$case_id]);
$status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest investigation report
$stmt = $pdo->prepare("SELECT * FROM investigation_reports WHERE case_id = ? ORDER BY submitted_at DESC LIMIT 1");
$stmt->execute([$case_id]);
$investigation_report = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch compensation breakdown and approvals
$comp_breakdown = [];
$comp_approval = null;
try {
    $stmt = $pdo->prepare("SELECT type, amount FROM compensation_breakdown WHERE case_id = ?");
    $stmt->execute([$case_id]);
    $comp_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT approved_amount FROM compensation_approvals WHERE case_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$case_id]);
    $comp_approval = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Map breakdown into categories we display
function normalize_comp_type($type) {
    $t = strtolower(trim($type));
    $t = str_replace([' ', '-', '/'], '_', $t);
    return $t;
}
$comp_values = [
    'medical_expenses' => null,
    'physical_injury' => null,
    'mental_trauma' => null,
    'loss_of_wages' => null
];
foreach ($comp_breakdown as $row) {
    $key = normalize_comp_type($row['type'] ?? '');
    if ($key === 'medical_expenses' || $key === 'medical') $comp_values['medical_expenses'] = $row['amount'];
    if ($key === 'physical_injury' || $key === 'physical') $comp_values['physical_injury'] = $row['amount'];
    if ($key === 'mental_trauma' || $key === 'mental') $comp_values['mental_trauma'] = $row['amount'];
    if ($key === 'loss_of_wages' || $key === 'wages') $comp_values['loss_of_wages'] = $row['amount'];
}
$comp_total = array_sum(array_map(function($v){ return $v !== null ? (float)$v : 0; }, $comp_values));
$approved_total = $comp_approval['approved_amount'] ?? $comp_total;

function format_inr($amount) {
    if ($amount === null || $amount === '') return '-';
    return '₹' . number_format((float)$amount, 2, '.', ',');
}

// Helper: get document by type
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

// Find latest collector action in status history
$latest_collector_action = null;
foreach (array_reverse($status_history) as $status) {
    if (in_array($status['status'], ['collector_approved','collector_reverify','collector_rejected','collector_allotted'])) {
        $latest_collector_action = $status;
        break;
    }
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
            --police-blue: #0a4a7a;
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
        .btn-secondary { background-color: var(--police-blue); color: white; }
        .btn-secondary:hover { background-color: #08315a; }
        .sp-section { background-color: #e9f2f9; border-left: 4px solid var(--police-blue); }
        .sp-title { color: var(--police-blue); }
        .status-badge { display: inline-block; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background-color: #fff3e0; color: #e65100; }
        .status-active { background-color: #e3f2fd; color: #1565c0; }
        .status-completed { background-color: #e8f5e9; color: var(--collector-green); }
        .compensation-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .compensation-table th, .compensation-table td { padding: 0.8rem; border: 1px solid var(--collector-gray); text-align: left; }
        .compensation-table th { background-color: var(--collector-green); color: white; }
        .compensation-table tr:nth-child(even) { background-color: var(--collector-light); }
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
                <h1>C-section </h1>
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
                <li><a href="c_section_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#"><i class="fas fa-list"></i> Pending Cases</a></li>
                <li><a href="#"><i class="fas fa-rupee-sign"></i> Compensation</a></li>
            </ul>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <!-- Case Header -->
            <div class="case-header">
                <h2>Case Review & Compensation</h2>
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
                                <span class="status-badge status-active">Pending Compensation</span>
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
            <!-- SP Office Section -->
            <div class="case-section sp-section">
                <h3 class="section-title sp-title">SP Office Submission</h3>
                <div class="detail-card">
                    <div class="detail-row">
                        <div class="detail-label">Investigation Findings:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($investigation_report['findings'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Witness Statements:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($investigation_report['witness_summary'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">IO Recommendations:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($investigation_report['recommendations'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Evidence Status:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['evidence_status'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Recommended Compensation:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['recommended_compensation'] ?? '-'); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">SP Comments:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($case['sp_comments'] ?? ''); ?></div>
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
            <!-- Compensation Section -->
            <div class="case-section compensation-section">
                <h3 class="section-title">Compensation Approval (GOMS 95)</h3>
                <table class="compensation-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>GOMS 95 Clause</th>
                            <th>Standard Amount</th>
                            <th>Proposed Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Medical Expenses</td>
                            <td>Clause 5(a)</td>
                            <td>₹25,000</td>
                            <td><?php echo format_inr($comp_values['medical_expenses'] ?? 25000); ?></td>
                        </tr>
                        <tr>
                            <td>Physical Injury</td>
                            <td>Clause 7(b) - Moderate</td>
                            <td>₹50,000</td>
                            <td><?php echo format_inr($comp_values['physical_injury'] ?? 50000); ?></td>
                        </tr>
                        <tr>
                            <td>Mental Trauma</td>
                            <td>Clause 8</td>
                            <td>₹25,000</td>
                            <td><?php echo format_inr($comp_values['mental_trauma'] ?? 25000); ?></td>
                        </tr>
                        <tr>
                            <td>Loss of Wages</td>
                            <td>Clause 10</td>
                            <td>₹15,000</td>
                            <td><?php echo format_inr($comp_values['loss_of_wages'] ?? 15000); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td></td>
                            <td><strong>₹1,15,000</strong></td>
                            <td><strong><?php echo format_inr($approved_total ?: $comp_total); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                <div class="form-group" style="margin-top: 2rem;">
                    <label>Additional Compensation (If any)</label>
                    <input type="number" placeholder="Enter additional amount" min="0" value="">
                </div>
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select>
                        <option value="direct">Direct Bank Transfer (DBT)</option>
                        <option value="cheque">Cheque</option>
                        <option value="cash">Cash (Special Case)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>C-section Remarks</label>
                    <textarea placeholder="Enter official remarks for this compensation...">Approved ₹1,15,000 as per GOMS 95 Clause 5(a), 7(b), 8, and 10 for medical expenses, physical injury, mental trauma, and loss of wages. Case meets all criteria for immediate disbursement.</textarea>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-secondary"><i class="fas fa-save"></i> Save Draft</button>
                    <button class="btn btn-primary"><i class="fas fa-check-circle"></i> Approve Compensation</button>
                </div>
            </div>
            <!-- Final Approval Section -->
            <div class="case-section">
                <h3 class="section-title">Final Approval</h3>
                <form action="handlers/process_case.php" method="post" enctype="multipart/form-data" style="margin-top:1.5rem;" id="finalApprovalForm">
                    <div class="detail-card">
                        <div class="detail-row">
                            <div class="detail-label">Approval Authority:</div>
                            <div class="detail-value">District Collector (<?php echo htmlspecialchars($case['district'] ?? '-'); ?>)</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Digital Signature:</div>
                            <div class="detail-value">
                                <input type="file" id="digitalSignatureInput" name="digital_signature" accept=".pfx,.pdf,.jpg,.jpeg,.png" style="display:inline;">
                                <span id="signatureFileName" style="margin-left:10px;"></span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="case_id" value="<?php echo $case['case_id']; ?>">
                    <input type="hidden" name="action" id="finalApprovalAction" value="forward_to_collector">
                    <div class="form-group">
                        <label>Remarks for Collector (optional)</label>
                        <textarea name="collector_remarks" placeholder="Enter any remarks for Collector..."></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit" onclick="document.getElementById('finalApprovalAction').value='forward_to_collector';"><i class="fas fa-paper-plane"></i> Forward to Collector</button>
                    <button class="btn btn-success" type="submit" style="margin-left:10px;" onclick="document.getElementById('finalApprovalAction').value='forward_to_social_welfare'; return true;"><i class="fas fa-paper-plane"></i> Forward to Social Welfare</button>
                </form>
                <div id="grand-success-modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);align-items:center;justify-content:center;">
                    <div style="background:white;padding:2rem 2.5rem;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.2);text-align:center;max-width:90vw;min-width:350px;">
                        <div style="display:flex;align-items:center;gap:1rem;justify-content:center;margin-bottom:1rem;">
                            <span style="font-size:2.5rem;color:#2e7d32;"><i class="fas fa-check-circle"></i></span>
                            <h2 style="color:#2e7d32;margin:0;">Report Submitted</h2>
                        </div>
                        <p style="font-size:1.1rem;">Your case has been successfully forwarded to the Collector.</p>
                        <div style="margin:1.5rem 0;text-align:left;">
                            <strong>Case ID:</strong> SCST/<?php echo date('Y', strtotime($case['created_at'])); ?>/<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?><br>
                            <strong>Submitted On:</strong> <?php echo date('d/m/Y H:i'); ?>
                        </div>
                        <button id="grandSuccessOkBtn" class="btn btn-success" style="margin-top:1.5rem;">OK</button>
                    </div>
                </div>
            </div>
            <?php if ($latest_collector_action): ?>
            <div class="case-section" style="background:#e8f5e9;border-left:4px solid #2e7d32;">
                <h3 class="section-title">Latest Collector Action</h3>
                <div style="padding:1em 0 0.5em 0;">
                    <span class="badge" style="background-color:#2e7d32;color:white;font-size:1rem;padding:0.5em 1em;">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $latest_collector_action['status']))); ?>
                    </span>
                    <span style="margin-left:1em;color:#333;font-weight:600;">by <?php echo htmlspecialchars($latest_collector_action['updated_by_name']); ?> on <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($latest_collector_action['created_at']))); ?></span>
                    <div style="margin-top:0.5em;background:#f1f8e9;padding:1em;border-radius:6px;min-height:40px;">
                        <?php echo nl2br(htmlspecialchars($latest_collector_action['comments'] ?? '')); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Show filename for digital signature
            const digitalSignatureInput = document.getElementById('digitalSignatureInput');
            const signatureFileName = document.getElementById('signatureFileName');
            if (digitalSignatureInput && signatureFileName) {
                digitalSignatureInput.onchange = function() {
                    if (digitalSignatureInput.files.length > 0) {
                        signatureFileName.textContent = digitalSignatureInput.files[0].name;
                    } else {
                        signatureFileName.textContent = '';
                    }
                };
            }
            // Calculate total compensation dynamically
            const inputs = document.querySelectorAll('.compensation-table input');
            inputs.forEach(input => {
                input.addEventListener('change', updateTotal);
            });
            function updateTotal() {
                let total = 0;
                inputs.forEach(input => {
                    total += parseInt(input.value) || 0;
                });
                document.querySelector('.compensation-table tr:last-child td:last-child').textContent = '₹' + total.toLocaleString('en-IN');
            }
            // Approve Compensation button
            const approveBtn = document.querySelector('.compensation-section .btn-primary');
            if (approveBtn) {
                approveBtn.addEventListener('click', function() {
                    if(confirm('Are you sure you want to approve this compensation? This will initiate DBT to victim.')) {
                        alert('Compensation approved successfully! Disbursement initiated.');
                        // In real implementation, would update status in backend
                    }
                });
            }
            // Submit to Commission button
            const submitBtns = document.querySelectorAll('.case-section:last-child .btn');
            submitBtns.forEach(function(submitBtn) {
                submitBtn.addEventListener('click', function() {
                    alert('Case and compensation details submitted.');
                });
            });
            // Forward to Collector form (show grand modal on success)
            var forwardForm = document.getElementById('finalApprovalForm');
            if (forwardForm) {
                forwardForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(forwardForm);
                    fetch('handlers/process_case.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('grand-success-modal').style.display = 'flex';
                            document.getElementById('grandSuccessOkBtn').onclick = function() {
                                window.location.href = 'c_section_dashboard.php';
                            };
                            setTimeout(function() {
                                window.location.href = 'c_section_dashboard.php';
                            }, 2000);
                        } else {
                            alert('Error: ' + (data.message || 'An error occurred.'));
                        }
                    })
                    .catch(async (err) => {
                        let msg = 'An error occurred while forwarding the case.';
                        if (err && err.response && err.response.text) {
                            msg = await err.response.text();
                        }
                        alert('Error: ' + msg);
                    });
                });
            }
        });
    </script>
</body>
</html> 
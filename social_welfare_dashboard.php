<?php
require_once 'includes/auth.php';
requireRole('social_welfare');
require_once 'includes/db.php';

// Fetch stats
$total_cases = 0;
$pending_cases = 0;
$completed_cases = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases WHERE status IN ('social_welfare_review', 'from_social_welfare', 'compensation_rejected')");
    $total_cases = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases WHERE status = 'social_welfare_review'");
    $pending_cases = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases WHERE status = 'from_social_welfare'");
    $completed_cases = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Fetch cases for review (pending in Social Welfare)
$cases = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as complainant_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status = 'social_welfare_review'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch completed cases (forwarded to C Section)
$completed_cases_list = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as complainant_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status = 'from_social_welfare'
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute();
    $completed_cases_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!-- Social Welfare Department Dashboard - Dynamic Version -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SWD Dashboard - Case Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --swd-green: #2e7d32;
            --swd-dark-green: #1b5e20;
            --swd-light-green: #e8f5e9;
            --swd-accent-green: #4caf50;
        }
        body { padding: 0; background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { height: 100vh; background-color: var(--swd-green); color: white; position: fixed; width: 250px; padding-top: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .main-content { margin-left: 250px; padding: 20px; background-color: var(--swd-light-green); }
        .nav-link { color: rgba(255,255,255,.8); margin-bottom: 5px; border-radius: 4px; padding: 10px 15px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { color: white; background-color: rgba(255,255,255,.1); transform: translateX(5px); }
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .logout-btn { position: absolute; bottom: 20px; left: 20px; right: 20px; }
        .stats-card { border-radius: 8px; padding: 20px; margin-bottom: 20px; color: white; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: all 0.3s; border: none; }
        .stats-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .stats-card.total { background: linear-gradient(135deg, var(--swd-green), var(--swd-dark-green)); }
        .stats-card.pending { background: linear-gradient(135deg, #388e3c, #1b5e20); }
        .stats-card.completed { background: linear-gradient(135deg, #4caf50, #2e7d32); }
        .stats-card h3 { font-size: 2.5rem; margin: 10px 0; font-weight: 700; }
        .stats-card p { opacity: 0.9; margin-bottom: 0; }
        .section-title { color: var(--swd-green); border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; font-weight: 600; }
        .all-cases-table { width: 100%; border-collapse: collapse; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .all-cases-table th { background-color: var(--swd-green); color: white; padding: 12px; text-align: left; }
        .all-cases-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .all-cases-table tr:hover { background-color: rgba(46, 125, 50, 0.05); }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-case-new { background-color: var(--swd-accent-green); color: white; }
        .badge-case-inprogress { background-color: #ff9800; color: white; }
        .badge-case-resolved { background-color: #34a853; color: white; }
        .priority-high { color: #d32f2f; font-weight: 600; }
        .priority-medium { color: #ff9800; font-weight: 600; }
        .priority-low { color: #6c757d; font-weight: 600; }
        .filter-container { background-color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-check-input:checked { background-color: var(--swd-green); border-color: var(--swd-green); }
        .form-check-label { font-weight: 500; }
        .btn-primary { background-color: var(--swd-green); border-color: var(--swd-green); }
        .btn-primary:hover { background-color: var(--swd-dark-green); border-color: var(--swd-dark-green); }
        .comment-box { width: 100%; min-height: 100px; padding: 10px; border-radius: 5px; border: 1px solid #ced4da; }
        .action-buttons { margin-top: 15px; }
        .action-btn { margin-right: 10px; }
        .recommendation-options { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-people-fill" style="font-size: 2.5rem;"></i>
            </div>
            <h4>Social Welfare Dept</h4>
            <p class="text-muted">Case Management System</p>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#completed-cases" data-bs-toggle="tab">
                    <i class="bi bi-check-circle"></i> Completed Cases
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="recpdetails.html">
                    <i class="bi bi-person-vcard"></i> Recipient Form
                </a>
            </li>
        </ul>
        <div class="logout-btn">
            <a href="logout.php" class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="bi bi-person-badge"></i> Social Welfare Department Dashboard</h1>
            <div class="text-muted">
                <i class="bi bi-calendar"></i> <span id="current-date"><?php echo date('F j, Y'); ?></span>
            </div>
        </div>
        <div class="tab-content">
            <!-- Dashboard -->
            <div class="tab-pane fade show active" id="dashboard">
                <h3 class="section-title"><i class="bi bi-speedometer2"></i> Overview</h3>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card total">
                            <i class="bi bi-folder" style="font-size: 2rem;"></i>
                            <h3><?php echo $total_cases; ?></h3>
                            <p>Total Cases</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card pending">
                            <i class="bi bi-hourglass" style="font-size: 2rem;"></i>
                            <h3><?php echo $pending_cases; ?></h3>
                            <p>Pending Cases</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card completed">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            <h3><?php echo $completed_cases; ?></h3>
                            <p>Completed Cases</p>
                        </div>
                    </div>
                </div>

                <h3 class="section-title"><i class="bi bi-list-ul"></i> Cases for Review</h3>
                <div class="table-responsive">
                    <table class="all-cases-table">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>FIR No.</th>
                                <th>Complainant</th>
                                <th>Type</th>
                                <th>Police Station</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cases as $case): ?>
                            <tr>
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['complainant_name']); ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars($case['police_station']); ?></td>
                                <td><span class="priority-<?php echo strtolower($case['priority']); ?>"><?php echo htmlspecialchars($case['priority']); ?></span></td>
                                <td><span class="status-badge badge-case-new">New from C Section</span></td>
                                <td>
                                    <a href="review_social_welfare_case.php?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($cases)): ?>
                            <tr><td colspan="8" style="text-align:center; color:#888;">No cases found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Completed Cases -->
            <div class="tab-pane fade" id="completed-cases">
                <h3 class="section-title"><i class="bi bi-check-circle"></i> Completed Cases</h3>
                <p class="text-muted mb-4">Cases forwarded to C Section</p>
                <div class="table-responsive">
                    <table class="all-cases-table">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>FIR No.</th>
                                <th>Complainant</th>
                                <th>Type</th>
                                <th>Finalized On</th>
                                <th>Recommendation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($completed_cases_list as $case): ?>
                            <tr>
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['complainant_name']); ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($case['updated_at']))); ?></td>
                                <td>
                                    <?php
                                    // Fetch latest recommendation for this case
                                    $rec_stmt = $pdo->prepare("SELECT recommendation FROM recommendations WHERE case_id = ? ORDER BY created_at DESC LIMIT 1");
                                    $rec_stmt->execute([$case['case_id']]);
                                    $rec = $rec_stmt->fetchColumn();
                                    echo $rec ? htmlspecialchars(ucwords(str_replace('_', ' ', $rec))) : '-';
                                    ?>
                                </td>
                                <td>
                                    <a href="review_social_welfare_case.php?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($completed_cases_list)): ?>
                            <tr><td colspan="7" style="text-align:center; color:#888;">No completed cases found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Reports -->
            <div class="tab-pane fade" id="reports">
                <h3 class="section-title"><i class="bi bi-file-earmark-bar-graph"></i> Reports</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-pie-chart"></i> Case Statistics</h5>
                                <div class="text-center my-4">
                                    <img src="https://via.placeholder.com/300x200?text=Case+Statistics+Chart" alt="Case Statistics" class="img-fluid">
                                </div>
                                <p class="card-text">Monthly breakdown of cases received and resolved by the department.</p>
                                <button class="btn btn-primary">Generate Report</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-bar-chart"></i> Compensation Analysis</h5>
                                <div class="text-center my-4">
                                    <img src="https://via.placeholder.com/300x200?text=Compensation+Analysis" alt="Compensation Analysis" class="img-fluid">
                                </div>
                                <p class="card-text">Analysis of compensation awarded to victims by category and amount.</p>
                                <button class="btn btn-primary">Generate Report</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
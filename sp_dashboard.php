<?php
require_once 'includes/auth.php';
requireRole('rdo');
require_once 'includes/db.php';

// Fetch DCR-forwarded cases (status = 'sp_review')
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as filed_by_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status = 'sp_review'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $dcr_cases = $stmt->fetchAll();
} catch (PDOException $e) {
    $dcr_cases = [];
}

// Fetch IO-forwarded cases (status = 'sp_review_from_io')
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as filed_by_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status = 'sp_review_from_io'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $io_forwarded_cases = $stmt->fetchAll();
} catch (PDOException $e) {
    $io_forwarded_cases = [];
}

// Fetch completed cases (status = 'c_section_review')
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as filed_by_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status = 'c_section_review'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $completed_cases = $stmt->fetchAll();
} catch (PDOException $e) {
    $completed_cases = [];
}

// Fetch IO cases (future, status = 'io_review')
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as filed_by_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status = 'io_review'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $io_cases = $stmt->fetchAll();
} catch (PDOException $e) {
    $io_cases = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SP Office Dashboard - Case Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --police-blue: #0a4a7a;
            --police-dark-blue: #08315a;
            --police-light-blue: #e9f2f9;
            --police-accent-blue: #1a73e8;
        }
        body { padding: 0; background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { height: 100vh; background-color: var(--police-blue); color: white; position: fixed; width: 250px; padding-top: 20px; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .main-content { margin-left: 250px; padding: 20px; background-color: var(--police-light-blue); }
        .nav-link { color: rgba(255,255,255,.8); margin-bottom: 5px; border-radius: 4px; padding: 10px 15px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { color: white; background-color: rgba(255,255,255,.1); transform: translateX(5px); }
        .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .logout-btn { position: absolute; bottom: 20px; left: 20px; right: 20px; }
        .stats-card { border-radius: 8px; padding: 20px; margin-bottom: 20px; color: white; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: all 0.3s; border: none; }
        .stats-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .stats-card.total { background: linear-gradient(135deg, var(--police-blue), var(--police-dark-blue)); }
        .stats-card.pending { background: linear-gradient(135deg, #1a73e8, #0d47a1); }
        .stats-card.completed { background: linear-gradient(135deg, #34a853, #0d652d); }
        .stats-card h3 { font-size: 2.5rem; margin: 10px 0; font-weight: 700; }
        .stats-card p { opacity: 0.9; margin-bottom: 0; }
        .section-title { color: var(--police-blue); border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; font-weight: 600; }
        .all-cases-table { width: 100%; border-collapse: collapse; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .all-cases-table th { background-color: var(--police-blue); color: white; padding: 12px; text-align: left; }
        .all-cases-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .all-cases-table tr:hover { background-color: rgba(10, 74, 122, 0.05); }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-case-new { background-color: var(--police-accent-blue); color: white; }
        .badge-case-inprogress { background-color: #ff9800; color: white; }
        .badge-case-resolved { background-color: #34a853; color: white; }
        .priority-high { color: #d32f2f; font-weight: 600; }
        .priority-medium { color: #ff9800; font-weight: 600; }
        .priority-low { color: #6c757d; font-weight: 600; }
        .filter-container { background-color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-check-input:checked { background-color: var(--police-blue); border-color: var(--police-blue); }
        .form-check-label { font-weight: 500; }
        .btn-primary { background-color: var(--police-blue); border-color: var(--police-blue); }
        .btn-primary:hover { background-color: var(--police-dark-blue); border-color: var(--police-dark-blue); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-shield-lock" style="font-size: 2.5rem;"></i>
            </div>
            <h4>SP Office</h4>
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
        </ul>
        <div class="logout-btn">
            <a href="logout.php" class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="bi bi-person-badge"></i> Superintendent of Police Dashboard</h1>
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
                            <h3><?php echo count($dcr_cases) + count($io_forwarded_cases) + count($completed_cases); ?></h3>
                            <p>Total Cases</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card pending">
                            <i class="bi bi-hourglass" style="font-size: 2rem;"></i>
                            <h3><?php echo count($dcr_cases) + count($io_forwarded_cases); ?></h3>
                            <p>Pending Cases</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card completed">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                            <h3><?php echo count($completed_cases); ?></h3>
                            <p>Completed Cases</p>
                        </div>
                    </div>
                </div>
                <div class="filter-container">
                    <h5><i class="bi bi-funnel"></i> Filter Cases</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="dcrFilter" checked>
                                <label class="form-check-label" for="dcrFilter">From DCR</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ioFilter" checked>
                                <label class="form-check-label" for="ioFilter">From Investigation Officers</label>
                            </div>
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
                                <th>Assigned To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dcr_cases as $case): ?>
                            <tr class="dcr-case">
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['victim_name']) . ' (' . strtoupper($case['victim_caste']) . ')'; ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars($case['police_station']); ?></td>
                                <td><span class="priority-<?php echo htmlspecialchars($case['priority']); ?>"><?php echo ucfirst($case['priority']); ?></span></td>
                                <td><span class="status-badge badge-case-new">New from DCR</span></td>
                                <td>-</td>
                                <td>
                                    <?php
                                    // Determine the correct review page based on status
                                    $review_page = ($case['status'] === 'sp_review_from_io') ? 'review_io_sp_case.php' : 'review_case.php';
                                    ?>
                                    <a href="<?php echo $review_page; ?>?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Review & Assign
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($io_forwarded_cases as $case): ?>
                            <tr class="io-case">
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['victim_name']) . ' (' . strtoupper($case['victim_caste']) . ')'; ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars($case['police_station']); ?></td>
                                <td><span class="priority-<?php echo htmlspecialchars($case['priority']); ?>"><?php echo ucfirst($case['priority']); ?></span></td>
                                <td><span class="status-badge badge-case-inprogress">From IO</span></td>
                                <td><?php echo htmlspecialchars($case['io_username']); ?></td>
                                <td>
                                    <?php
                                    // Determine the correct review page based on status
                                    $review_page = ($case['status'] === 'sp_review_from_io') ? 'review_io_sp_case.php' : 'review_case.php';
                                    ?>
                                    <a href="<?php echo $review_page; ?>?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Review
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($io_cases as $case): ?>
                            <tr class="io-case">
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['victim_name']) . ' (' . strtoupper($case['victim_caste']) . ')'; ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars($case['police_station']); ?></td>
                                <td><span class="priority-<?php echo htmlspecialchars($case['priority']); ?>"><?php echo ucfirst($case['priority']); ?></span></td>
                                <td><span class="status-badge badge-case-inprogress">From IO</span></td>
                                <td><?php echo htmlspecialchars($case['assigned_officer']); ?></td>
                                <td>
                                    <?php
                                    // Determine the correct review page based on status
                                    $review_page = ($case['status'] === 'sp_review_from_io') ? 'review_io_sp_case.php' : 'review_case.php';
                                    ?>
                                    <a href="<?php echo $review_page; ?>?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Review
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Completed Cases -->
            <div class="tab-pane fade" id="completed-cases">
                <h3 class="section-title"><i class="bi bi-check-circle"></i> Completed Cases</h3>
                <p class="text-muted mb-4">Cases finalized and sent to Collector</p>
                <div class="table-responsive">
                    <table class="all-cases-table">
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>FIR No.</th>
                                <th>Complainant</th>
                                <th>Type</th>
                                <th>Finalized On</th>
                                <th>Disposition</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_cases as $case): ?>
                            <tr>
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['victim_name']) . ' (' . strtoupper($case['victim_caste']) . ')'; ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($case['updated_at']))); ?></td>
                                <td>Forwarded to Collector</td>
                                <td>
                                    <a href="review_sp_completed_case.php?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Review
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['case_id'])): ?>
        <?php
        // Fetch minimal case details for the success message
        try {
            $stmt = $pdo->prepare("SELECT victim_name, fir_number, case_type, created_at FROM cases WHERE case_id = ?");
            $stmt->execute([$_GET['case_id']]);
            $case = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $case = null;
        }
        ?>
        <div class="modal fade" id="forwardSuccessModal" tabindex="-1" aria-labelledby="forwardSuccessModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="forwardSuccessModalLabel">Case Forwarded Successfully!</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <?php if ($case): ?>
                    <p><strong>Victim Name:</strong> <?php echo htmlspecialchars($case['victim_name']); ?><br>
                    <strong>FIR Number:</strong> <?php echo htmlspecialchars($case['fir_number']); ?><br>
                    <strong>Case Type:</strong> <?php echo htmlspecialchars($case['case_type']); ?><br>
                    <strong>Date:</strong> <?php echo htmlspecialchars(date('d-m-Y', strtotime($case['created_at']))); ?></p>
                <?php else: ?>
                    <p>Case forwarded successfully!</p>
                <?php endif; ?>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-success" id="forwardSuccessOkBtn">OK</button>
              </div>
            </div>
          </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
          var modal = new bootstrap.Modal(document.getElementById('forwardSuccessModal'));
          modal.show();
          document.getElementById('forwardSuccessOkBtn').onclick = function() {
            window.location.href = 'sp_dashboard.php';
          };
          // Also close on modal close (X)
          document.getElementById('forwardSuccessModal').addEventListener('hidden.bs.modal', function () {
            window.location.href = 'sp_dashboard.php';
          });
        });
        </script>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Filter cases based on checkbox selection
            function filterCases() {
                const showDcr = $('#dcrFilter').is(':checked');
                const showIo = $('#ioFilter').is(':checked');
                $('.dcr-case').toggle(showDcr);
                $('.io-case').toggle(showIo);
                if (!showDcr && !showIo) {
                    $('tbody tr').show(); // Show all if both unchecked
                }
            }
            // Initialize filter
            filterCases();
            // Add event listeners to checkboxes
            $('#dcrFilter, #ioFilter').change(function() {
                filterCases();
            });
        });
    </script>
</body>
</html> 
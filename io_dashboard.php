<?php
require_once 'includes/auth.php';
requireRole('io');
require_once 'includes/db.php';

$io_username = $_SESSION['username'];

// Fetch stats
$stats = [
    'assigned' => 0,
    'inprogress' => 0,
    'completed' => 0,
    'forwarded' => 0
];

$cases = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM cases WHERE io_username = ?");
    $stmt->execute([$io_username]);
    $cases = $stmt->fetchAll();
    foreach ($cases as $case) {
        $stats['assigned']++;
        if ($case['status'] === 'io_investigation') {
            $stats['inprogress']++;
        } elseif ($case['status'] === 'completed') {
            $stats['completed']++;
        } elseif ($case['status'] === 'sp_review_from_io') {
            $stats['forwarded']++;
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!-- BEGIN NEW IO DASHBOARD UI -->
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
        body {
            background-color: var(--io-light-blue);
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, var(--io-dark-blue), var(--io-blue));
            color: white;
            padding: 1rem 2rem;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge-status {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        .badge-new {
            background-color: var(--io-blue);
        }
        .badge-inprogress {
            background-color: var(--io-blue);
        }
        .badge-completed {
            background-color: var(--io-green);
        }
        .badge-forwarded {
            background-color: var(--io-secondary);
        }
        .case-card {
            border-left: 4px solid var(--io-blue);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .case-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .evidence-thumbnail {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .evidence-thumbnail:hover {
            border-color: var(--io-blue);
            transform: scale(1.05);
        }
        .nav-pills .nav-link.active {
            background-color: var(--io-blue);
        }
        .nav-pills .nav-link {
            color: var(--io-dark-blue);
        }
        .detail-label {
            font-weight: 600;
            color: var(--io-dark-blue);
        }
        .medical-report {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid var(--io-blue);
        }
        .evidence-card {
            border: 1px dashed var(--io-blue);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: rgba(26, 115, 232, 0.05);
        }
        .form-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .dropzone {
            border: 2px dashed var(--io-blue) !important;
            border-radius: 8px;
            background: rgba(26, 115, 232, 0.05);
        }
        .dropzone .dz-message {
            color: var(--io-dark-blue);
            font-weight: 500;
        }
        .btn-io-primary {
            background-color: var(--io-blue);
            color: white;
        }
        .btn-io-primary:hover {
            background-color: var(--io-dark-blue);
            color: white;
        }
        .btn-io-outline {
            border-color: var(--io-blue);
            color: var(--io-blue);
        }
        .btn-io-outline:hover {
            background-color: var(--io-light-blue);
        }
        .evidence-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .evidence-item {
            position: relative;
        }
        .evidence-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: none;
        }
        .evidence-item:hover .evidence-actions {
            display: block;
        }
        .evidence-actions .btn {
            padding: 0.25rem 0.4rem;
            font-size: 0.7rem;
        }
        .witness-card {
            border-left: 4px solid var(--io-green);
            margin-bottom: 15px;
        }
        .cctv-card {
            border-left: 4px solid var(--io-secondary);
            margin-bottom: 15px;
        }
        .icon-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(26, 115, 232, 0.15);
            font-size: 2rem;
            margin-left: auto;
        }
        .bg-primary {
            background-color: var(--io-blue) !important;
        }
        .bg-warning {
            background-color: #ffc107 !important;
        }
        .bg-success {
            background-color: var(--io-green) !important;
        }
        .bg-purple {
            background-color: #7c3aed !important;
        }
        .icon-circle i {
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-clipboard2-data-fill"></i> Investigating Officer Dashboard</h1>
            <div>
                <span class="badge bg-dark me-2">IO: <?php echo htmlspecialchars($io_username); ?></span>
                <a href="logout.php" class="btn btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">ASSIGNED CASES</h6>
                                <h3 id="assigned-cases"><?php echo $stats['assigned']; ?></h3>
                            </div>
                            <div class="icon-circle bg-primary text-white">
                                <i class="bi bi-folder" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">IN PROGRESS</h6>
                                <h3 id="inprogress-cases"><?php echo $stats['inprogress']; ?></h3>
                            </div>
                            <div class="icon-circle bg-warning text-dark">
                                <i class="bi bi-hourglass-split" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">COMPLETED</h6>
                                <h3 id="completed-cases"><?php echo $stats['completed']; ?></h3>
                            </div>
                            <div class="icon-circle bg-success text-white">
                                <i class="bi bi-check-circle" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">FORWARDED</h6>
                                <h3 id="forwarded-cases"><?php echo $stats['forwarded']; ?></h3>
                            </div>
                            <div class="icon-circle bg-purple text-white">
                                <i class="bi bi-send-check" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white border-bottom-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-table"></i> My Assigned Cases</h5>
                </div>
            </div>
            <div class="card-body">
                <table id="casesTable" class="table table-hover" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Case ID</th>
                            <th>FIR Number</th>
                            <th>Police Station</th>
                            <th>Assigned On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['case_id']); ?></td>
                            <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                            <td><?php echo htmlspecialchars($case['police_station']); ?></td>
                            <td><?php echo htmlspecialchars($case['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($case['fir_date']); ?></td>
                            <td>
                                <?php if ($case['status'] === 'io_investigation'): ?>
                                    <span class="badge badge-status badge-inprogress">In Progress</span>
                                <?php elseif ($case['status'] === 'completed'): ?>
                                    <span class="badge badge-status badge-completed">Completed</span>
                                <?php elseif ($case['status'] === 'sp_review_from_io'): ?>
                                    <span class="badge badge-status badge-forwarded">Forwarded</span>
                                <?php else: ?>
                                    <span class="badge badge-status badge-new">New</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="review_io_case.php?case_id=<?php echo urlencode($case['case_id']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#casesTable').DataTable({
                responsive: true
            });
            // Handle submit to SP button
            $('#submitToSPBtn').click(function() {
                // 1. Submit the investigation report
                // (Assume you have a variable caseId for the current case)
                var caseId = 'REPLACE_WITH_CASE_ID'; // TODO: Set this dynamically
                var findings = $('#investigationFindings').val();
                var witnessSummary = $('#witnessSummary').val();
                var recommendations = $('#ioRecommendations').val();
                $.post('handlers/investigation_report.php', {
                    case_id: caseId,
                    findings: findings,
                    witness_summary: witnessSummary,
                    recommendations: recommendations,
                    status: 'submitted'
                }, function(reportRes) {
                    // 2. Update the case status to 'sp_review_from_io'
                    $.post('handlers/update_case_status.php', {
                        case_id: caseId,
                        status: 'sp_review_from_io'
                    }, function(statusRes) {
                        // 3. Show success modal
                        $('#successModal').modal('show');
                    });
                });
            });
        });
    </script>
</body>
</html> 
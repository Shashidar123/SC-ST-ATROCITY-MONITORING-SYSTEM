<?php
require_once 'includes/auth.php';
requireRole('c_section');
require_once 'includes/db.php';

// Fetch stats
$total_cases = 0;
$pending_cases = 0;
$completed_cases = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases");
    $total_cases = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases WHERE status IN ('c_section_review','from_social_welfare')");
    $pending_cases = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM cases WHERE status = 'completed'");
    $completed_cases = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Fetch cases for review (from SP and Social Welfare)
$cases = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as complainant_name,
            (
                SELECT cs.status FROM case_status cs WHERE cs.case_id = c.case_id ORDER BY cs.created_at DESC LIMIT 1
            ) as latest_status
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status IN ('c_section_review','from_social_welfare')
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Fetch forwarded (completed) cases for the Forwarded Cases tab
$forwarded_cases = [];
$forwarded_cases_count = 0;
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as complainant_name
        FROM cases c
        JOIN users u ON c.filed_by = u.id
        WHERE c.status IN ('collector_review','collector_approved','collector_reverify','collector_rejected','collector_allotted')
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute();
    $forwarded_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $forwarded_cases_count = count($forwarded_cases);
} catch (PDOException $e) {}

// Fetch case details for modal if forwarded
$modal_case = null;
if (isset($_GET['success'], $_GET['case_id']) && $_GET['success'] === 'Case forwarded to Collector!') {
    $modal_case_id = intval($_GET['case_id']);
    $stmt = $pdo->prepare("SELECT c.*, u.username as complainant_name FROM cases c JOIN users u ON c.filed_by = u.id WHERE c.case_id = ?");
    $stmt->execute([$modal_case_id]);
    $modal_case = $stmt->fetch(PDO::FETCH_ASSOC);
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
        body {
            padding: 0;
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            height: 100vh;
            background-color: var(--police-blue);
            color: white;
            position: fixed;
            width: 250px;
            padding-top: 20px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            background-color: var(--police-light-blue);
        }
        .nav-link {
            color: rgba(255,255,255,.8);
            margin-bottom: 5px;
            border-radius: 4px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.1);
            transform: translateX(5px);
        }
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }
        .stats-card {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border: none;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .stats-card.total {
            background: linear-gradient(135deg, var(--police-blue), var(--police-dark-blue));
        }
        .stats-card.pending {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
        }
        .stats-card.completed {
            background: linear-gradient(135deg, #34a853, #0d652d);
        }
        .stats-card h3 {
            font-size: 2.5rem;
            margin: 10px 0;
            font-weight: 700;
        }
        .stats-card p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        .section-title {
            color: var(--police-blue);
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .all-cases-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .all-cases-table th {
            background-color: var(--police-blue);
            color: white;
            padding: 12px;
            text-align: left;
        }
        .all-cases-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .all-cases-table tr:hover {
            background-color: rgba(10, 74, 122, 0.05);
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-case-new {
            background-color: var(--police-accent-blue);
            color: white;
        }
        .badge-case-inprogress {
            background-color: #ff9800;
            color: white;
        }
        .badge-case-resolved {
            background-color: #34a853;
            color: white;
        }
        .badge-case-rejected {
            background-color: #d32f2f;
            color: white;
        }
        .priority-high {
            color: #d32f2f;
            font-weight: 600;
        }
        .priority-medium {
            color: #ff9800;
            font-weight: 600;
        }
        .priority-low {
            color: #6c757d;
            font-weight: 600;
        }
        .filter-container {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-check-input:checked {
            background-color: var(--police-blue);
            border-color: var(--police-blue);
        }
        .form-check-label {
            font-weight: 500;
        }
        .btn-primary {
            background-color: var(--police-blue);
            border-color: var(--police-blue);
        }
        .btn-primary:hover {
            background-color: var(--police-dark-blue);
            border-color: var(--police-dark-blue);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-shield-lock" style="font-size: 2.5rem;"></i>
            </div>
            <h4>C-section</h4>
             <h6>Case Management System</h6>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#dashboard" id="dashboard-link">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#forwarded-cases" id="forwarded-cases-link">
                    <i class="bi bi-check-circle"></i> Forwarded Cases
                </a>
            </li>
        </ul>
        <div class="logout-btn">
            <form method="post" action="logout.php">
                <button type="submit" class="btn btn-outline-light btn-sm w-100">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="bi bi-person-badge"></i>C-section Dashboard</h1>
            <div class="text-muted">
                <i class="bi bi-calendar"></i> <span id="current-date"><?php echo date('F d, Y'); ?></span>
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
                            <h3><?php echo $forwarded_cases_count; ?></h3>
                            <p>Forwarded Cases</p>
                        </div>
                    </div>
                </div>
                <div class="filter-container">
                    <h5><i class="bi bi-funnel"></i> Filter Cases</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="spOfficeFilter" checked>
                                <label class="form-check-label" for="spOfficeFilter">From SP office</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="swFilter" checked>
                                <label class="form-check-label" for="swFilter">From social welfare dept.</label>
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
                            <?php foreach ($cases as $case): ?>
                            <?php echo '<!-- DEBUG: ' . print_r($case, true) . ' -->'; ?>
                            <tr class="case-row <?php echo ($case['status'] === 'c_section_review') ? 'sp-case' : 'sw-case'; ?>">
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['complainant_name']); ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars($case['police_station']); ?></td>
                                <td><span class="priority-<?php echo strtolower($case['priority']); ?>"><?php echo htmlspecialchars($case['priority']); ?></span></td>
                                <td>
                                    <?php 
                                        $status = $case['latest_status'] ?? $case['status'];
                                        $badgeClass = 'status-badge badge-case-new';
                                        if ($status === 'collector_approved') $badgeClass = 'status-badge badge-case-resolved';
                                        elseif ($status === 'collector_reverify') $badgeClass = 'status-badge badge-case-inprogress';
                                        elseif ($status === 'collector_rejected') $badgeClass = 'status-badge badge-case-rejected';
                                        elseif ($status === 'collector_allotted') $badgeClass = 'status-badge bg-primary text-white';
                                        elseif ($status === 'c_section_review') $badgeClass = 'status-badge badge-case-new';
                                        elseif ($status === 'from_social_welfare') $badgeClass = 'status-badge badge-case-inprogress';
                                        echo '<span class="' . $badgeClass . '">' . htmlspecialchars(ucwords(str_replace('_', ' ', $status))) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $assigned = $case['assigned_officer'] ?? $case['io_username'] ?? null;
                                        if (is_array($assigned)) {
                                            // If array, show first value or dash
                                            $assigned = reset($assigned) ?: '-';
                                        } elseif (!is_string($assigned)) {
                                            $assigned = '-';
                                        }
                                        echo htmlspecialchars($assigned ?: '-'); 
                                    ?>
                                </td>
                                <td>
                                    <?php if ($case['status'] === 'c_section_review'): ?>
                                        <a href="review_c_section_case.php?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> Review
                                        </a>
                                    <?php elseif ($case['status'] === 'from_social_welfare'): ?>
                                        <a href="review_c_section_from_swd_case.php?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> Review
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($cases)): ?>
                            <tr><td colspan="9" style="text-align:center; color:#888;">No cases found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Forwarded Cases -->
            <div class="tab-pane fade" id="forwarded-cases">
                <h3 class="section-title"><i class="bi bi-arrow-right-circle"></i> Forwarded Cases</h3>
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
                            <?php foreach ($forwarded_cases as $case): ?>
                            <?php
                                // Fetch latest collector action for this case
                                $collector_statuses = ['collector_approved', 'collector_reverify', 'collector_rejected', 'collector_allotted'];
                                $stmt = $pdo->prepare("SELECT status FROM case_status WHERE case_id = ? AND status IN ('collector_approved','collector_reverify','collector_rejected','collector_allotted') ORDER BY created_at DESC LIMIT 1");
                                $stmt->execute([$case['case_id']]);
                                $collector_status = $stmt->fetchColumn();
                                $display_status = $collector_status ? ucwords(str_replace('_', ' ', $collector_status)) : 'Forwarded to Collector';
                                $badgeClass = 'status-badge badge-case-resolved';
                                if ($collector_status === 'collector_reverify') $badgeClass = 'status-badge badge-case-inprogress';
                                elseif ($collector_status === 'collector_rejected') $badgeClass = 'status-badge badge-case-rejected';
                                elseif ($collector_status === 'collector_allotted') $badgeClass = 'status-badge bg-primary text-white';
                            ?>
                            <tr>
                                <td>SC/ST-<?php echo date('Y', strtotime($case['created_at'])); ?>-<?php echo str_pad($case['case_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($case['fir_number']); ?></td>
                                <td><?php echo htmlspecialchars($case['complainant_name']); ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td><?php echo htmlspecialchars($case['police_station']); ?></td>
                                <td><span class="priority-<?php echo strtolower($case['priority']); ?>"><?php echo htmlspecialchars($case['priority']); ?></span></td>
                                <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($display_status); ?></span></td>
                                <td>
                                    <?php 
                                        $assigned = $case['assigned_officer'] ?? $case['io_username'] ?? null;
                                        if (is_array($assigned)) {
                                            $assigned = reset($assigned) ?: '-';
                                        } elseif (!is_string($assigned)) {
                                            $assigned = '-';
                                        }
                                        echo htmlspecialchars($assigned ?: '-'); 
                                    ?>
                                </td>
                                <td>
                                    <a href="forwarded_case_view.php?case_id=<?php echo $case['case_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($forwarded_cases)): ?>
                            <tr><td colspan="9" style="text-align:center; color:#888;">No forwarded cases found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'Case forwarded to Collector!' && $modal_case): ?>
    <div id="success-modal" style="display:flex;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);align-items:center;justify-content:center;">
        <div style="background:white;padding:2rem 2.5rem;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.2);text-align:center;max-width:90vw;min-width:350px;">
            <h2 style="color:#2e7d32;margin-bottom:1rem;">Case Forwarded Successfully!</h2>
            <p style="font-size:1.1rem;">The case has been forwarded to the Collector.<br>All documents and information are now available for further action.</p>
            <div style="margin:1.5rem 0;text-align:left;">
                <strong>Case ID:</strong> SC/ST-<?php echo date('Y', strtotime($modal_case['created_at'])); ?>-<?php echo str_pad($modal_case['case_id'], 4, '0', STR_PAD_LEFT); ?><br>
                <strong>Victim Name:</strong> <?php echo htmlspecialchars($modal_case['victim_name']); ?><br>
                <strong>FIR Number:</strong> <?php echo htmlspecialchars($modal_case['fir_number']); ?><br>
                <strong>Complainant:</strong> <?php echo htmlspecialchars($modal_case['complainant_name']); ?><br>
            </div>
            <button id="close-modal-btn" class="btn btn-primary" style="margin-top:1.5rem;">Close</button>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('success-modal');
        var closeBtn = document.getElementById('close-modal-btn');
        if (closeBtn) {
            closeBtn.onclick = function() {
                modal.style.display = 'none';
                window.history.replaceState({}, document.title, window.location.pathname);
            };
        }
        setTimeout(function() {
            if (modal) {
                modal.style.display = 'none';
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }, 4000);
    });
    </script>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            function filterCases() {
                const showSP = $('#spOfficeFilter').is(':checked');
                const showSW = $('#swFilter').is(':checked');
                $('.sp-case').toggle(showSP);
                $('.sw-case').toggle(showSW);
                if (!showSP && !showSW) {
                    $('.case-row').show(); // Show all if both unchecked
                }
            }
            filterCases();
            $('#spOfficeFilter, #swFilter').change(filterCases);
        });
    </script>
    <script>
    // Make Forwarded Cases and Dashboard tabs act as pages: switch content without reload
    document.addEventListener('DOMContentLoaded', function() {
        var hash = window.location.hash;
        if (hash === '#forwarded-cases') {
            document.getElementById('dashboard').classList.remove('show', 'active');
            document.getElementById('forwarded-cases').classList.add('show', 'active');
        } else {
            document.getElementById('dashboard').classList.add('show', 'active');
            document.getElementById('forwarded-cases').classList.remove('show', 'active');
        }
        document.getElementById('dashboard-link').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('dashboard').classList.add('show', 'active');
            document.getElementById('forwarded-cases').classList.remove('show', 'active');
            window.location.hash = '#dashboard';
        });
        document.getElementById('forwarded-cases-link').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('dashboard').classList.remove('show', 'active');
            document.getElementById('forwarded-cases').classList.add('show', 'active');
            window.location.hash = '#forwarded-cases';
        });
    });
    </script>
</body>
</html> 
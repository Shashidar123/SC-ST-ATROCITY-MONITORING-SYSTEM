<?php
require_once 'includes/auth.php';
requireRole('dcr');
require_once 'includes/db.php';

// Fetch DCR cases for review
try {
    // Received today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $received_today = $stmt->fetchColumn();

    // Pending review (dcr_review)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status = 'dcr_review'");
    $stmt->execute();
    $pending_review = $stmt->fetchColumn();

    // Verified (verified)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status = 'verified'");
    $stmt->execute();
    $verified = $stmt->fetchColumn();

    // Forwarded (sp_review, ever)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT case_id) FROM case_status WHERE status = 'sp_review'");
    $stmt->execute();
    $forwarded = $stmt->fetchColumn();

    // For table display
    $stmt = $pdo->prepare("SELECT c.*, u.username as filed_by_name FROM cases c LEFT JOIN users u ON c.filed_by = u.id WHERE c.status = 'dcr_review' ORDER BY c.created_at DESC");
    $stmt->execute();
    $cases = $stmt->fetchAll();
} catch (PDOException $e) {
    $received_today = $pending_review = $verified = $forwarded = 0;
    $cases = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCR Dashboard -  District Crime Records </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --dcr-blue: #1565c0;
            --dcr-light: #e3f2fd;
            --dcr-dark-blue: #0d47a1;
        }
        body {
            padding: 20px;
            background-color: var(--dcr-light);
        }
        .stats-card {
            border-left: 4px solid var(--dcr-blue);
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .badge-status {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        .badge-new {
            background-color: #0d6efd;
        }
        .badge-verified {
            background-color: #198754;
        }
        .badge-pending {
            background-color: #fd7e14;
        }
        .badge-forwarded {
            background-color: #6610f2;
        }
        .verification-panel {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        .icon-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(21, 101, 192, 0.15);
            font-size: 2rem;
            margin-left: auto;
        }
        .bg-primary {
            background-color: var(--dcr-blue) !important;
        }
        .bg-warning {
            background-color: #ffc107 !important;
        }
        .bg-success {
            background-color: #198754 !important;
        }
        .bg-info {
            background-color: #0dcaf0 !important;
        }
        .icon-circle i {
            font-size: 2rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-archive-fill"></i> Crime Records Verification</h1>
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted">RECEIVED TODAY</h6>
                                <h3 id="received-cases"><?php echo $received_today; ?></h3>
                            </div>
                            <div class="icon-circle bg-primary text-white">
                                <i class="bi bi-inbox" style="font-size: 1.5rem;"></i>
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
                                <h6 class="text-muted">PENDING REVIEW</h6>
                                <h3 id="pending-cases"><?php echo $pending_review; ?></h3>
                            </div>
                            <div class="icon-circle bg-warning text-dark">
                                <i class="bi bi-clock" style="font-size: 1.5rem;"></i>
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
                                <h6 class="text-muted">VERIFIED</h6>
                                <h3 id="verified-cases"><?php echo $verified; ?></h3>
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
                                <h3 id="forwarded-cases"><?php echo $forwarded; ?></h3>
                            </div>
                            <div class="icon-circle bg-info text-white">
                                <i class="bi bi-send" style="font-size: 1.5rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white border-bottom-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-table"></i> Records for Review</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="recordsTable" class="table table-hover" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>FIR Number</th>
                            <th>Police Station</th>
                            <th>Received</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['fir_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($case['police_station'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($case['created_at']))); ?></td>
                            <td><span class="badge badge-status badge-pending">Pending Review</span></td>
                            <td>
                                <a href="review_dcr_case.php?case_id=<?php echo $case['case_id']; ?>" class="btn btn-primary btn-sm">Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Record Review Modal (to be implemented with JS) -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Review FIR Record</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="reviewContent">
                    <!-- Dynamic content will be loaded here via JS -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveReviewBtn">Save Review</button>
                    <button type="button" class="btn btn-success" id="forwardBtn">Forward to SP</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Modal (static for now) -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Records</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="new" id="filterNew" checked>
                            <label class="form-check-label" for="filterNew">New</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="pending" id="filterPending" checked>
                            <label class="form-check-label" for="filterPending">Pending</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="verified" id="filterVerified">
                            <label class="form-check-label" for="filterVerified">Verified</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="filterStation" class="form-label">Police Station</label>
                        <select class="form-select" id="filterStation">
                            <option value="">All Stations</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="applyFiltersBtn">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1' && isset($_GET['case_id'])): ?>
        <?php
        // Fetch minimal case details for the success message
        try {
            $stmt = $pdo->prepare("SELECT victim_name, fir_number, police_station FROM cases WHERE case_id = ?");
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
                    <strong>Police Station:</strong> <?php echo htmlspecialchars($case['police_station']); ?></p>
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
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#recordsTable').DataTable();
        // Review button click handler (to be implemented)
        $('.review-btn').on('click', function() {
            var caseId = $(this).data('case-id');
            // Load case details via AJAX and show modal (to be implemented)
            $('#reviewModal').modal('show');
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var modal = new bootstrap.Modal(document.getElementById('forwardSuccessModal'));
      modal.show();
      document.getElementById('forwardSuccessOkBtn').onclick = function() {
        window.location.href = 'dcr_dashboard.php';
      };
      // Also close on modal close (X)
      document.getElementById('forwardSuccessModal').addEventListener('hidden.bs.modal', function () {
        window.location.href = 'dcr_dashboard.php';
      });
    });
    </script>
</body>
</html> 
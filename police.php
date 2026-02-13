<?php
require_once 'includes/auth.php';
requireRole('police');
require_once 'includes/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get success/error messages if any
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Dashboard - SC/ST Case Management</title>
    <style>
        /* Police Department Styling */
        :root {
            --police-blue: #0a4a7a;
            --police-light: #e9f2f9;
            --police-red: #d32f2f;
            --police-white: #ffffff;
            --police-dark: #333333;
            --police-gray: #e0e0e0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--police-light);
            color: var(--police-dark);
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .case-form {
            background: var(--police-white);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-title {
            color: var(--police-blue);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--police-gray);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--police-dark);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--police-gray);
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-row {
            display: flex;
            gap: 1.5rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        .file-upload {
            border: 2px dashed var(--police-gray);
            padding: 1.5rem;
            text-align: center;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload:hover {
            border-color: var(--police-blue);
            background-color: var(--police-light);
        }
        .file-upload input {
            display: none;
        }
        .file-upload-label {
            display: block;
            cursor: pointer;
        }
        .file-upload-icon {
            font-size: 2rem;
            color: var(--police-blue);
            margin-bottom: 0.5rem;
        }
        .case-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background-color: var(--police-light);
            border-radius: 8px;
        }
        .submit-btn {
            background-color: var(--police-blue);
            color: var(--police-white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }
        .submit-btn:hover {
            background-color: #083b63;
        }
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        .cases-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .status-pending {
            color: #e67e22;
        }
        .status-approved {
            color: #27ae60;
        }
        .status-rejected {
            color: #c0392b;
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
        .nav-bar {
            background-color: #2c3e50;
            padding: 15px;
            margin-bottom: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-bar h2 {
            margin: 0;
        }
        .nav-bar .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-bar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            background-color: #3498db;
        }
        .nav-bar a:hover {
            background-color: #2980b9;
        }
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            color: #2c3e50;
            font-weight: bold;
        }
        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="nav-bar">
        <h2>Police Dashboard</h2>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="dashboard-container">
        <?php
        // Show flash message for successful case submission
        if (isset($_SESSION['case_success'])): ?>
            <div class="alert alert-success" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 1.1em; text-align: center;">
                <?php echo htmlspecialchars($_SESSION['case_success']); unset($_SESSION['case_success']); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php 
                if ($success == '1') {
                    echo "Case submitted successfully!";
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php
        // Get case statistics
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_cases,
                    SUM(CASE WHEN status = 'dcr_review' THEN 1 ELSE 0 END) as forwarded_cases
                FROM cases 
                WHERE filed_by = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $stats = $stmt->fetch();
            // For now, pending cases = total cases
            $stats['pending_cases'] = $stats['total_cases'];
        } catch(PDOException $e) {
            $stats = ['total_cases' => 0, 'pending_cases' => 0, 'forwarded_cases' => 0];
        }
        ?>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_cases'] ?? 0; ?></div>
                <div class="stat-label">Total Cases Filed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_cases'] ?? 0; ?></div>
                <div class="stat-label">Pending Cases</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['forwarded_cases'] ?? 0; ?></div>
                <div class="stat-label">Cases Forwarded to DCR</div>
            </div>
        </div>

        <!-- New Case Form -->
        <div class="case-form">
            <h2 class="section-title">File New SC/ST Atrocity Case</h2>
            <form action="handlers/submit_case.php" method="POST" enctype="multipart/form-data" id="police-case-form">
                <!-- Victim Basic Details -->
                <div class="case-section">
                    <h3 class="section-title">Victim Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="victim-name">Victim's Full Name*</label>
                            <input type="text" id="victim-name" name="victim_name" required>
                        </div>
                        <div class="form-group">
                            <label for="victim-age">Age*</label>
                            <input type="number" id="victim-age" name="victim_age" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="victim-gender">Gender*</label>
                            <select id="victim-gender" name="victim_gender" required>
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="transgender">Transgender</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="victim-caste">Caste Category*</label>
                            <select id="victim-caste" name="victim_caste" required>
                                <option value="">Select</option>
                                <option value="sc">Scheduled Caste (SC)</option>
                                <option value="st">Scheduled Tribe (ST)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="victim-address">Full Address*</label>
                        <textarea id="victim-address" name="victim_address" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="victim-contact">Contact Number*</label>
                            <input type="tel" id="victim-contact" name="victim_contact" required>
                        </div>
                        <div class="form-group">
                            <label for="victim-aadhaar">Aadhaar Number (if available)</label>
                            <input type="text" id="victim-aadhaar" name="victim_aadhaar">
                        </div>
                    </div>
                </div>
                <!-- FIR Details -->
                <div class="case-section">
                    <h3 class="section-title">FIR Registration</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fir-number">FIR Number*</label>
                            <input type="text" id="fir-number" name="fir_number" required>
                        </div>
                        <div class="form-group">
                            <label for="fir-date">FIR Registration Date*</label>
                            <input type="date" id="fir-date" name="fir_date" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="police-station">Police Station*</label>
                        <input type="text" id="police-station" name="police_station" required>
                    </div>
                    <div class="form-group">
                        <label>Upload FIR Copy (PDF/Image)*</label>
                        <div class="file-upload">
                            <input type="file" id="fir-upload" name="fir_upload" accept=".pdf,.jpg,.jpeg,.png" required>
                            <label for="fir-upload" class="file-upload-label">
                                <div class="file-upload-icon">📄</div>
                                <p>Click to upload FIR document</p>
                                <p>Max 5MB | PDF, JPG, PNG</p>
                            </label>
                        </div>
                    </div>
                </div>
                <!-- Victim's Statement (Verbatim) -->
                <div class="case-section">
                    <h3 class="section-title">Victim's Statement (Verbatim)</h3>
                    <div class="form-group">
                        <label for="victim-statement">Record Victim's Statement Word-for-Word*</label>
                        <textarea id="victim-statement" name="victim_statement" placeholder="Record the victim's exact words in their own language..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Upload Victim's Audio/Video Statement (Optional)</label>
                        <div class="file-upload">
                            <input type="file" id="statement-media" name="statement_media" accept=".mp3,.mp4,.wav">
                            <label for="statement-media" class="file-upload-label">
                                <div class="file-upload-icon">🎤</div>
                                <p>Click to upload audio/video recording</p>
                                <p>Max 10MB | MP3, MP4, WAV</p>
                            </label>
                        </div>
                    </div>
                </div>
                <!-- Medical Examination & Evidence -->
                <div class="case-section">
                    <h3 class="section-title">Medical Examination & Evidence</h3>
                    <div class="form-group">
                        <label for="medical-report">Medical Examination Report Summary</label>
                        <textarea id="medical-report" name="medical_report"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Upload Medical Reports</label>
                        <div class="file-upload">
                            <input type="file" id="medical-upload" name="medical_upload" accept=".pdf,.jpg,.jpeg,.png">
                            <label for="medical-upload" class="file-upload-label">
                                <div class="file-upload-icon">🏥</div>
                                <p>Click to upload medical documents</p>
                                <p>Max 5MB each</p>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Upload Other Evidence (Photos, Videos, etc.)</label>
                        <div class="file-upload">
                            <input type="file" id="evidence-upload" name="evidence_upload[]" accept=".jpg,.jpeg,.png,.mp4" multiple>
                            <label for="evidence-upload" class="file-upload-label">
                                <div class="file-upload-icon">🔍</div>
                                <p>Click to upload evidence files</p>
                                <p>Max 10MB total</p>
                            </label>
                        </div>
                    </div>
                </div>
                <!-- Sections Applied & SP Forwarding -->
                <div class="case-section">
                    <h3 class="section-title">Legal Sections & Forwarding</h3>
                    <div class="form-group">
                        <label for="case-sections">Sections Applied (IPC & SC/ST Act)*</label>
                        <textarea id="case-sections" name="case_sections" placeholder="Example: IPC 354, SC/ST Act Section 3(1)(x)..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="investigating-officer">Station House Officer</label>
                        <input type="text" id="investigating-officer" name="investigating_officer">
                    </div>
                </div>
                <!-- Priority Option (restored) -->
                <div class="form-section">
                    <div class="form-group">
                        <label for="priority">Priority*</label>
                        <select id="priority" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="submit-btn" id="submit-case">Submit Case</button>
            </form>
        </div>
        
        <!-- Recent Cases List -->
        <div class="cases-list">
            <h2>Recent Cases</h2>
            <?php
            try {
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                    (SELECT status FROM case_status WHERE case_id = c.case_id ORDER BY created_at DESC LIMIT 1) as current_status
                    FROM cases c 
                    WHERE c.filed_by = ? 
                    ORDER BY c.created_at DESC 
                    LIMIT 10
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $cases = $stmt->fetchAll();
                
                if (count($cases) > 0):
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Case ID</th>
                            <th>Victim Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Filed Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $case): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($case['case_id']); ?></td>
                                <td><?php echo htmlspecialchars($case['victim_name']); ?></td>
                                <td><?php echo htmlspecialchars($case['case_type']); ?></td>
                                <td class="status-<?php echo strtolower($case['current_status'] ?? 'pending'); ?>">
                                    <?php echo htmlspecialchars($case['current_status'] ?? 'Pending'); ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($case['created_at'])); ?></td>
                                <td>
                                    <a href="view_case.php?id=<?php echo $case['case_id']; ?>" class="btn">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No cases filed yet.</p>
            <?php endif;
            } catch(PDOException $e) {
                echo "<p>Error loading cases: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
    </div>

    <script>
    // Remove or comment out any JS that blocks form submission
    /*
    document.querySelector('form').addEventListener('submit', function(e) {
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    });
    */

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
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
    <script>
    // Show selected file name for each file input
    document.addEventListener('DOMContentLoaded', function() {
        function setupFileInput(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const label = input.parentElement.querySelector('.file-upload-label');
            let info = input.parentElement.querySelector('.file-info');
            if (!info) {
                info = document.createElement('div');
                info.className = 'file-info';
                info.style.marginTop = '8px';
                info.style.color = '#2c3e50';
                label.parentElement.appendChild(info);
            }
            input.addEventListener('change', function() {
                if (input.files && input.files.length > 0) {
                    let names = Array.from(input.files).map(f => f.name).join(', ');
                    info.textContent = 'Selected: ' + names + ' ✓';
                    info.style.color = '#27ae60';
                } else {
                    info.textContent = '';
                }
            });
        }
        setupFileInput('fir-upload');
        setupFileInput('medical-upload');
        setupFileInput('statement-media');
        setupFileInput('evidence-upload');
    });
    </script>
</body>
</html> 
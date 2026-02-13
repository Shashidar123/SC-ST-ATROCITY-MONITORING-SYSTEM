<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <title>Ananthapur Collector Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --police-blue: #0a4a7a;
            --police-light: #e9f2f9;
            --police-red: #d32f2f;
            --police-white: #ffffff;
            --police-dark: #333333;
            --police-gray: #e0e0e0;
        }
        body {
            background-color: var(--police-light);
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: var(--police-dark);
            margin: 0;
        }
        header {
            background-color: var(--police-blue);
            color: var(--police-white);
            padding: 1rem 0;
            text-align: center;
        }
        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
        }
        .logo {
            width: 40px;
            height: 40px;
            margin-bottom: 15px;
        }
        .police-badge {
            width: 50px;
            height: 50px;
            background-color: var(--police-white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--police-blue);
            font-weight: bold;
            border: 2px solid gold;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
            background-color: var(--police-white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .card {
            padding: 1rem;
            color: var(--police-white);
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .card h3 {
            margin: 0.5rem 0 0 0;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .col-md-3 {
            flex: 1;
            min-width: 200px;
        }
        footer {
            width: 100%;
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            left: 0;
        }
        
        footer p {
            margin: 0;
        }
        .bg-primary { background-color: var(--police-blue); }
        .bg-warning { background-color: #fbc02d; }
        .bg-success { background-color: #388e3c; }
        .bg-info { background-color: #0288d1; }
        .chart-card { background-color: var(--police-light); padding: 1rem; border-radius: 6px; }
        .chart-container {
            width: 300px;
            margin: 0 auto;
        }

        table.dataTable th, table.dataTable td {
            font-size: 0.9rem;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .row { flex-direction: column; }
        }
    </style>
</head>
<body>
<header>
    <div class="header-container">
        <div class="police-badge">AP</div>
        <div class="header-title">
            <h1>SC/ST Case Dashboard</h1>
            <p>Ananthapur District Collector | Social Welfare Monitoring</p>
        </div>
    </div>
</header>
<div class="container">
    <?php
    $total = $conn->query("SELECT COUNT(*) as c FROM cases WHERE district = 'Ananthapur'")->fetch_assoc()['c'];
    $pending = $conn->query("SELECT COUNT(*) as c FROM cases WHERE status = 'Under Investigation' AND district = 'Ananthapur'")->fetch_assoc()['c'];
    $verified = $conn->query("SELECT COUNT(*) as c FROM cases WHERE evidence_status = 'Fully Verified' AND district = 'Ananthapur'")->fetch_assoc()['c'];
    $paid = $conn->query("SELECT COUNT(*) as c FROM cases WHERE compensation_status = 'Disbursed' AND district = 'Ananthapur'")->fetch_assoc()['c'];
    ?>
    <div class="row">
        <div class="col-md-3"><div class="card bg-primary"><h5>Total Cases</h5><h3><?= $total ?></h3></div></div>
        <div class="col-md-3"><div class="card bg-warning"><h5>Pending</h5><h3><?= $pending ?></h3></div></div>
        <div class="col-md-3"><div class="card bg-success"><h5>Verified</h5><h3><?= $verified ?></h3></div></div>
        <div class="col-md-3"><div class="card bg-info"><h5>Compensated</h5><h3><?= $paid ?></h3></div></div>
    </div>
    <div class="chart-card mb-4">
        <h4>Case Status Breakdown</h4>
        <div class="chart-container">
            <canvas id="statusChart" width="80" height="60"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h4>Case Details</h4>
        <table id="casesTable" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Case ID</th>
                    <th>FIR No.</th>
                    <th>Victim Name</th>
                    <th>Caste</th>
                    <th>Incident Date</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Evidence Status</th>
                    <th>SP Remarks</th>
                    <th>Compensation Amount</th>
                    <th>Compensation Status</th>
                    <th>District</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $query = $conn->query("SELECT * FROM cases WHERE district = 'Ananthapur'");
            while ($row = $query->fetch_assoc()):
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['case_id'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['fir_number'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['victim_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['caste'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['incident_date'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['location'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['status'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['evidence_status'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['sp_remarks'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['compensation_amount'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['compensation_status'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['district'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['last_updated'] ?? '') ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
    $(document).ready(function () {
        $('#casesTable').DataTable({
            dom: 'Bfrtip',
            buttons: ['excelHtml5', 'pdfHtml5'],
            scrollX: true,
            scrollCollapse: true,
            fixedColumns: true
        });
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Verified', 'Compensated', 'Other'],
                datasets: [{
                    data: [<?= $pending ?>, <?= $verified ?>, <?= $paid ?>, <?= max(0, $total - $pending - $verified - $paid) ?>],
                    backgroundColor: ['#fbc02d', '#388e3c', '#0288d1', '#d32f2f']
                }]
            }
        });
    });
</script>
</body>
</html>
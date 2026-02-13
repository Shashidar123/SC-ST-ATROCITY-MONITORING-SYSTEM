<?php
require_once 'includes/auth.php';
requireRole('collector');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector Dashboard - Case Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <style>
        body { padding: 20px; background-color: #f8f9fa; }
        .logout-btn { position: absolute; top: 20px; right: 20px; }
        .stats-card { margin-bottom: 20px; transition: transform 0.3s; }
        .stats-card:hover { transform: translateY(-5px); }
        .dt-buttons { margin-bottom: 1rem; }
        .priority-high { background-color: #ffdddd !important; }
        .priority-medium { background-color: #fff3cd !important; }
        .badge-case-new { background-color: #0d6efd; }
        .badge-case-inprogress { background-color: #fd7e14; }
        .badge-case-resolved { background-color: #198754; }
        .badge-case-rejected { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Collector Dashboard - Case Management</h1>
        <a href="logout.php" class="btn btn-danger logout-btn">Logout</a>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Cases</h5>
                        <h3 id="total-count" class="card-text">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">New Cases</h5>
                        <h3 id="new-count" class="card-text">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">In Progress</h5>
                        <h3 id="inprogress-count" class="card-text">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Resolved</h5>
                        <h3 id="resolved-count" class="card-text">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-light">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="mb-0">Case Overview</h3>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table id="casesTable" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Case ID</th>
                            <th>FIR Number</th>
                            <th>Registration Date</th>
                            <th>Victim Name</th>
                            <th>Victim Contact</th>
                            <th>Case Type</th>
                            <th>Priority</th>
                            <th>Assigned Officer</th>
                            <th>Police Station</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5>Case Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5>Cases by Type</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="typeChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#casesTable').DataTable({
                ajax: {
                    url: 'handlers/get_collector_cases.php',
                    dataSrc: function(json) {
                        // Only show cases that should be in dashboard
                        return json.filter(row => row.show_in_dashboard);
                    }
                },
                columns: [
                    { data: 'case_id' },
                    { data: 'fir_number' },
                    { 
                        data: 'created_at',
                        render: function(data) {
                            return new Date(data).toLocaleDateString();
                        }
                    },
                    { data: 'victim_name' },
                    { data: 'victim_contact' },
                    { data: 'case_type' },
                    { 
                        data: 'priority',
                        render: function(data) {
                            let badgeClass = 'badge bg-secondary';
                            if (data === 'high') badgeClass = 'badge bg-danger';
                            if (data === 'medium') badgeClass = 'badge bg-warning text-dark';
                            if (data === 'low') badgeClass = 'badge bg-success';
                            return `<span class="${badgeClass}">${data}</span>`;
                        }
                    },
                    { data: 'assigned_officer' },
                    { data: 'police_station' },
                    { 
                        data: 'dashboard_status',
                        render: function(data) {
                            let badgeClass = 'badge badge-case-new';
                            let label = 'New';
                            if (data === 'inprogress') { badgeClass = 'badge badge-case-inprogress'; label = 'In Progress'; }
                            if (data === 'resolved') { badgeClass = 'badge badge-case-resolved'; label = 'Resolved'; }
                            if (data === 'rejected') { badgeClass = 'badge badge-case-rejected'; label = 'Rejected'; }
                            if (data === 'new') { badgeClass = 'badge badge-case-new'; label = 'New'; }
                            return `<span class="badge ${badgeClass}">${label}</span>`;
                        }
                    },
                    { 
                        data: 'last_updated',
                        render: function(data) {
                            return new Date(data).toLocaleString();
                        }
                    },
                    {
                        data: 'case_id',
                        render: function(data, type, row) {
                            return `<a href="review_collector_case.php?case_id=${data}" class="btn btn-sm btn-outline-primary">Review</a>`;
                        },
                        orderable: false
                    }
                ],
                createdRow: function(row, data, dataIndex) {
                    if (data.priority === 'high') {
                        $(row).addClass('priority-high');
                    } else if (data.priority === 'medium') {
                        $(row).addClass('priority-medium');
                    }
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Initialize charts
            const statusChartCtx = document.getElementById('statusChart').getContext('2d');
            const typeChartCtx = document.getElementById('typeChart').getContext('2d');
            
            let statusChart = new Chart(statusChartCtx, {
                type: 'doughnut',
                data: {
                    labels: ['New', 'In Progress', 'Resolved', 'Rejected'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: [
                            '#0d6efd',
                            '#fd7e14',
                            '#198754',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });

            let typeChart = new Chart(typeChartCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Cases by Type',
                        data: [],
                        backgroundColor: '#17a2b8'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Load stats and charts
            function loadStats() {
                $.get('handlers/get_stats.php', function(data) {
                    $('#total-count').text(data.total_cases);
                    $('#new-count').text(data.new_cases);
                    $('#inprogress-count').text(data.inprogress_cases);
                    $('#resolved-count').text(data.resolved_cases);
                    // Update status chart
                    statusChart.data.datasets[0].data = [
                        data.new_cases || 0, 
                        data.inprogress_cases || 0, 
                        data.resolved_cases || 0, 
                        data.rejected_cases || 0
                    ];
                    statusChart.update();
                });

                $.get('handlers/get_case_type_stats.php', function(data) {
                    const labels = data.map(item => item.case_type);
                    const counts = data.map(item => item.count);
                    typeChart.data.labels = labels;
                    typeChart.data.datasets[0].data = counts;
                    typeChart.update();
                });
            }

            loadStats();
        });
    </script>
</body>
</html> 
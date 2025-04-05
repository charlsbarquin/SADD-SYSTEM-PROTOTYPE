<?php include '../config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Automated Attendance System</title>

    <!-- Bootstrap & Custom Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <style>
        .stat-card {
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bg-primary-light {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .bg-success-light {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <!-- Error Alert (initially hidden) -->
        <div id="error-alert" class="alert alert-danger alert-dismissible fade show mb-4 d-none" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <span id="error-message"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <!-- Quick Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Total Professors</h6>
                                <h2 class="mb-0" id="total-professors">0</h2>
                            </div>
                            <div class="icon-circle bg-primary-light">
                                <i class="fas fa-users text-primary"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="professors.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Present Today</h6>
                                <h2 class="mb-0" id="present-today">0</h2>
                            </div>
                            <div class="icon-circle bg-success-light">
                                <i class="fas fa-user-check text-success"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="attendance-report.php?status=Present" class="btn btn-sm btn-outline-success">View</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Late Today</h6>
                                <h2 class="mb-0" id="late-today">0</h2>
                            </div>
                            <div class="icon-circle bg-warning-light">
                                <i class="fas fa-clock text-warning"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="attendance-report.php?status=Late" class="btn btn-sm btn-outline-warning">View</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-muted">Absent Today</h6>
                                <h2 class="mb-0" id="absent-today">0</h2>
                            </div>
                            <div class="icon-circle bg-danger-light">
                                <i class="fas fa-user-times text-danger"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="attendance-report.php?status=Absent" class="btn btn-sm btn-outline-danger">View</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card h-100 chart-container">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Weekly Attendance Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 chart-container">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Today's Status</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="attendancePieChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4">
            <div class="col-12">
                <div class="card chart-container">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            <a href="attendance-report.php" class="btn btn-success">
                                <i class="fas fa-file-export me-2"></i>Generate Report
                            </a>
                            <a href="settings.php" class="btn btn-secondary">
                                <i class="fas fa-cog me-2"></i>System Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Global chart references
        let barChart, pieChart;

        // Show error message
        function showError(message) {
            $('#error-message').text(message);
            $('#error-alert').removeClass('d-none');
        }

        // Hide error message
        function hideError() {
            $('#error-alert').addClass('d-none');
        }

        // Dashboard Data Fetch
        function fetchDashboardData() {
            $.ajax({
                url: "../api/dashboard-data.php",
                method: "GET",
                dataType: "json",
                success: function(response) {
                    hideError();

                    // Parse response data
                    const totalProfessors = parseInt(response.total_professors) || 0;
                    const presentToday = parseInt(response.today_attendance) || 0;
                    const lateToday = parseInt(response.late_entries) || 0;
                    const absentToday = Math.max(0, totalProfessors - presentToday - lateToday);

                    // Update Stats Cards
                    $("#total-professors").text(totalProfessors);
                    $("#present-today").text(presentToday);
                    $("#late-today").text(lateToday);
                    $("#absent-today").text(absentToday);

                    // Process chart data
                    const weeklyData = response.attendance_chart || {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        data: [0, 0, 0, 0, 0, 0, 0]
                    };

                    // Convert string numbers to integers for chart data
                    if (weeklyData.data && Array.isArray(weeklyData.data)) {
                        weeklyData.data = weeklyData.data.map(item => parseInt(item) || 0);
                    }

                    // Update Charts
                    updateBarChart(weeklyData);
                    updatePieChart({
                        labels: ['Present', 'Late', 'Absent'],
                        data: [presentToday, lateToday, absentToday]
                    });
                },
                error: function(xhr, status, error) {
                    showError("Failed to load dashboard data. Please try again.");
                    console.error("Error:", error);

                    // Initialize charts with empty data
                    updateBarChart({
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        data: [0, 0, 0, 0, 0, 0, 0]
                    });

                    updatePieChart({
                        labels: ['Present', 'Late', 'Absent'],
                        data: [0, 0, 0]
                    });
                }
            });
        }

        // Bar Chart (Weekly Trend)
        function updateBarChart(chartData) {
            const ctx = document.getElementById('attendanceChart').getContext('2d');

            if (barChart) barChart.destroy();

            barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Attendance Count',
                        data: chartData.data,
                        backgroundColor: 'rgba(67, 97, 238, 0.7)',
                        borderRadius: 4,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 12
                            },
                            padding: 12,
                            cornerRadius: 4,
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.y} attendance`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Pie Chart (Status Distribution)
        function updatePieChart(chartData) {
            const ctx = document.getElementById('attendancePieChart').getContext('2d');

            if (pieChart) pieChart.destroy();

            pieChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        data: chartData.data,
                        backgroundColor: [
                            'rgba(25, 135, 84, 0.8)', // Present
                            'rgba(255, 193, 7, 0.8)', // Late
                            'rgba(220, 53, 69, 0.8)' // Absent
                        ],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            bodyFont: {
                                size: 12
                            },
                            padding: 12,
                            cornerRadius: 4,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                    return `${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize Dashboard
        $(document).ready(function() {
            // Initialize charts with empty data first
            updateBarChart({
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                data: [0, 0, 0, 0, 0, 0, 0]
            });

            updatePieChart({
                labels: ['Present', 'Late', 'Absent'],
                data: [0, 0, 0]
            });

            // Then fetch real data
            fetchDashboardData();

            // Refresh data every 30 seconds
            setInterval(fetchDashboardData, 30000);
        });
    </script>
    <!-- Bootstrap & Popper.js (Required for Dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
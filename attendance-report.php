<?php
include '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report Dashboard</title>

    <!-- Bootstrap & Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/attendance-report.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="main-container">
            <!-- Header Section -->
            <div class="page-header">
                <h1><i class="fas fa-clipboard-list me-2"></i>Attendance Report Dashboard</h1>
                <p class="text-muted">Comprehensive view and management of attendance records</p>
            </div>

            <!-- Filters Section -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-sliders-h me-2"></i>Filter Records</h2>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control form-control-sm" id="start-date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control form-control-sm" id="end-date">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" id="status-filter">
                            <option value="">All Status</option>
                            <option value="Present">Present</option>
                            <option value="Late">Late</option>
                            <option value="Absent">Absent</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" id="apply-filters">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-table me-2"></i>Attendance Records</h2>
                </div>
                <div class="table-responsive" style="max-height: 600px;">
                    <table class="table" id="attendance-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Duration</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT a.*, p.name 
                                    FROM attendance a
                                    JOIN professors p ON a.professor_id = p.id
                                    ORDER BY a.checkin_date DESC";
                            $result = $conn->query($sql);

                            while ($row = $result->fetch_assoc()) {
                                // Extract time in 12-hour format with AM/PM
                                $timeIn = $row['check_in'] ? date("h:i A", strtotime($row['check_in'])) : '';
                                $timeOut = $row['check_out'] ? date("h:i A", strtotime($row['check_out'])) : "<span class='badge badge-pending'>Pending</span>";

                                $statusClass = '';
                                switch ($row['status']) {
                                    case 'Present':
                                        $statusClass = 'badge-present';
                                        break;
                                    case 'Late':
                                        $statusClass = 'badge-late';
                                        break;
                                    case 'Absent':
                                        $statusClass = 'badge-absent';
                                        break;
                                    default:
                                        $statusClass = 'badge-pending';
                                }

                                echo "<tr>
                                    <td class='fw-semibold'>{$row['name']}</td>
                                    <td>{$timeIn}</td>
                                    <td>{$timeOut}</td>
                                    <td>{$row['work_duration']}</td>
                                    <td>{$row['checkin_date']}</td>
                                    <td><span class='badge $statusClass'>{$row['status']}</span></td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#attendance-table').DataTable({
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "All"]
                ],
                pageLength: 5,
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12'i>>" +
                    "<'row'<'col-sm-6'B><'col-sm-6'p>>",
                buttons: [{
                        extend: 'copy',
                        className: 'btn btn-sm btn-secondary',
                        text: '<i class="fas fa-copy"></i> Copy'
                    },
                    {
                        extend: 'csv',
                        className: 'btn btn-sm btn-secondary',
                        text: '<i class="fas fa-file-csv"></i> CSV'
                    },
                    {
                        extend: 'excel',
                        className: 'btn btn-sm btn-secondary',
                        text: '<i class="fas fa-file-excel"></i> Excel'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm btn-secondary',
                        text: '<i class="fas fa-file-pdf"></i> PDF'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-secondary',
                        text: '<i class="fas fa-print"></i> Print'
                    }
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                initComplete: function() {
                    let searchContainer = $('.dataTables_filter');
                    let searchInput = searchContainer.find('input');

                    // Wrap input with a div for styling
                    searchContainer.html(`
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    ${searchInput.prop('outerHTML')}
                </div>
            `);

                    // Select the new input and adjust styling
                    let newSearchInput = searchContainer.find('input');
                    newSearchInput.addClass('form-control')
                        .css({
                            "width": "280px",
                            "height": "40px",
                            "font-size": "16px",
                            "border-left": "0"
                        });

                    // Add search event listener
                    newSearchInput.on('keyup', function() {
                        table.search(this.value).draw(); // Enables search functionality
                    });
                }
            });

            // Apply filters
            $('#apply-filters').click(function() {
                var startDate = $('#start-date').val();
                var endDate = $('#end-date').val();
                var status = $('#status-filter').val();

                table.columns(5).search(status).draw(); // Status column

                if (startDate || endDate) {
                    // Filter by date range (column 4 is the date column)
                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                        var rowDate = new Date(data[4]);
                        var min = startDate ? new Date(startDate) : null;
                        var max = endDate ? new Date(endDate) : null;

                        if ((min === null || rowDate >= min) &&
                            (max === null || rowDate <= max)) {
                            return true;
                        }
                        return false;
                    });

                    table.draw();
                    $.fn.dataTable.ext.search.pop();
                } else {
                    table.draw();
                }
            });
        });
    </script>
    <!-- Bootstrap & Popper.js (Required for Dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
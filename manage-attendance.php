<?php
session_start();
require_once '../config/database.php';

// Authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

// Get filter parameters
$date_filter = $_GET['date'] ?? '';
$professor_filter = $_GET['professor'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Build base query
// Replace your current query with this:
$query = "SELECT 
            a.id as attendance_id, 
            p.id as professor_db_id,
            a.professor_id,
            a.check_in, 
            a.check_out, 
            a.status,
            p.name as professor_name, 
            p.department, 
            p.designation, 
            p.profile_image,
            TIMESTAMPDIFF(MINUTE, a.check_in, a.check_out) as duration_minutes
          FROM attendance a
          JOIN professors p ON a.professor_id = p.id";

// Apply filters
$where = [];
$params = [];
$types = '';

if (!empty($date_filter)) {
    $where[] = "DATE(a.check_in) = ?";
    $params[] = $date_filter;
    $types .= 's';
}

if ($professor_filter !== 'all') {
    $where[] = "a.professor_id = ?";
    $params[] = $professor_filter;
    $types .= 'i';
}

if ($status_filter !== 'all') {
    $where[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY a.check_in DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$attendance = $stmt->get_result();

// Get professors for filter dropdown
$professors = $conn->query("SELECT id, name FROM professors ORDER BY name");

// Get counts for dashboard
$total_attendance = $conn->query("SELECT COUNT(*) FROM attendance")->fetch_row()[0];
$today_attendance = $conn->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetch_row()[0];
$pending_checkouts = $conn->query("SELECT COUNT(*) FROM attendance WHERE check_out IS NULL")->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance | Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Match manage-users.php styling exactly */
        .table {
            --bs-table-bg: transparent;
            --bs-table-striped-bg: rgba(0, 0, 0, 0.02);
            --bs-table-hover-bg: rgba(0, 0, 0, 0.03);
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom-width: 1px;
            border-top: none;
        }

        .table td {
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(var(--primary-rgb), 0.03);
        }

        .status-badge {
            font-weight: 500;
            letter-spacing: 0.5px;
            padding: 0.35em 0.65em;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-img-sm {
            width: 35px;
            height: 35px;
            object-fit: cover;
            border-radius: 50%;
        }

        .duration-badge {
            font-size: 0.75rem;
            padding: 0.25em 0.5em;
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        /* Export buttons styling */
        .dt-buttons .btn {
            border-radius: 4px !important;
            margin-right: 5px;
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        .dt-buttons .btn i {
            margin-right: 3px;
        }

        /* Specific button colors */
        .dt-buttons .btn.buttons-excel {
            background-color: #198754;
            color: white;
            border: none;
        }

        .dt-buttons .btn.buttons-pdf {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .dt-buttons .btn.buttons-csv {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .dt-buttons .btn.buttons-print {
            background-color: #0dcaf0;
            color: white;
            border: none;
        }
    </style>
</head>

<body>
    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="container-fluid py-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1 fw-bold">Attendance Management</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home me-1"></i> Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-calendar-check me-1"></i> Attendance</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="add-attendance.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Add Record
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stats-card border-start border-primary border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Total Records</h6>
                                    <h3 class="mb-0"><?= $total_attendance ?></h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-calendar-alt text-primary fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card border-start border-success border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Today's Records</h6>
                                    <h3 class="mb-0"><?= $today_attendance ?></h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-clock text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stats-card border-start border-warning border-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Pending Checkouts</h6>
                                    <h3 class="mb-0"><?= $pending_checkouts ?></h3>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card mb-4">
                <div class="card-body p-2">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
                        </div>

                        <div class="col-md-3">
                            <label for="professor" class="form-label">Professor</label>
                            <select class="form-select" id="professor" name="professor">
                                <option value="all">All Professors</option>
                                <?php while ($prof = $professors->fetch_assoc()): ?>
                                    <option value="<?= $prof['id'] ?>" <?= $professor_filter == $prof['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prof['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                                <option value="present" <?= $status_filter === 'present' ? 'selected' : '' ?>>Present</option>
                                <option value="late" <?= $status_filter === 'late' ? 'selected' : '' ?>>Late</option>
                                <option value="absent" <?= $status_filter === 'absent' ? 'selected' : '' ?>>Absent</option>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Apply Filters
                            </button>
                            <a href="manage-attendance.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sync me-1"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attendance Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="attendanceTable" class="table table-striped table-hover" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Professor</th>
                                    <th>Department</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while ($record = $attendance->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $record['professor_db_id'] ?></td> <!-- Displaying the actual professor ID -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($record['profile_image'])): ?>
                                                    <img src="../uploads/professors/<?= htmlspecialchars($record['profile_image']) ?>"
                                                        class="profile-img-sm me-3" alt="<?= htmlspecialchars($record['professor_name']) ?>">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($record['professor_name']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($record['designation']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($record['department']) ?></td>
                                        <td><?= date('M j, Y h:i A', strtotime($record['check_in'])) ?></td>
                                        <td>
                                            <?= $record['check_out'] ? date('M j, Y h:i A', strtotime($record['check_out'])) : '<span class="badge rounded-pill bg-secondary">Pending</span>' ?>
                                        </td>
                                        <td>
                                            <?php if ($record['check_out']): ?>
                                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= floor($record['duration_minutes'] / 60) ?>h <?= $record['duration_minutes'] % 60 ?>m
                                                </span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary">
                                                    <i class="fas fa-minus me-1"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = strtolower($record['status']);
                                            $badge_class = 'bg-success'; // Default to present (green)
                                            $status_text = 'Present';

                                            if ($status === 'late') {
                                                $badge_class = 'bg-warning text-dark';
                                                $status_text = 'Late';
                                            } elseif ($status === 'absent') {
                                                $badge_class = 'bg-danger';
                                                $status_text = 'Absent';
                                            }
                                            ?>
                                            <span class="badge rounded-pill <?= $badge_class ?>">
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-end gap-1">
                                                <!-- Edit Button -->
                                                <a href="edit-attendance.php?id=<?= $record['attendance_id'] ?>"
                                                    class="btn btn-sm btn-icon btn-primary rounded-circle"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <!-- Delete Button -->
                                                <button class="btn btn-sm btn-icon btn-danger rounded-circle delete-btn"
                                                    data-id="<?= $record['attendance_id'] ?>"
                                                    title="Delete"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteAttendanceModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                                <?php if (!$record['check_out']): ?>
                                                    <!-- Checkout Button (only shown if not checked out) -->
                                                    <button class="btn btn-sm btn-icon btn-success rounded-circle checkout-btn"
                                                        data-id="<?= $record['attendance_id'] ?>"
                                                        title="Check Out">
                                                        <i class="fas fa-sign-out-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Check Out Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Check Out Professor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to check out this professor?</p>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="checkoutStatus">
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmCheckout" class="btn btn-success">Check Out</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Attendance Modal -->
    <div class="modal fade" id="deleteAttendanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this attendance record? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteAttendance" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            const table = $('#attendanceTable').DataTable({
                responsive: true,
                dom: '<"top"Bf>rt<"bottom"lip><"clear">',
                buttons: [{
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel me-1"></i> Excel',
                        className: 'btn btn-sm buttons-excel',
                        title: 'Attendance Records',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fas fa-file-pdf me-1"></i> PDF',
                        className: 'btn btn-sm buttons-pdf',
                        title: 'Attendance Records',
                        exportOptions: {
                            columns: ':visible'
                        },
                        customize: function(doc) {
                            doc.defaultStyle.fontSize = 8;
                            doc.styles.tableHeader.fontSize = 9;
                        }
                    },
                    {
                        extend: 'csvHtml5',
                        text: '<i class="fas fa-file-csv me-1"></i> CSV',
                        className: 'btn btn-sm buttons-csv',
                        title: 'Attendance Records',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print me-1"></i> Print',
                        className: 'btn btn-sm buttons-print',
                        title: 'Attendance Records',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ],
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                columnDefs: [{
                        responsivePriority: 1,
                        targets: 1
                    }, // Professor
                    {
                        responsivePriority: 2,
                        targets: 7
                    }, // Actions
                    {
                        responsivePriority: 3,
                        targets: 3
                    }, // Check-In
                    {
                        responsivePriority: 4,
                        targets: 0
                    } // ID
                ]
            });

            // Track current IDs for actions
            let currentRecordId = null;
            let currentDeleteId = null;

            // Checkout button handler
            $(document).on('click', '.checkout-btn', function() {
                currentRecordId = $(this).data('id');
                $('#checkoutModal').modal('show');
            });

            // Delete button handler - only shows modal
            $(document).on('click', '.delete-btn', function() {
                currentDeleteId = $(this).data('id');
                $('#deleteAttendanceModal').modal('show');
            });

            // Confirm delete action
            $('#confirmDeleteAttendance').on('click', function() {
                if (!currentDeleteId) return;

                const button = $(this);
                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');

                $.ajax({
                    url: 'delete-attendance.php',
                    type: 'POST',
                    data: {
                        id: currentDeleteId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#deleteAttendanceModal').modal('hide');
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Attendance record deleted successfully',
                                icon: 'success'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error!', response.message || 'Failed to delete record', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to delete record. Please try again.', 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false).html('Delete');
                    }
                });
            });

            // Confirm checkout action
            $('#confirmCheckout').on('click', function() {
                const status = $('#checkoutStatus').val();
                const button = $(this);
                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

                $.ajax({
                    url: 'checkout-attendance.php',
                    type: 'POST',
                    data: {
                        id: currentRecordId,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#checkoutModal').modal('hide');
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Professor checked out successfully',
                                icon: 'success'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error!', response.message || 'Failed to check out', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to check out. Please try again.', 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false).html('Check Out');
                    }
                });
            });
        });
    </script>
</body>

</html>
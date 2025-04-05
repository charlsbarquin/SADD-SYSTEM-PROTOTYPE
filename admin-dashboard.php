<?php include '../config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Bootstrap & Custom Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> 

    <!-- jQuery & DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
</head>

<body>
    <div class="container mt-4">
        <h1 class="text-center" style="color: #72A0C1;">🛠 Admin Dashboard</h1>

        <!-- Dashboard Overview -->
        <div class="row text-center my-4">
            <div class="col-md-4">
                <div class="card p-3 bg-primary text-white">
                    <h4>Total Professors</h4>
                    <h2>
                        <?php 
                        $result = $conn->query("SELECT COUNT(*) AS total FROM professors");
                        echo $result->fetch_assoc()['total'];
                        ?>
                    </h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 bg-success text-white">
                    <h4>Total Attendance</h4>
                    <h2>
                        <?php 
                        $result = $conn->query("SELECT COUNT(*) AS total FROM attendance");
                        echo $result->fetch_assoc()['total'];
                        ?>
                    </h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 bg-warning text-white">
                    <h4>Late Entries</h4>
                    <h2>
                        <?php 
                        $result = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE status='Late'");
                        echo $result->fetch_assoc()['total'];
                        ?>
                    </h2>
                </div>
            </div>
        </div>

        <!-- Manage Professors -->
        <h3 class="mt-4">🏫 Manage Professors</h3>
        <button class="btn btn-success mb-2" onclick="openAddProfessor()">➕ Add Professor</button>
        <table class="table table-bordered" id="professors-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT id, name FROM professors ORDER BY name ASC");
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['name']}</td>
                            <td>
                                <button class='btn btn-warning btn-sm' onclick='editProfessor({$row['id']})'>✏️ Edit</button>
                                <button class='btn btn-danger btn-sm' onclick='deleteProfessor({$row['id']})'>❌ Delete</button>
                            </td>
                          </tr>";
                }
                $stmt->close();
                ?>
            </tbody>
        </table>

        <!-- Manage Attendance -->
        <h3 class="mt-4">🕒 Manage Attendance Records</h3>
        <table class="table table-bordered" id="attendance-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Work Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT a.*, p.name FROM attendance a JOIN professors p ON a.professor_id = p.id ORDER BY a.checkin_date DESC");
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $statusBadge = $row['status'] === 'Late' ? 'badge bg-danger' : 'badge bg-success';
                    $checkOut = $row['check_out'] ? $row['check_out'] : "<span class='text-danger'><i class='fas fa-exclamation-circle'></i> Pending</span>";

                    echo "<tr>
                            <td>{$row['name']}</td>
                            <td>{$row['check_in']}</td>
                            <td>{$checkOut}</td>
                            <td>{$row['work_duration']}</td>
                            <td><span class='{$statusBadge}'>{$row['status']}</span></td>
                            <td>
                                <button class='btn btn-danger btn-sm' onclick='deleteAttendance({$row['id']})'>🗑️ Delete</button>
                            </td>
                          </tr>";
                }
                $stmt->close();
                ?>
            </tbody>
        </table>

        <!-- Export Data -->
        <div class="mt-3">
            <button class="btn btn-success" onclick="exportCSV()">📥 Export CSV</button>
            <button class="btn btn-danger" onclick="exportPDF()">📥 Export PDF</button>
        </div>
    </div>

    <script>
        function deleteProfessor(id) {
            if (confirm("Are you sure you want to delete this professor?")) {
                $.ajax({
                    url: "../api/delete-professor.php",
                    type: "POST",
                    data: { id: id },
                    success: function(response) {
                        alert(response.message);
                        location.reload();
                    }
                });
            }
        }

        function deleteAttendance(id) {
            if (confirm("Are you sure you want to delete this attendance record?")) {
                $.ajax({
                    url: "../api/delete-attendance.php",
                    type: "POST",
                    data: { id: id },
                    success: function(response) {
                        alert(response.message);
                        location.reload();
                    }
                });
            }
        }

        function exportCSV() {
            window.location.href = "../api/export-csv.php";
        }

        function exportPDF() {
            window.location.href = "../api/export-pdf.php";
        }

        $(document).ready(function () {
            $("#professors-table, #attendance-table").DataTable();
        });
    </script>
</body>
</html>

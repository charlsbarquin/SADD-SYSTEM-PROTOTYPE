<?php
session_start();
require_once '../config/database.php';

// Authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

// Get attendance record ID
$attendance_id = $_GET['id'] ?? null;

if (!$attendance_id) {
    header('Location: manage-attendance.php');
    exit;
}

// Fetch the attendance record
$stmt = $conn->prepare("SELECT a.*, p.name as professor_name 
                       FROM attendance a
                       JOIN professors p ON a.professor_id = p.id
                       WHERE a.id = ?");
$stmt->bind_param('i', $attendance_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    header('Location: manage-attendance.php');
    exit;
}

// Fetch all professors for dropdown
$professors = $conn->query("SELECT id, name FROM professors ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $professor_id = $_POST['professor_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $status = $_POST['status'];

    // Validate inputs
    $errors = [];

    if (empty($professor_id)) {
        $errors[] = "Professor is required";
    }

    if (empty($check_in)) {
        $errors[] = "Check-in time is required";
    }

    if (!empty($check_out) && strtotime($check_out) < strtotime($check_in)) {
        $errors[] = "Check-out time cannot be before check-in time";
    }

    if (empty($errors)) {
        // Prepare the query based on whether check_out has a value
        if (!empty($check_out)) {
            $stmt = $conn->prepare("UPDATE attendance 
                                  SET professor_id = ?, 
                                      check_in = ?, 
                                      check_out = ?, 
                                      status = ?
                                  WHERE id = ?");
            $stmt->bind_param(
                'isssi',
                $professor_id,
                $check_in,
                $check_out,
                $status,
                $attendance_id
            );
        } else {
            $stmt = $conn->prepare("UPDATE attendance 
                                  SET professor_id = ?, 
                                      check_in = ?, 
                                      check_out = NULL, 
                                      status = ?
                                  WHERE id = ?");
            $stmt->bind_param(
                'issi',
                $professor_id,
                $check_in,
                $status,
                $attendance_id
            );
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Attendance record updated successfully";
            header("Location: manage-attendance.php");
            exit;
        } else {
            $errors[] = "Error updating record: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendance | Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .time-input-group {
            position: relative;
        }

        .time-input-group .form-control {
            padding-left: 2.5rem;
        }

        .time-input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
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
                    <h2 class="mb-1 fw-bold">Edit Attendance Record</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home me-1"></i> Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="manage-attendance.php"><i class="fas fa-calendar-check me-1"></i> Attendance</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Record</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Form Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Record for <?= htmlspecialchars($record['professor_name']) ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="professor_id" class="form-label">Professor</label>
                                <select class="form-select" id="professor_id" name="professor_id" required>
                                    <option value="">Select Professor</option>
                                    <?php while ($professor = $professors->fetch_assoc()): ?>
                                        <option value="<?= $professor['id'] ?>"
                                            <?= $professor['id'] == $record['professor_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($professor['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="present" <?= $record['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                                    <option value="late" <?= $record['status'] === 'late' ? 'selected' : '' ?>>Late</option>
                                    <option value="absent" <?= $record['status'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="check_in" class="form-label">Check-In</label>
                                <div class="time-input-group">
                                    <i class="fas fa-clock"></i>
                                    <input type="datetime-local" class="form-control" id="check_in" name="check_in"
                                        value="<?= date('Y-m-d\TH:i', strtotime($record['check_in'])) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="check_out" class="form-label">Check-Out</label>
                                <div class="time-input-group">
                                    <i class="fas fa-clock"></i>
                                    <input type="datetime-local" class="form-control" id="check_out" name="check_out"
                                        value="<?= $record['check_out'] ? date('Y-m-d\TH:i', strtotime($record['check_out'])) : '' ?>">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="manage-attendance.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum check-out time based on check-in time
        document.getElementById('check_in').addEventListener('change', function() {
            const checkOutField = document.getElementById('check_out');
            checkOutField.min = this.value;

            if (checkOutField.value && checkOutField.value < this.value) {
                checkOutField.value = '';
            }
        });
    </script>
</body>

</html>
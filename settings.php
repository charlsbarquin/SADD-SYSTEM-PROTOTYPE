<?php
// Start session at the very top
session_start();

include '../config/database.php';

// Initialize user role with default value
$_SESSION['user_role'] = $_SESSION['user_role'] ?? 'user';

// Fetch current settings with proper null checking
$settings = [
    'late_cutoff' => '08:00:00',
    'timezone' => 'Asia/Manila',
    'allow_auto_timeout' => 1
];

$sql = "SELECT * FROM settings WHERE id = 1";
$result = $conn->query($sql);

// Only merge if we got results
if ($result && $result->num_rows > 0) {
    $db_settings = $result->fetch_assoc();
    if ($db_settings) {
        $settings = array_merge($settings, $db_settings);
    }
}

// Handle settings update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_settings'])) {
    $late_cutoff = trim($_POST['late_cutoff'] ?? '08:00:00');
    $timezone = trim($_POST['timezone'] ?? 'Asia/Manila');
    $allow_auto_timeout = isset($_POST['allow_auto_timeout']) ? 1 : 0;

    $update_sql = "UPDATE settings SET late_cutoff = ?, timezone = ?, allow_auto_timeout = ? WHERE id = 1";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $late_cutoff, $timezone, $allow_auto_timeout);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Settings updated successfully!";
        header("Location: settings.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Error updating settings: " . $conn->error;
    }
}

// Handle Reset Attendance Data (Admin only) with proper session check
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_attendance']) && 
    isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM attendance");
        $conn->query("ALTER TABLE attendance AUTO_INCREMENT = 1");
        $conn->commit();
        $_SESSION['success_message'] = "Attendance data reset successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error resetting attendance: " . $e->getMessage();
    }
    header("Location: settings.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Bicol University Polangui</title>
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-content-container">
        <div class="content-wrapper">
            <!-- Header Container -->
            <div class="header-container">
                <div class="page-header">
                    <h2 class="page-title"><i class="fas fa-cog me-2"></i>System Settings</h2>
                    <p class="page-subtitle">Configure system preferences and attendance rules</p>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['success_message'])) : ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])) : ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Attendance Settings -->
            <div class="settings-card">
                <div class="card-title">
                    <i class="fas fa-clock"></i>Attendance Settings
                </div>
                
                <form method="POST">
                    <!-- Late Cutoff -->
                    <div class="mb-4">
                        <label class="form-label">Late Cutoff Time</label>
                        <input type="time" class="form-control" name="late_cutoff" 
                               value="<?= htmlspecialchars($settings['late_cutoff']); ?>" required
                               oninput="updateLateCutoffPreview()">
                        <div class="time-preview">
                            Employees will be marked late if they check in after: 
                            <strong id="lateCutoffPreview"><?= htmlspecialchars($settings['late_cutoff']); ?></strong>
                        </div>
                    </div>

                    <!-- Timezone -->
                    <div class="mb-4">
                        <label class="form-label">System Timezone</label>
                        <select class="form-select" name="timezone" required>
                            <option value="Asia/Manila" <?= $settings['timezone'] === 'Asia/Manila' ? 'selected' : '' ?>>Asia/Manila (UTC+8)</option>
                            <option value="UTC" <?= $settings['timezone'] === 'UTC' ? 'selected' : '' ?>>UTC</option>
                            <option value="America/New_York" <?= $settings['timezone'] === 'America/New_York' ? 'selected' : '' ?>>America/New York (UTC-5/-4)</option>
                            <option value="Europe/London" <?= $settings['timezone'] === 'Europe/London' ? 'selected' : '' ?>>Europe/London (UTC+0/+1)</option>
                        </select>
                    </div>

                    <!-- Auto Timeout -->
                    <div class="mb-4 form-check">
                        <input class="form-check-input" type="checkbox" name="allow_auto_timeout" 
                            <?= $settings['allow_auto_timeout'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Enable Automatic Time-Out</label>
                        <div class="form-text text-muted">
                            Automatically logs out employees who forget to time out at the end of the day.
                        </div>
                    </div>

                    <button type="submit" name="save_settings" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Settings
                    </button>
                </form>
            </div>

            <!-- Admin Section -->
            <?php if ($_SESSION['user_role'] === 'admin') : ?>
            <div class="settings-card danger-zone mt-5">
                <div class="card-title">
                    <i class="fas fa-exclamation-triangle"></i>Administrator Actions
                </div>
                
                <div class="warning-note">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> These actions are irreversible and will permanently affect system data.
                </div>

                <form method="POST" id="resetForm">
                    <button type="submit" name="reset_attendance" class="btn btn-outline-danger">
                        <i class="fas fa-trash-alt me-2"></i>Reset All Attendance Data
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update time preview
        function updateLateCutoffPreview() {
            document.getElementById('lateCutoffPreview').textContent = 
                document.querySelector("input[name='late_cutoff']").value;
        }

        // Confirm reset action
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            if (!confirm('⚠️ WARNING: This will permanently delete ALL attendance records. Continue?')) {
                e.preventDefault();
            }
        });
    </script>
    <!-- Bootstrap & Popper.js (Required for Dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
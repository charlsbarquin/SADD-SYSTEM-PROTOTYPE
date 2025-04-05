<?php include '../config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Automated Time In/Out</title>

    <!-- Bootstrap & Custom Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <!-- WebcamJS & jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/index.js"></script>
</head>

<?php include '../includes/navbar.php'; ?>

<body>
    <!-- Time In Modal -->
    <div class="modal fade" id="timeInModal" tabindex="-1" aria-labelledby="timeInModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #0077b6;">
                    <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i> Time In</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="search-container mb-3">
                        <input type="text" id="search-professor-in" class="form-control" placeholder="🔍 Search professor...">
                    </div>
                    <ul class="list-group mt-2 professor-list" id="professor-list-in">
                        <!-- Dynamic list will be populated here -->
                    </ul>
                    <!-- Camera Section -->
                    <div id="camera-section" class="mt-3 hidden">
                        <div class="camera-header d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0"><i class="fas fa-camera me-2"></i>Face Capture</h6>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div id="camera" style="width: 320px; height: 240px;"></div>
                        </div>
                        <button id="take-photo" class="btn btn-primary w-100 mt-2">
                            <i class="fas fa-camera me-2"></i> Capture
                        </button>
                    </div>
                    <!-- Location Status -->
                    <div id="location-status" class="mt-2 small text-center">
                        <i class="fas fa-map-marker-alt me-2"></i> Location services will be used for check-in
                    </div>
                    <div id="location-process" class="text-center mt-3 hidden">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        <span>Getting Location...</span>
                        <small id="location-text" class="d-block mt-1"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Out Modal -->
    <div class="modal fade" id="timeOutModal" tabindex="-1" aria-labelledby="timeOutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" style="background-color: #FF6600;">
                    <h5 class="modal-title"><i class="fas fa-sign-out-alt me-2"></i> Time Out</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="search-container mb-3">
                        <input type="text" id="search-professor-out" class="form-control" placeholder="🔍 Search professor to Time Out...">
                    </div>
                    <ul class="list-group mt-2 professor-list" id="professor-list-out">
                        <!-- Dynamic list of checked-in professors will be populated here -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="mb-3" id="success-message">Success!</h3>
                    <p id="success-details" class="mb-0"></p>
                    <button class="btn btn-success mt-3" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="mb-3" id="error-title">Error</h3>
                    <p id="error-message" class="mb-0"></p>
                    <button class="btn btn-danger mt-3" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Recent History -->
    <aside class="history-panel">
        <div class="history-header d-flex justify-content-between align-items-center p-3 text-white" style="background-color: #0077b6;">
            <h5 class="mb-0"><i class="fas fa-clock"></i> Recent History</h5>
        </div>
        <div class="history-content">
            <ul id="recent-history-list" class="list-group"></ul>
        </div>
        <button id="view-more-btn" class="btn btn-outline-secondary w-100">
            <span id="view-more-text">View More</span>
            <span id="refresh-spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
        </button>
    </aside>

    <!-- Main Dashboard -->
    <main class="dashboard" id="landing-page">
        <div class="date-container">
            <i class="fas fa-calendar-day"></i> <span id="current-date"></span>
        </div>

        <div class="clock-container text-center">
            <h1 id="clock" class="fw-bold"></h1>
            <p class="text-muted">Current Time</p>
        </div>

        <div class="button-container text-center mt-4">
            <button id="time-in-btn" class="btn btn-lg text-white time-action-btn" style="background-color: #0099CC; border: none;" data-bs-toggle="modal" data-bs-target="#timeInModal">
                <i class="fas fa-sign-in-alt"></i> Time In
            </button>
            <button id="time-out-btn" class="btn btn-lg text-white time-action-btn" style="background-color: #FF6600; border: none;" data-bs-toggle="modal" data-bs-target="#timeOutModal">
                <i class="fas fa-sign-out-alt"></i> Time Out
            </button>
        </div>

        <hr class="dashboard-divider">

        <!-- Attendance Statistics -->
        <section class="stats-section mt-4">
            <h3 class="fw-bold text-center"><i class="fas fa-chart-bar"></i> Attendance Overview</h3>
            <div class="stats-container d-flex justify-content-center flex-wrap mt-3">
                <div class="stat-card total-professors">
                    <h4><i class="fas fa-user-tie"></i> Total Professors</h4>
                    <h2>
                        <?php
                        $result = $conn->query("SELECT COUNT(*) AS total FROM professors");
                        echo $result->fetch_assoc()['total'];
                        ?>
                    </h2>
                </div>

                <div class="stat-card total-attendance">
                    <h4><i class="fas fa-user-check"></i> Total Attendance</h4>
                    <h2>
                        <?php
                        $result = $conn->query("SELECT COUNT(*) AS total FROM attendance");
                        echo $result->fetch_assoc()['total'];
                        ?>
                    </h2>
                </div>

                <div class="stat-card pending-checkouts">
                    <h4><i class="fas fa-clock"></i> Pending Check-Outs</h4>
                    <h2>
                        <?php
                        $result = $conn->query("SELECT COUNT(*) AS total FROM attendance WHERE check_out IS NULL AND DATE(check_in) = CURDATE()");
                        echo $result->fetch_assoc()['total'];
                        ?>
                    </h2>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Display current date
        function updateDate() {
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const today = new Date();
            document.getElementById('current-date').textContent = today.toLocaleDateString('en-US', options);
        }

        // Display current time
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('clock').textContent = timeString;
        }

        // Initialize and update both date and time
        updateDate();
        updateClock();
        setInterval(updateClock, 1000);

        // Add animation to time in/out buttons when clicked
        document.querySelectorAll('.time-action-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Add pulse animation
                this.classList.add('btn-pulse');

                // Remove animation after it completes
                setTimeout(() => {
                    this.classList.remove('btn-pulse');
                }, 300);
            });
        });

        // Refresh history button functionality
        document.getElementById('refresh-history-btn')?.addEventListener('click', function() {
            const spinner = document.getElementById('refresh-spinner');
            const btn = this;

            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            // Refresh history
            loadRecentHistory();

            // Restore button after 1 second
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i>';
            }, 1000);
        });

        // Function to show success modal
        function showSuccessModal(title, message) {
            document.getElementById('success-message').textContent = title;
            document.getElementById('success-details').textContent = message;
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        }

        // Auto-refresh after time out
        document.addEventListener('timeOutSuccess', function() {
            // Show success message
            showSuccessModal('Time Out Recorded', 'Your time out has been successfully recorded.');

            // Refresh the page after 3 seconds
            setTimeout(() => {
                location.reload();
            }, 3000);
        });

        // Handle time in success
        document.addEventListener('timeInSuccess', function(e) {
            // Show success message with details from the event
            showSuccessModal('Time In Recorded', `${e.detail.professorName} has been successfully checked in at ${new Date().toLocaleTimeString()}.`);

            // Close the time in modal
            const timeInModal = bootstrap.Modal.getInstance(document.getElementById('timeInModal'));
            timeInModal.hide();

            // Refresh the recent history and attendance overview
            loadRecentHistory();
            loadAttendanceOverview();

            // Refresh the page after 3 seconds
            setTimeout(() => {
                location.reload();
            }, 3000);
        });

        // Initialize camera with appropriate size
        function initializeCamera() {
            Webcam.set({
                width: 320,
                height: 240,
                image_format: 'jpeg',
                jpeg_quality: 90,
                constraints: {
                    facingMode: 'user'
                }
            });
            Webcam.attach('#camera');
        }

        // Initialize camera when time in modal is shown
        document.getElementById('timeInModal').addEventListener('shown.bs.modal', function() {
            initializeCamera();
        });

        // Detach camera when modal is hidden
        document.getElementById('timeInModal').addEventListener('hidden.bs.modal', function() {
            Webcam.reset();
        });

        // Global functions for other scripts to use
        function loadRecentHistory() {
            // This would be implemented in index.js
            console.log('Loading recent history...');
        }

        function loadAttendanceOverview() {
            // This would be implemented in index.js
            console.log('Loading attendance overview...');
        }
    </script>
</body>

</html>
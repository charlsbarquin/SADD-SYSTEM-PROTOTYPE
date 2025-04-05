<?php
session_start();
require_once '../config/database.php';

// Security and authentication checks
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

// Get filter parameters
$date_filter = $_GET['date'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';

// Build base query
$query = "
    SELECT 
        p.id as professor_id,
        p.name, 
        p.department,
        p.profile_image,
        a.latitude, 
        a.longitude, 
        a.check_in, 
        a.status,
        a.id as attendance_id
    FROM attendance a
    JOIN professors p ON a.professor_id = p.id
    WHERE DATE(a.check_in) = ?
    AND a.latitude IS NOT NULL
    AND a.longitude IS NOT NULL
";

// Apply additional filters
$params = [$date_filter];
$types = 's';

if ($status_filter !== 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$query .= " ORDER BY a.check_in DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$locations = $stmt->get_result();

// Get min/max dates for datepicker
$date_range = $conn->query("SELECT MIN(DATE(check_in)) as min_date, MAX(DATE(check_in)) as max_date FROM attendance")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Locations | Bicol University Polangui</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #003d7a;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        #mapContainer {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s;
        }
        
        #checkinMap {
            height: 600px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            z-index: 1;
        }
        
        .map-legend {
            padding: 10px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.4);
            line-height: 1.5;
            z-index: 1000;
        }
        
        .map-legend i {
            width: 18px;
            height: 18px;
            float: left;
            margin-right: 8px;
            opacity: 0.8;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        .professor-img-sm {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            #mapContainer {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'partials/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div id="mapContainer">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Professor Check-in Locations</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button class="btn btn-sm btn-outline-secondary" id="refreshMap">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body p-2">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?= htmlspecialchars($date_filter) ?>"
                               min="<?= $date_range['min_date'] ?>"
                               max="<?= $date_range['max_date'] ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="Present" <?= $status_filter === 'Present' ? 'selected' : '' ?>>Present</option>
                            <option value="Late" <?= $status_filter === 'Late' ? 'selected' : '' ?>>Late</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                        <a href="maps.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sync me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Map Card -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-map-marked-alt me-2"></i> Interactive Map
                <div class="float-end">
                    <span class="badge bg-primary me-2" id="markerCount">0</span> check-ins
                </div>
            </div>
            <div class="card-body p-0">
                <div id="checkinMap"></div>
            </div>
        </div>
        
        <!-- Data Table Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i> Location Data
                <div class="float-end">
                    <span class="badge bg-primary"><?= $locations->num_rows ?></span> records
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Professor</th>
                                <th>Department</th>
                                <th>Check-in Time</th>
                                <th>Status</th>
                                <th>Coordinates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($locations->num_rows > 0): ?>
                                <?php while ($location = $locations->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($location['profile_image'])): ?>
                                                <img src="../uploads/professors/<?= htmlspecialchars($location['profile_image']) ?>" 
                                                     class="professor-img-sm" alt="<?= htmlspecialchars($location['name']) ?>">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($location['name']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($location['department']) ?></td>
                                    <td><?= date('M j, Y h:i A', strtotime($location['check_in'])) ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $location['status'] == 'Late' ? 'bg-warning' : 'bg-success' ?>">
                                            <?= htmlspecialchars($location['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="https://www.google.com/maps?q=<?= $location['latitude'] ?>,<?= $location['longitude'] ?>" 
                                           target="_blank" class="text-decoration-none">
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            View on Map
                                        </a>
                                    </td>
                                    <td>
                                        <a href="edit-attendance.php?id=<?= $location['attendance_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No check-in records found for the selected filters</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize map centered on BUP
        const map = L.map('checkinMap').setView([13.3486, 123.7069], 16);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add campus boundary polygon
        const campusBounds = [
            [13.3475, 123.7050],
            [13.3475, 123.7085],
            [13.3500, 123.7085],
            [13.3500, 123.7050]
        ];
        
        const campusPolygon = L.polygon(campusBounds, {
            color: '#0056b3',
            fillOpacity: 0.1,
            weight: 2
        }).addTo(map).bindPopup("<b>BUP Campus Boundary</b>");
        
        // Create marker clusters
        const markers = L.markerClusterGroup({
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            maxClusterRadius: 40
        });
        
        // Custom icons
        const presentIcon = L.divIcon({
            html: '<i class="fas fa-map-marker-alt fa-2x" style="color: #28a745;"></i>',
            iconSize: [30, 30],
            className: 'custom-marker-icon'
        });
        
        const lateIcon = L.divIcon({
            html: '<i class="fas fa-map-marker-alt fa-2x" style="color: #ffc107;"></i>',
            iconSize: [30, 30],
            className: 'custom-marker-icon'
        });
        
        // Add markers from database
        <?php 
        $locations->data_seek(0); // Reset pointer
        $marker_count = 0;
        while ($location = $locations->fetch_assoc()): 
            $marker_count++;
            $popup_content = '<div class="map-popup">';
            $popup_content .= '<div class="d-flex align-items-center mb-2">';
            if (!empty($location['profile_image'])) {
                $popup_content .= '<img src="../uploads/professors/'.htmlspecialchars($location['profile_image']).'" class="professor-img-sm me-2">';
            }
            $popup_content .= '<b>'.htmlspecialchars($location['name']).'</b></div>';
            $popup_content .= '<div class="mb-1"><span class="badge '.($location['status'] == 'Late' ? 'bg-warning' : 'bg-success').'">'.htmlspecialchars($location['status']).'</span></div>';
            $popup_content .= '<div class="text-muted small mb-1">'.date('M j, Y h:i A', strtotime($location['check_in'])).'</div>';
            $popup_content .= '<div class="small">'.htmlspecialchars($location['department']).'</div>';
            $popup_content .= '<hr class="my-2">';
            $popup_content .= '<a href="edit-attendance.php?id='.$location['attendance_id'].'" class="btn btn-sm btn-outline-primary w-100">View Details</a>';
            $popup_content .= '</div>';
        ?>
            const marker<?= $marker_count ?> = L.marker(
                [<?= $location['latitude'] ?>, <?= $location['longitude'] ?>],
                { icon: <?= $location['status'] == 'Late' ? 'lateIcon' : 'presentIcon' ?> }
            ).bindPopup(`<?= $popup_content ?>`);
            markers.addLayer(marker<?= $marker_count ?>);
        <?php endwhile; ?>
        
        map.addLayer(markers);
        
        // Update marker count
        $('#markerCount').text(<?= $marker_count ?>);
        
        // Add legend
        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'map-legend');
            div.innerHTML = `
                <h6 class="mb-2">Legend</h6>
                <div><i class="fas fa-map-marker-alt" style="color: #28a745;"></i> Present</div>
                <div><i class="fas fa-map-marker-alt" style="color: #ffc107;"></i> Late</div>
                <div><i class="fas fa-square" style="color: #0056b3; opacity: 0.3;"></i> Campus Area</div>
            `;
            return div;
        };
        legend.addTo(map);
        
        // Fit bounds to show all markers
        if (<?= $marker_count ?> > 0) {
            map.fitBounds(markers.getBounds(), { padding: [50, 50] });
        }
        
        // Refresh button
        $('#refreshMap').click(function() {
            location.reload();
        });
        
        // Auto-refresh every 5 minutes (300000 ms)
        setInterval(function() {
            Swal.fire({
                title: 'Refresh Data?',
                text: 'New check-ins may be available. Refresh now?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Refresh',
                cancelButtonText: 'Later'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        }, 300000);
    </script>
</body>
</html>
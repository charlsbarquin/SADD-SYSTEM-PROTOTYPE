<?php
include '../config/database.php';

// Fetch all professors from database (now including phone)
$professors = [];
$result = $conn->query("SELECT id, name, designation, email, phone FROM professors ORDER BY name");
if ($result) {
    $professors = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professors | Automated Attendance System</title>

    <!-- Bootstrap & Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .professor-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .professor-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .search-container {
            max-width: 500px;
            margin: 0 auto 30px;
        }

        .search-input {
            border-radius: 20px;
            padding-left: 20px;
            border: 1px solid #dee2e6;
        }

        .search-btn {
            border-radius: 0 20px 20px 0;
            border-left: none;
        }

        .professor-email {
            color: #6c757d;
            font-size: 0.9rem;
            word-break: break-word;
        }

        .professor-name {
            color: #343a40;
            font-weight: 500;
        }

        .professor-designation {
            color: #495057;
            font-size: 0.95rem;
        }

        .professor-phone {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="mb-3"><i class="fas fa-chalkboard-teacher me-2"></i>Professors</h1>
                <nav aria-label="breadcrumb" class="d-flex justify-content-center">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Professors</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="search-container mx-auto">
                    <div class="input-group">
                        <input type="text" class="form-control search-input" placeholder="Search by name or designation..." id="searchInput">
                        <button class="btn btn-primary search-btn" type="button" id="searchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Professors List -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="professorsContainer">
            <?php if (count($professors) > 0): ?>
                <?php foreach ($professors as $professor): ?>
                    <div class="col professor-item">
                        <div class="card professor-card h-100">
                            <div class="card-body text-center p-4">
                                <!-- Professor Info -->
                                <h5 class="professor-name mb-2"><?= htmlspecialchars($professor['name']) ?></h5>
                                <p class="professor-designation mb-3"><?= htmlspecialchars($professor['designation']) ?></p>

                                <!-- Email -->
                                <div class="professor-email mb-2">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?= htmlspecialchars($professor['email']) ?>
                                </div>

                                <!-- Phone Number -->
                                <?php if (!empty($professor['phone'])): ?>
                                    <div class="professor-phone">
                                        <i class="fas fa-phone me-1"></i>
                                        <?= htmlspecialchars($professor['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-info-circle me-2"></i> No professors found in the system.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchBtn').click(function() {
                performSearch();
            });

            $('#searchInput').keyup(function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                } else {
                    // Live search as typing
                    performSearch();
                }
            });

            function performSearch() {
                const searchTerm = $('#searchInput').val().toLowerCase().trim();

                $('.professor-item').each(function() {
                    const name = $(this).find('.professor-name').text().toLowerCase();
                    const designation = $(this).find('.professor-designation').text().toLowerCase();
                    const email = $(this).find('.professor-email').text().toLowerCase();

                    const matches = name.includes(searchTerm) ||
                        designation.includes(searchTerm) ||
                        email.includes(searchTerm);

                    $(this).toggle(matches);
                });

                // Show message if no results
                if ($('.professor-item:visible').length === 0) {
                    $('#professorsContainer').append(`
                        <div class="col-12 no-results">
                            <div class="alert alert-warning text-center py-4">
                                <i class="fas fa-exclamation-circle me-2"></i> No professors match your search.
                            </div>
                        </div>
                    `);
                } else {
                    $('.no-results').remove();
                }
            }
        });
    </script>
    <!-- Bootstrap & Popper.js (Required for Dropdowns) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
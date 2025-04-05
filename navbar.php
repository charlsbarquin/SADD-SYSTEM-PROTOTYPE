<?php include '../config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar | Bicol University Polangui</title>

    <!-- Bootstrap & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Google Fonts (Poppins) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap">

    <style>
        /* ===== General Navbar Styling ===== */
        body {
            font-family: 'Poppins', sans-serif;
            padding-top: 85px;
            transition: background-color 0.3s ease-in-out;
        }

        .navbar {
            width: 100%;
            background-color: white;
            border-bottom: 4px solid #0099CC;
            padding: 15px 25px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* ===== Logo & Title Styling ===== */
        .navbar-brand {
            display: flex;
            align-items: center;
            font-size: 22px;
            font-weight: 700;
            color: #0099CC;
            transition: transform 0.2s ease-in-out;
            margin-right: 0;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            height: 70px;
            width: auto;
            margin-right: 5px;
        }

        .title-text {
            font-size: 18px;
            font-weight: 600;
            line-height: 1.3;
            margin-left: 10px;
            text-align: left;
        }

        /* ===== Navigation Items ===== */
        .navbar-nav {
            align-items: center;
        }

        .navbar-nav .nav-link {
            font-size: 18px;
            font-weight: 500;
            color: black;
            transition: all 0.3s ease-in-out;
            padding: 10px 15px;
            border-radius: 8px;
            white-space: nowrap;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: white;
            background-color: #0099CC;
        }

        /* ===== Dropdown Styling ===== */
        .dropdown-menu {
            border-radius: 10px;
            border: none;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s ease-in-out;
            font-size: 16px;
        }

        .dropdown-item {
            padding: 12px 18px;
            transition: all 0.3s ease-in-out;
        }

        .dropdown-item:hover {
            background-color: #0099CC;
            color: white;
        }

        /* ===== Icons & Notifications ===== */
        .nav-icons {
            font-size: 22px;
            color: black;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .nav-icons:hover {
            color: #0099CC;
            transform: scale(1.1);
        }

        .notification-badge {
            background-color: red;
            color: white;
            font-size: 12px;
            padding: 3px 6px;
            border-radius: 50%;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        /* ===== Mobile Menu Toggle ===== */
        .navbar-toggler {
            border: none;
            padding: 8px;
            font-size: 1.5rem;
            color: #0099CC;
        }

        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }

        /* ===== Animations ===== */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== Responsive Adjustments ===== */
        @media (max-width: 1199px) {
            .navbar-brand {
                font-size: 20px;
            }

            .title-text {
                font-size: 16px;
            }

            .navbar-nav .nav-link {
                font-size: 16px;
                padding: 8px 12px;
            }
        }

        @media (max-width: 991px) {
            .navbar {
                padding: 10px 15px;
            }

            .navbar-brand img {
                height: 60px;
            }

            .navbar-collapse {
                background-color: white;
                padding: 15px;
                border-radius: 8px;
                margin-top: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }

            .navbar-nav {
                gap: 5px;
            }

            .dropdown-menu {
                text-align: center;
                margin-top: 5px;
            }
        }

        @media (max-width: 767px) {
            body {
                padding-top: 70px;
            }

            .navbar-brand {
                flex-direction: row;
                align-items: center;
            }

            .title-text {
                font-size: 14px;
                margin-left: 8px;
            }

            .navbar-brand img {
                height: 50px;
            }

            .navbar-nav .nav-link {
                font-size: 15px;
                padding: 8px 10px;
            }

            .dropdown-item {
                padding: 10px 15px;
            }
        }

        @media (max-width: 575px) {
            .navbar {
                padding: 8px 10px;
            }

            .navbar-brand {
                font-size: 18px;
            }

            .title-text {
                display: none;
            }

            .navbar-toggler {
                font-size: 1.25rem;
            }

            .navbar-nav .nav-link {
                font-size: 14px;
                padding: 6px 8px;
            }

            .nav-icons {
                font-size: 18px;
            }

            .dropdown-menu {
                font-size: 14px;
            }
        }

        @media (max-width: 400px) {
            .navbar-brand img {
                height: 40px;
            }

            .navbar-nav .nav-link i {
                margin-right: 5px;
            }

            .navbar-nav .nav-link span {
                display: inline-block;
            }
        }

        /* Add to your existing dropdown styles */
        .dropdown-item.text-primary {
            color: #0099CC !important;
        }

        .dropdown-item.text-primary:hover {
            background-color: #0099CC !important;
            color: white !important;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <!-- Left: Logos & System Title -->
            <a class="navbar-brand" href="index.php">
                <div class="logo-container">
                    <img src="../assets/images/bu-logo.png" alt="BU Logo">
                    <img src="../assets/images/polangui-logo.png" alt="Polangui Logo">
                </div>
                <span class="title-text">
                    <span style="color: #0099CC;">Bicol University</span>
                    <span style="color: #FF6600;">Polangui</span>
                    <span style="color: black; display: block;">CSD Professors' Attendance System</span>
                </span>
            </a>

            <!-- Mobile Menu Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navbar Items -->
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../pages/index.php"><i class="fas fa-home"></i> <span>Home</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="../pages/attendance-report.php"><i class="fas fa-file-alt"></i> <span>Reports</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="../pages/dashboard.php"><i class="fas fa-chart-line"></i> <span>Statistics</span></a></li>

                    <!-- Settings Link -->
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/settings.php">
                            <i class="fas fa-cog nav-icons"></i> <span>Settings</span>
                        </a>
                    </li>

                    <!-- Admin Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle nav-icons"></i> <span>Admin</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <!-- Change this in your dropdown menu -->
                            <li><a class="dropdown-item text-primary fw-bold" href="../admin/admin-login.php">
                                    <i class="fas fa-sign-in-alt me-2"></i> Admin Login
                                </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dark Mode & Language Selector Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Dark mode toggle logic (if applicable)
            const darkModeToggle = document.getElementById("darkModeToggle");
            if (darkModeToggle) {
                darkModeToggle.addEventListener("click", function() {
                    document.body.classList.toggle("dark-mode");
                    localStorage.setItem("dark-mode", document.body.classList.contains("dark-mode"));
                });

                // Load dark mode preference
                if (localStorage.getItem("dark-mode") === "true") {
                    document.body.classList.add("dark-mode");
                }
            }

            // Language selection logic (if applicable)
            const languageSelector = document.getElementById("languageSelector");
            if (languageSelector) {
                languageSelector.addEventListener("change", function() {
                    let selectedLanguage = this.value;
                    localStorage.setItem("language", selectedLanguage);
                    location.reload(); // Reload to apply changes
                });

                // Load selected language
                if (localStorage.getItem("language")) {
                    languageSelector.value = localStorage.getItem("language");
                }
            }
        });
    </script>

    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
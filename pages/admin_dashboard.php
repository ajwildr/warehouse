<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Fetch notifications for low stock
$query = "SELECT COUNT(*) AS count FROM products WHERE current < min_limit";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$notificationCount = $row['count'];

// Fetch quick stats
$statsQueries = [
    "SELECT COUNT(*) as total FROM products" => "totalProducts",
    "SELECT COUNT(*) as total FROM users" => "totalUsers",
    "SELECT COUNT(*) as total FROM suppliers" => "totalSuppliers",
    "SELECT COUNT(*) as total FROM categories" => "totalCategories"
];

$stats = [];
foreach ($statsQueries as $query => $key) {
    $result = $conn->query($query);
    $stats[$key] = $result->fetch_assoc()['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudWare - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #34495E;
            --accent-color: #3498DB;
            --success-color: #2ECC71;
            --warning-color: #F1C40F;
            --danger-color: #E74C3C;
            --light-bg: #ECF0F1;
            --dark-text: #2C3E50;
            --light-text: #ECF0F1;
        }

        /* Base Styles */
        body {
            background-color: var(--light-bg);
            color: var(--dark-text);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            background-color: var(--primary-color);
            color: var(--light-text);
            width: 250px;
            position: fixed;
            height: 100vh;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: var(--light-text);
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: var(--secondary-color);
            padding-left: 1.5rem;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
            transition: all 0.3s ease;
        }

        /* Card Styles */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Notification Badge */
        .notification-badge {
            position: relative;
            display: inline-block;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            padding: 4px 8px;
            border-radius: 50%;
            background: var(--danger-color);
            color: white;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }

            .sidebar.active {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .main-content.active {
                margin-left: 250px;
            }

            .toggle-sidebar {
                display: block !important;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--secondary-color);
            border-radius: 4px;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3">
            <h3 class="text-center mb-4">CloudWare</h3>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_users.php" class="nav-link">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_suppliers.php" class="nav-link">
                        <i class="bi bi-truck"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_manage_products.php" class="nav-link">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_category.php" class="nav-link">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_manager_scan.php" class="nav-link">
                        <i class="bi bi-qr-code-scan"></i> Scan
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded mb-4 shadow-sm">
            <div class="container-fluid">
                <button class="btn toggle-sidebar d-md-none">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-flex align-items-center">
                    <div class="notification-badge me-3">
                        <a href="low_stock_notifications.php" class="text-dark">
                            <i class="bi bi-bell fs-5"></i>
                            <?php if ($notificationCount > 0): ?>
                                <span class="badge"><?= $notificationCount ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Content -->
        <div class="container-fluid animate-fade-in">
            <!-- Quick Stats -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="stat-card">
                        <i class="bi bi-box text-primary"></i>
                        <h3><?= $stats['totalProducts'] ?></h3>
                        <p class="mb-0">Total Products</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="stat-card">
                        <i class="bi bi-people text-success"></i>
                        <h3><?= $stats['totalUsers'] ?></h3>
                        <p class="mb-0">Total Users</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="stat-card">
                        <i class="bi bi-truck text-warning"></i>
                        <h3><?= $stats['totalSuppliers'] ?></h3>
                        <p class="mb-0">Total Suppliers</p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="stat-card">
                        <i class="bi bi-tags text-danger"></i>
                        <h3><?= $stats['totalCategories'] ?></h3>
                        <p class="mb-0">Total Categories</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <!-- Add your recent activity content here -->
                            <p class="text-muted">No recent activities to display.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-sidebar');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('active');
                    document.querySelector('.main-content').classList.remove('active');
                }
            }
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html>
<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is Accounting
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Accounting') {
    header("Location: login.php");
    exit;
}

// Get total number of unique products in stock
$total_stock_query = "SELECT COUNT(DISTINCT product_id) as total FROM products WHERE current > 0";
$total_result = $conn->query($total_stock_query);
$total_stock = $total_result->fetch_assoc()['total'];

// Get incoming stock count for today
$incoming_query = "SELECT COUNT(*) as total, SUM(quantity) as qty FROM stock 
                  WHERE transaction_type = 'Incoming' 
                  AND DATE(created_at) = CURDATE()";
$incoming_result = $conn->query($incoming_query);
$incoming_data = $incoming_result->fetch_assoc();
$incoming_today = $incoming_data['qty'] ?? 0;

// Get outgoing stock count for today
$outgoing_query = "SELECT COUNT(*) as total, SUM(quantity) as qty FROM stock 
                  WHERE transaction_type = 'Outgoing' 
                  AND DATE(created_at) = CURDATE()";
$outgoing_result = $conn->query($outgoing_query);
$outgoing_data = $outgoing_result->fetch_assoc();
$outgoing_today = $outgoing_data['qty'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudWare - Accounting Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }

        /* Base Styles */
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        /* Sidebar Styles */
        .sidebar {
            background: var(--primary-color);
            min-height: 100vh;
            transition: all 0.3s;
        }

        .sidebar .nav-link {
            color: #fff;
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            background: var(--secondary-color);
        }

        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }

        /* Main Content Styles */
        .main-content {
            padding: 2rem;
        }

        .dashboard-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .stat-card {
            padding: 1.5rem;
            border-radius: 1rem;
            color: #fff;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                z-index: 1000;
                width: 80%;
                max-width: 300px;
            }

            .sidebar.active {
                left: 0;
            }

            .main-content {
                padding: 1rem;
            }

            .mobile-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: var(--primary-color);
                padding: 0.5rem;
                z-index: 999;
            }
        }

        /* Touch-friendly Elements */
        @media (max-width: 1024px) {
            .nav-link, .btn {
                min-height: 44px;
                display: flex;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="d-flex flex-column p-3">
                    <h4 class="text-white mb-4">CloudWare</h4>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        <a class="nav-link" href="incoming_stock.php"><i class="bi bi-box-arrow-in-down"></i> Incoming Stock</a>
                        <a class="nav-link" href="outgoing_stock.php"><i class="bi bi-box-arrow-up"></i> Outgoing Stock</a>
                        <a class="nav-link" href="logs.php"><i class="bi bi-journal-text"></i> Stock Logs</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top Navigation -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white rounded-3 mb-4 p-3">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="d-flex align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-link dropdown-toggle text-dark text-decoration-none" type="button" id="userMenu" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i>
                                    <?= htmlspecialchars($_SESSION['username']) ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Dashboard Content -->
                <div class="row g-4">
                    <!-- Stats Cards -->
                    <div class="col-md-4">
                        <div class="stat-card dashboard-card bg-primary">
                            <h3>Total Stock Items</h3>
                            <h2 class="mb-0"><?= $total_stock ?></h2>
                            <small>Active Products</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card dashboard-card bg-success">
                            <h3>Incoming Today</h3>
                            <h2 class="mb-0"><?= $incoming_today ?></h2>
                            <small>Units Received</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card dashboard-card bg-warning">
                            <h3>Outgoing Today</h3>
                            <h2 class="mb-0"><?= $outgoing_today ?></h2>
                            <small>Units Dispatched</small>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-12">
                        <div class="dashboard-card p-4">
                            <h3>Quick Actions</h3>
                            <div class="row g-3 mt-2">
                                <div class="col-md-4">
                                    <a href="incoming_stock.php" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-circle"></i> New Stock Entry
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="outgoing_stock.php" class="btn btn-success w-100">
                                        <i class="bi bi-arrow-right-circle"></i> Record Outgoing
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="logs.php" class="btn btn-info w-100">
                                        <i class="bi bi-file-text"></i> View Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="mobile-nav d-md-none">
            <div class="row text-center">
                <div class="col">
                    <a href="#" class="text-white"><i class="bi bi-house-door"></i></a>
                </div>
                <div class="col">
                    <a href="incoming_stock.php" class="text-white"><i class="bi bi-box-arrow-in-down"></i></a>
                </div>
                <div class="col">
                    <a href="outgoing_stock.php" class="text-white"><i class="bi bi-box-arrow-up"></i></a>
                </div>
                <div class="col">
                    <a href="logs.php" class="text-white"><i class="bi bi-journal-text"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.querySelector('.navbar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const toggler = document.querySelector('.navbar-toggler');
            if (!sidebar.contains(event.target) && !toggler.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
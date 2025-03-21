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

// Get recent stock activities
$recent_query = "SELECT s.*, p.name as product_name 
                FROM stock s 
                INNER JOIN products p ON s.product_id = p.product_id 
                ORDER BY s.created_at DESC LIMIT 5";
$recent_result = $conn->query($recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudWare - Accounting Dashboard</title>
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

        body {
            background-color: var(--light-bg);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--primary-color);
            color: var(--light-text);
            width: 250px;
            position: fixed;
            height: 100vh;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            padding: 1rem 0;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar .nav-link {
            color: var(--light-text);
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background-color: var(--secondary-color);
            padding-left: 1.5rem;
        }

        .sidebar .nav-link.active {
            background-color: var(--accent-color);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 2rem;
            transition: all 0.3s ease;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        /* Quick Actions */
        .quick-action {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 1rem;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--dark-text);
        }

        .quick-action:hover {
            transform: translateY(-5px);
            background-color: var(--accent-color);
            color: white;
        }

        .quick-action i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Activity Card */
        .activity-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
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
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            CloudWare
        </div>
        <div class="mt-4">
            <div class="text-center mb-4">
                <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                <h6 class="mt-2 mb-0"><?= htmlspecialchars($_SESSION['username']) ?></h6>
                <small>Accounting</small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="accounting_dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="incoming_stock.php">
                        <i class="bi bi-box-arrow-in-down me-2"></i> Incoming Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="outgoing_stock.php">
                        <i class="bi bi-box-arrow-up me-2"></i> Outgoing Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logs.php">
                        <i class="bi bi-journal-text me-2"></i> Stock Logs
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <a class="nav-link text-danger" href="../logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
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
                <button class="btn d-md-none" id="sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <span class="me-3">
                        <i class="bi bi-calendar-date me-1"></i> <?= date('F d, Y') ?>
                    </span>
                </div>
            </div>
        </nav>

        <!-- Quick Actions Grid -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <a href="incoming_stock.php" class="quick-action shadow">
                        <i class="bi bi-box-arrow-in-down text-primary"></i>
                        <h4>Incoming Stock</h4>
                        <p class="mb-0">Record new inventory arrivals</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="outgoing_stock.php" class="quick-action shadow">
                        <i class="bi bi-box-arrow-up text-success"></i>
                        <h4>Outgoing Stock</h4>
                        <p class="mb-0">Record inventory dispatches and transfers</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="logs.php" class="quick-action shadow">
                        <i class="bi bi-journal-text text-warning"></i>
                        <h4>Stock Logs</h4>
                        <p class="mb-0">Past inventory transactions and updates</p>
                    </a>
                </div>
            </div>
        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h3 class="mb-2"><?= $total_stock ?></h3>
                    <p class="text-muted mb-0">Active Products in Stock</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </div>
                    <h3 class="mb-2"><?= $incoming_today ?></h3>
                    <p class="text-muted mb-0">Incoming Stock Today</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-box-arrow-up"></i>
                    </div>
                    <h3 class="mb-2"><?= $outgoing_today ?></h3>
                    <p class="text-muted mb-0">Outgoing Stock Today</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-card">
            <h5 class="card-title mb-4">Recent Stock Activities</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($activity = $recent_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($activity['product_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $activity['transaction_type'] === 'Incoming' ? 'success' : 'warning' ?>">
                                        <?= $activity['transaction_type'] ?>
                                    </span>
                                </td>
                                <td><?= $activity['quantity'] ?></td>
                                <td><?= date('M d, Y H:i', strtotime($activity['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                document.querySelector('.main-content').classList.remove('active');
            }
        });
    </script>
</body>
</html>
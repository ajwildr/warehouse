<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: login.php");
    exit;
}

$assigned_category = $_SESSION['assigned_category'];

// Fetch various statistics for the manager's dashboard
$stats = [];

// 1. Low stock notifications
$query = "SELECT COUNT(*) AS count FROM products WHERE current < min_limit AND category = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $assigned_category);
$stmt->execute();
$result = $stmt->get_result();
$stats['low_stock'] = $result->fetch_assoc()['count'];

// 2. Total products in category
$query = "SELECT COUNT(*) AS count FROM products WHERE category = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $assigned_category);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_products'] = $result->fetch_assoc()['count'];

// 3. Workers in category
$query = "SELECT COUNT(*) AS count FROM users WHERE role = 'Worker' AND assigned_category = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $assigned_category);
$stmt->execute();
$result = $stmt->get_result();
$stats['workers'] = $result->fetch_assoc()['count'];

// 4. Total racks assigned
$query = "SELECT COUNT(DISTINCT rack_location) AS count FROM rack r 
          INNER JOIN products p ON r.product_id = p.product_id 
          WHERE p.category = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $assigned_category);
$stmt->execute();
$result = $stmt->get_result();
$stats['racks'] = $result->fetch_assoc()['count'];

// 5. Recent stock movements
$query = "SELECT s.*, p.name as product_name FROM stock s 
          INNER JOIN products p ON s.product_id = p.product_id 
          WHERE p.category = ? 
          ORDER BY s.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $assigned_category);
$stmt->execute();
$recent_movements = $stmt->get_result();

// Fetch tasks assigned by the manager

$manager_id = $_SESSION['user_id'];

$query = "SELECT t.task_id, u.username AS worker_name, t.description, t.status, t.created_at 
          FROM tasks t 
          JOIN users u ON t.assigned_to = u.user_id 
          WHERE t.assigned_by = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$tasks = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudWare - Manager Dashboard</title>
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
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        /* Recent Activity */
        .activity-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .activity-item:hover {
            background-color: rgba(52, 152, 219, 0.1);
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

        /* Utilities */
        .text-primary { color: var(--primary-color) !important; }
        .text-accent { color: var(--accent-color) !important; }
        .bg-gradient { background: linear-gradient(135deg, var(--primary-color), var(--accent-color)) !important; }
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
                <small>Manager - <?= htmlspecialchars($assigned_category) ?></small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="manager_dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_products.php">
                        <i class="bi bi-box-seam me-2"></i> Manage Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="assign_task.php">
                        <i class="bi bi-list-check me-2"></i> Assign Tasks
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="manage_racks.php">
                        <i class="bi bi-grid-3x3-gap me-2"></i> Manage Racks
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="generate_barcodes.php">
                        <i class="bi bi-upc-scan me-2"></i> Generate Barcodes
                    </a>
                </li> -->
                
                <li class="nav-item">
                    <a class="nav-link" href="admin_manager_scan.php">
                        <i class="bi bi-qr-code-scan me-2"></i> Scan
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
                <div class="ms-auto">
                    <a href="low_stock_notifications.php" class="btn btn-outline-warning position-relative me-3">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($stats['low_stock'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $stats['low_stock'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Statistics Cards -->
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['total_products'] ?></h3>
                    <p class="text-muted mb-0">Total Products</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['low_stock'] ?></h3>
                    <p class="text-muted mb-0">Low Stock Items</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['workers'] ?></h3>
                    <p class="text-muted mb-0">Active Workers</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon text-info">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['racks'] ?></h3>
                    <p class="text-muted mb-0">Total Racks</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-card">
            <h5 class="card-title mb-4">Recent Stock Movements</h5>
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
                        <?php while ($movement = $recent_movements->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($movement['product_name']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $movement['transaction_type'] === 'Incoming' ? 'success' : 'warning' ?>">
                                        <?= $movement['transaction_type'] ?>
                                    </span>
                                </td>
                                <td><?= $movement['quantity'] ?></td>
                                <td><?= date('M d, Y H:i', strtotime($movement['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="activity-card">
            <h5 class="card-title mb-4">Assigned Tasks</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($task = $tasks->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['worker_name']) ?></td>
                                <td><?= htmlspecialchars($task['description']) ?></td>
                                <td><?= htmlspecialchars($task['status']) ?></td>
                                <td><?= htmlspecialchars($task['created_at']) ?></td>
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
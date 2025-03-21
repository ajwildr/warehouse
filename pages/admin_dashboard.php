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

// Fetch recent activities (similar to worker dashboard)
$query = "SELECT s.*, p.name as product_name 
          FROM stock s 
          INNER JOIN products p ON s.product_id = p.product_id 
          ORDER BY s.created_at DESC LIMIT 5";
$recent_activities = $conn->query($query);
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
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 0.5rem;
            min-height: 150px; /* Reduced from 200px */
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
            font-size: 2.25rem; /* Reduced from 3rem */
            margin-bottom: 0.75rem;
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
                <small>Administrator</small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="admin_dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">
                        <i class="bi bi-people me-2"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_suppliers.php">
                        <i class="bi bi-truck me-2"></i> Suppliers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_manage_products.php">
                        <i class="bi bi-box-seam me-2"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_category.php">
                        <i class="bi bi-tags me-2"></i> Categories
                    </a>
                </li>
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
                <div class="ms-auto d-flex align-items-center">
                    <!-- Notifications -->
                    <a href="low_stock_notifications.php" class="btn btn-outline-primary position-relative me-3">
                        <i class="bi bi-bell-fill"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $notificationCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                </div>
            </div>
        </nav>

        <!-- Quick Actions Grid -->
        <div class="container-md py-3">
            <div class="row justify-content-center g-4">
                <div class="col-sm-6 col-md-4">
                    <a href="admin_manage_products.php" class="quick-action shadow">
                        <i class="bi bi-box-seam text-primary"></i>
                        <h5>Manage Products</h5>
                        <p class="mb-0">Add, edit or remove products</p>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4">
                    <a href="manage_users.php" class="quick-action shadow">
                        <i class="bi bi-people text-success"></i>
                        <h5>Manage Users</h5>
                        <p class="mb-0">Edit user accounts and permissions</p>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4">
                    <a href="manage_suppliers.php" class="quick-action shadow">
                        <i class="bi bi-truck text-warning"></i>
                        <h5>Manage Suppliers</h5>
                        <p class="mb-0">Add or edit supplier information</p>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4">
                    <a href="manage_category.php" class="quick-action shadow active">
                        <i class="bi bi-tags text-light"></i>
                        <h5>Manage Categories</h5>
                        <p class="mb-0">Organize products with categories</p>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4">
                    <a href="admin_manager_scan.php" class="quick-action shadow">
                        <i class="bi bi-qr-code-scan text-info"></i>
                        <h5>Scan Products</h5>
                        <p class="mb-0">Quickly scan product barcodes</p>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4">
                    <a href="low_stock_notifications.php" class="quick-action shadow">
                        <i class="bi bi-bell-fill text-secondary"></i>
                        <h5>Stock Alerts</h5>
                        <p class="mb-0">View products with low inventory</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['totalProducts'] ?></h3>
                    <p class="text-muted mb-0">Total Products</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['totalUsers'] ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-warning">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['totalSuppliers'] ?></h3>
                    <p class="text-muted mb-0">Total Suppliers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon text-danger">
                        <i class="bi bi-tags"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['totalCategories'] ?></h3>
                    <p class="text-muted mb-0">Total Categories</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="activity-card">
            <h5 class="card-title mb-4">Recent Activities</h5>
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
                        <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
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
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No recent activities to display.</td>
                            </tr>
                        <?php endif; ?>
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
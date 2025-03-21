<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is Worker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Worker') {
    header("Location: login.php");
    exit;
}

$assigned_category = $_SESSION['assigned_category'];

// Fetch worker-specific statistics
$stats = [];

// 1. Total transactions today - modified to remove worker_id dependency
$query = "SELECT COUNT(*) AS count FROM stock 
          WHERE DATE(created_at) = CURDATE()";
$result = $conn->query($query);
$stats['scans_today'] = $result->fetch_assoc()['count'];


//total products
$query = "SELECT COUNT(*) AS total_products FROM products";
$result = $conn->query($query);
$stats['total_products'] = $result->fetch_assoc()['total_products'];


// 2. Recent activities - modified to show all recent activities for the assigned category
$query = "SELECT s.*, p.name as product_name 
          FROM stock s 
          INNER JOIN products p ON s.product_id = p.product_id 
          WHERE p.category = ? 
          ORDER BY s.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $assigned_category);
$stmt->execute();
$recent_activities = $stmt->get_result();

$worker_id = $_SESSION['user_id'];
// tasks
$query = "SELECT t.task_id, t.description, t.status, u.username as manager 
          FROM tasks t 
          JOIN users u ON t.assigned_by = u.user_id
          WHERE t.assigned_to = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$tasks = $stmt->get_result();

// notification

$notif_query = "SELECT id, message, created_at FROM notifications WHERE user_id = ? AND status = 'Unread' ORDER BY created_at DESC";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $worker_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudWare - Worker Dashboard</title>
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
                <small>Worker - <?= htmlspecialchars($assigned_category) ?></small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="worker_dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="generate_barcode.php">
                        <i class="bi bi-qr-code"></i> Generate QR code
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="scan_barcode.php">
                        <i class="bi bi-qr-code-scan me-2"></i> Scan QR code
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="worker_list_product.php">
                        <i class="bi bi-box-seam me-2"></i> List Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_rack.php">
                        <i class="bi bi-grid-3x3-gap"></i> View Rack
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
                    <!-- Task Notifications -->
                    <a href="task_notifications.php" class="btn btn-outline-primary position-relative">
                        <i class="bi bi-bell-fill"></i>
                        <?php
                        // Fetch unread task notifications
                        $user_id = $_SESSION['user_id'];
                        $query = "SELECT COUNT(*) AS unread_tasks FROM notifications WHERE user_id = ? AND status = 'Unread'";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $task_notifications = $result->fetch_assoc();

                        if ($task_notifications['unread_tasks'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $task_notifications['unread_tasks'] ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </nav>


        <!-- Quick Actions Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <a href="scan_barcode.php" class="quick-action shadow">
                    <i class="bi bi-qr-code-scan me-2 text-primary"></i>
                    <h4>Scan Qr code</h4>
                    <p class="mb-0">Scan products for inventory management</p>
                </a>
            </div>
            <div class="col-md-6">
                <a href="generate_barcode.php" class="quick-action shadow">
                    <i class="bi bi-qr-code text-success"></i>
                    <h4>Generate QR Code</h4>
                    <p class="mb-0">Generate and print QR codes for inventory tracking</p>
                </a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-icon text-primary">
                        <i class="bi bi-qr-code-scan me-2"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['scans_today'] ?></h3>
                    <p class="text-muted mb-0">Products Scanned Today</p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-icon text-success">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h3 class="mb-2"><?= $stats['total_products'] ?></h3>
                    <p class="text-muted mb-0">Total Products in Inventory</p>
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
                    </tbody>
                </table>
            </div>
        </div>

        <div class="activity-card">
            <h5 class="card-title mb-4">Assigned Tasks</h5>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assigned By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($task = $tasks->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['description']) ?></td>
                            <td><?= htmlspecialchars($task['manager']) ?></td>
                            <td>
                                <span class="badge bg-<?= $task['status'] === 'Pending' ? 'warning' : 'success' ?>">
                                    <?= $task['status'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($task['status'] === 'Pending'): ?>
                                    <form method="post" action="update_task_status.php">
                                        <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                        <button type="submit" name="complete_task" class="btn btn-success btn-sm">Mark Completed</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-success">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
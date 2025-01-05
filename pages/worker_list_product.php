<?php
session_start();
require '../includes/db_connect.php';

// Ensure the user is logged in and has the Worker role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Worker') {
    header("Location: error.php");
    exit;
}

// Get the worker's assigned category from the session
$assigned_category = $_SESSION['assigned_category'];

// Query to fetch all products in the worker's assigned category
$query = "SELECT * FROM products WHERE category = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $assigned_category);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List - CloudWare</title>
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
            padding-bottom: 60px; /* Space for bottom nav on mobile */
        }

        /* Back Button */
        .back-button {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .back-button:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateX(-5px);
        }

        /* Content Container */
        .content-container {
            padding: 2rem;
            padding-top: 4rem; /* Space for back button */
        }

        /* Card Design */
        .product-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-card .title {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .product-card .details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .product-card .detail-item {
            display: flex;
            flex-direction: column;
        }

        .product-card .label {
            font-size: 0.9rem;
            color: #666;
        }

        .product-card .value {
            font-weight: bold;
            color: var(--dark-text);
        }

        /* Responsive Table */
        .table-container {
            display: none; /* Hidden on mobile */
            overflow-x: auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 1rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: var(--primary-color);
            color: white;
            white-space: nowrap;
        }

        .table tbody tr {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        /* Bottom Navigation (Mobile) */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 0.8rem;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-around;
            z-index: 1000;
        }

        .bottom-nav a {
            color: var(--dark-text);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.8rem;
        }

        .bottom-nav i {
            font-size: 1.5rem;
            margin-bottom: 0.2rem;
        }

        /* Responsive Breakpoints */
        @media (min-width: 768px) {
            .table-container {
                display: block;
            }

            .product-cards {
                display: none;
            }

            .bottom-nav {
                display: none;
            }

            body {
                padding-bottom: 2rem;
            }
        }

        /* Loading Animation */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Touch-friendly Adjustments */
        @media (max-width: 767px) {
            .product-card {
                padding: 1rem;
                min-height: 44px; /* Minimum touch target size */
            }

            .bottom-nav a {
                padding: 0.5rem;
                min-width: 44px;
                min-height: 44px;
            }
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="worker_dashboard.php" class="back-button">
        <i class="bi bi-arrow-left"></i>
        <span>Back</span>
    </a>

    <div class="content-container">
        <h2 class="mb-4">Products in <?= htmlspecialchars($assigned_category) ?></h2>

        <!-- Mobile Product Cards -->
        <div class="product-cards">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" onclick="window.location.href='rack_details.php?product_id=<?= $product['product_id'] ?>'">
                        <div class="title"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="details">
                            <div class="detail-item">
                                <span class="label">Stock</span>
                                <span class="value"><?= htmlspecialchars($product['current']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Min Limit</span>
                                <span class="value"><?= htmlspecialchars($product['min_limit']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Max Limit</span>
                                <span class="value"><?= htmlspecialchars($product['max_limit']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No products found in your assigned category.
                </div>
            <?php endif; ?>
        </div>

        <!-- Desktop Table View -->
        <div class="table-container">
            <?php if (!empty($products)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Current Stock</th>
                            <th>Min Limit</th>
                            <th>Max Limit</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr onclick="window.location.href='rack_details.php?product_id=<?= $product['product_id'] ?>'">
                                <td><?= htmlspecialchars($product['product_id']) ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['category']) ?></td>
                                <td><?= htmlspecialchars($product['supplier_id']) ?></td>
                                <td><?= htmlspecialchars($product['current']) ?></td>
                                <td><?= htmlspecialchars($product['min_limit']) ?></td>
                                <td><?= htmlspecialchars($product['max_limit']) ?></td>
                                <td><?= htmlspecialchars($product['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="worker_dashboard.php">
            <i class="bi bi-house-door"></i>
            <span>Home</span>
        </a>
        <a href="scan_barcode.php">
            <i class="bi bi-upc-scan"></i>
            <span>Scan</span>
        </a>
        <a href="#" class="active">
            <i class="bi bi-box-seam"></i>
            <span>Products</span>
        </a>
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
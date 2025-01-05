<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

$success_message = '';
$error_message = '';

// Fetch all products with relevant details using JOIN queries
$products_query = "
    SELECT 
        p.product_id,
        p.name AS product_name,
        p.category AS product_category,
        p.current AS current_stock,
        p.min_limit AS minimum_stock,
        p.max_limit AS maximum_stock,
        p.supplier_id,
        s.name AS supplier_name,
        s.contact_info AS supplier_contact
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    ORDER BY p.category, p.name";  // Added ordering
$products_result = $conn->query($products_query);

if (!$products_result) {
    die("Error fetching products: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Base Styles */
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin-top: 2rem;
            padding: 0 1rem;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: white;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            color: #2c3e50;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }

        /* Stock Status Indicators */
        .stock-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-low {
            background-color: #ffe5e5;
            color: #dc3545;
        }

        .status-good {
            background-color: #e5ffe5;
            color: #198754;
        }

        .status-high {
            background-color: #fff3cd;
            color: #664d03;
        }

        /* Info Labels */
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: #6c757d;
            margin-bottom: 0.75rem;
        }

        /* Navigation */
        .back-button {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background-color: #333;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .back-button:hover {
            background-color: #555;
            color: white;
            text-decoration: none;
        }

        /* Action Buttons */
        .action-buttons {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                margin-top: 1rem;
            }
            
            .col-md-4 {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="admin_dashboard.php" class="back-button">
        ← Back to Dashboard
    </a>

    <div class="container">
        <h1 class="text-center mb-4">View Products</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Product Grid -->
        <div class="row g-4">
            <?php while ($product = $products_result->fetch_assoc()): 
                // Calculate stock status
                $stock_status = '';
                $current_stock = (int)$product['current_stock'];
                $min_stock = (int)$product['minimum_stock'];
                $max_stock = (int)$product['maximum_stock'];
                
                if ($current_stock <= $min_stock) {
                    $stock_status = '<span class="stock-status status-low">Low Stock</span>';
                } elseif ($current_stock >= $max_stock) {
                    $stock_status = '<span class="stock-status status-high">Overstocked</span>';
                } else {
                    $stock_status = '<span class="stock-status status-good">In Stock</span>';
                }
            ?>
                <div class="col-md-4">
                    <div class="card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title"><?= htmlspecialchars($product['product_name']) ?></h3>
                            <?= $stock_status ?>
                        </div>
                        
                        <div class="info-label">Product ID</div>
                        <div class="info-value"><?= htmlspecialchars($product['product_id']) ?></div>
                        
                        <div class="info-label">Category</div>
                        <div class="info-value"><?= htmlspecialchars($product['product_category']) ?></div>
                        
                        <div class="info-label">Stock Information</div>
                        <div class="info-value">
                            Current: <?= htmlspecialchars($product['current_stock']) ?><br>
                            Min: <?= htmlspecialchars($product['minimum_stock']) ?> | 
                            Max: <?= htmlspecialchars($product['maximum_stock']) ?>
                        </div>
                        
                        <div class="info-label">Supplier Details</div>
                        <div class="info-value">
                            <?= htmlspecialchars($product['supplier_name'] ?? 'N/A') ?><br>
                            <?= htmlspecialchars($product['supplier_contact'] ?? 'N/A') ?>
                        </div>

                        <!-- <div class="action-buttons">
                            <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="stock_history.php?id=<?= $product['product_id'] ?>" class="btn btn-info btn-sm">History</a>
                            <a href="adjust_stock.php?id=<?= $product['product_id'] ?>" class="btn btn-success btn-sm">Adjust Stock</a>
                        </div> -->
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
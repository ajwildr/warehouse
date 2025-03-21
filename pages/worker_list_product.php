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
            padding-bottom: 2rem;
        }

        /* Content Container */
        .content-container {
            padding: 2rem;
        }

        /* Card Design */
        .product-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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

        /* Search Section */
        .search-box {
            position: relative;
            max-width: 400px;
            margin-bottom: 1.5rem;
        }

        .search-box .bi-search {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .search-input {
            padding-left: 2.5rem;
            border-radius: 2rem;
        }

        /* Responsive Breakpoints */
        @media (max-width: 767px) {
            .table-container {
                display: none;
            }
            
            .product-card {
                padding: 1rem;
            }
        }

        @media (min-width: 768px) {
            .product-cards {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="content-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Products in <?= htmlspecialchars($assigned_category) ?></h2>
            <span class="badge bg-primary"><?= count($products) ?> Products</span>
        </div>

        <!-- Search -->
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" class="form-control search-input" placeholder="Search products...">
        </div>

        <!-- Mobile Product Cards -->
        <div class="product-cards">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-product-id="<?= $product['product_id'] ?>">
                        <div class="title"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="details">
                            <div class="detail-item">
                                <span class="label">ID</span>
                                <span class="value"><?= htmlspecialchars($product['product_id']) ?></span>
                            </div>
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
                <table class="table" id="productsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Supplier</th>
                            <th>Current Stock</th>
                            <th>Min Limit</th>
                            <th>Max Limit</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr data-product-id="<?= $product['product_id'] ?>">
                                <td><?= htmlspecialchars($product['product_id']) ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            filterProducts(searchValue);
        });

        function filterProducts(searchValue) {
            // Get all product cards and table rows
            const cards = document.querySelectorAll('.product-card');
            const rows = document.querySelectorAll('#productsTable tbody tr');

            // Filter the cards (mobile view)
            cards.forEach(card => {
                const title = card.querySelector('.title').innerText.toLowerCase();
                card.style.display = title.includes(searchValue) ? '' : 'none';
            });

            // Filter the table rows (desktop view)
            rows.forEach(row => {
                const name = row.cells[1].innerText.toLowerCase();
                row.style.display = name.includes(searchValue) ? '' : 'none';
            });
        }
    </script>
</body>
</html>
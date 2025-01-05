<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is a Manager
if ($_SESSION['role'] != 'Manager') {
    header("Location: error.php");
    exit;
}

// Fetch manager's assigned category
$user_id = $_SESSION['user_id'];
$category_query = "SELECT assigned_category FROM users WHERE user_id = ?";
$stmt = $conn->prepare($category_query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        header("Location: error.php");
        exit;
    }
    $manager_category = $user['assigned_category'];
    $stmt->close();
} else {
    die("Error fetching assigned category: " . $conn->error);
}

$success_message = '';
$error_message = '';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $supplier_id = $_POST['supplier_id'];
    $current = $_POST['current'];
    $min_limit = $_POST['min_limit'];
    $max_limit = $_POST['max_limit'];

    $query = "INSERT INTO products (name, category, supplier_id, current, min_limit, max_limit) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssiidd", $product_name, $manager_category, $supplier_id, $current, $min_limit, $max_limit);
        if ($stmt->execute()) {
            $success_message = "Product added successfully!";
        } else {
            $error_message = "Failed to add product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}

// Fetch products for the manager's assigned category
$products_query = "
    SELECT p.product_id, p.name, p.current, p.min_limit, p.max_limit, s.name AS supplier_name
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE p.category = ?
";
$products_stmt = $conn->prepare($products_query);
$products_stmt->bind_param("s", $manager_category);
$products_stmt->execute();
$products_result = $products_stmt->get_result();

// Fetch suppliers
$suppliers_query = "SELECT supplier_id, name FROM suppliers";
$suppliers_result = $conn->query($suppliers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
        }
        .back-button {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .back-button i {
            margin-right: 5px;
        }
        .back-button:hover {
            text-decoration: underline;
        }
        .table {
            margin-top: 20px;
        }
        .form-control {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="manager_dashboard.php" class="back-button"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>

        <h1 class="mb-4">Manage Products</h1>

        <!-- Success and Error Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Add New Product</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                    </div>
                    <div class="form-group">
                        <label for="supplier_id">Supplier</label>
                        <select class="form-control" id="supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                                <option value="<?= $supplier['supplier_id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="current">Current Stock</label>
                        <input type="number" class="form-control" id="current" name="current" required>
                    </div>
                    <div class="form-group">
                        <label for="min_limit">Minimum Stock Limit</label>
                        <input type="number" class="form-control" id="min_limit" name="min_limit" required>
                    </div>
                    <div class="form-group">
                        <label for="max_limit">Maximum Stock Limit</label>
                        <input type="number" class="form-control" id="max_limit" name="max_limit" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
            </div>
        </div>

        <!-- List of Products -->
        <h2>Product List for <?= htmlspecialchars($manager_category) ?></h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Supplier</th>
                        <th>Current Stock</th>
                        <th>Min Limit</th>
                        <th>Max Limit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_id']) ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['supplier_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($product['current']) ?></td>
                            <td><?= htmlspecialchars($product['min_limit']) ?></td>
                            <td><?= htmlspecialchars($product['max_limit']) ?></td>
                            <td>
                                <a href="edit_product.php?product_id=<?= $product['product_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_product.php?product_id=<?= $product['product_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

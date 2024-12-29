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
    <title>Product List</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .table tbody tr {
            cursor: pointer;
        }
        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Products in Your Category: <?= htmlspecialchars($assigned_category) ?></h1>
        
        <?php if (!empty($products)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Supplier ID</th>
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
        <?php else: ?>
            <p>No products found in your assigned category.</p>
        <?php endif; ?>

        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>

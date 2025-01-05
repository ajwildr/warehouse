<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is authorized (Manager only)
if ($_SESSION['role'] != 'Manager') {
    header("Location: error.php");
    exit;
}

// Fetch the manager's assigned category
$user_id = $_SESSION['user_id'];
$category_query = "SELECT assigned_category FROM users WHERE user_id = ?";
$stmt = $conn->prepare($category_query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $manager_category = $user['assigned_category'];
    $stmt->close();
} else {
    die("Error fetching assigned category: " . $conn->error);
}

$success_message = '';
$error_message = '';

// Fetch the product details if a product_id is passed in the URL
if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // Fetch product data from the products table
    $product_query = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($product_query);
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        if (!$product) {
            $error_message = "Product not found.";
        }
        $stmt->close();
    } else {
        $error_message = "Failed to fetch product details: " . $conn->error;
    }

    // Handle the form submission for updating the product
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $product_name = $_POST['product_name'];
        $supplier_id = $_POST['supplier_id'];
        $current = $_POST['current'];
        $min_limit = $_POST['min_limit'];
        $max_limit = $_POST['max_limit'];

        // Update product in the database
        $update_query = "UPDATE products SET name = ?, supplier_id = ?, current = ?, min_limit = ?, max_limit = ? WHERE product_id = ?";
        $update_stmt = $conn->prepare($update_query);
        if ($update_stmt) {
            $update_stmt->bind_param("siiiii", $product_name, $supplier_id, $current, $min_limit, $max_limit, $product_id);
            if ($update_stmt->execute()) {
                $success_message = "Product updated successfully!";
            } else {
                $error_message = "Failed to update product: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $error_message = "Failed to prepare statement: " . $conn->error;
        }
    }
} else {
    header("Location: manage_products.php"); // Redirect if no product_id is provided
    exit;
}

// Fetch all suppliers for the dropdown
$suppliers_query = "SELECT supplier_id, name FROM suppliers";
$suppliers_result = $conn->query($suppliers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-lg p-4">
            <h1 class="mb-4">Edit Product</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <!-- Product Edit Form -->
            <form method="POST">
                <div class="mb-3">
                    <label for="product_name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <input type="text" class="form-control" id="category" name="category" value="<?= htmlspecialchars($manager_category) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select class="form-control" id="supplier_id" name="supplier_id" required>
                        <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                            <option value="<?= $supplier['supplier_id'] ?>" <?= ($supplier['supplier_id'] == $product['supplier_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($supplier['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="current" class="form-label">Current Stock</label>
                    <input type="number" class="form-control" id="current" name="current" value="<?= $product['current'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="min_limit" class="form-label">Minimum Stock Limit</label>
                    <input type="number" class="form-control" id="min_limit" name="min_limit" value="<?= $product['min_limit'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="max_limit" class="form-label">Maximum Stock Limit</label>
                    <input type="number" class="form-control" id="max_limit" name="max_limit" value="<?= $product['max_limit'] ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Product</button>
            </form>

            <a href="manage_products.php" class="btn btn-secondary w-100 mt-3">Back to Products List</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

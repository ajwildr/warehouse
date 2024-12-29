<?php
session_start();
require '../includes/db_connect.php';

// Ensure the user is an Accounting staff
if ($_SESSION['role'] != 'Accounting') {
    header("Location: error.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle incoming stock submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Fetch product details
    $product_query = "SELECT name, current, max_limit FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $new_quantity = $product['current'] + $quantity;

        // Check if the new quantity exceeds the maximum limit
        if ($new_quantity > $product['max_limit']) {
            echo "<script>alert('Quantity exceeds max limit! Please report to the manager.');</script>";
        } else {
            // Insert into stock table
            $insert_stock_query = "INSERT INTO stock (product_id, quantity, transaction_type) VALUES (?, ?, 'Incoming')";
            $stmt = $conn->prepare($insert_stock_query);
            $stmt->bind_param("ii", $product_id, $quantity);

            if ($stmt->execute()) {
                // Update the current quantity in the products table
                $update_product_query = "UPDATE products SET current = ? WHERE product_id = ?";
                $stmt = $conn->prepare($update_product_query);
                $stmt->bind_param("ii", $new_quantity, $product_id);
                if ($stmt->execute()) {
                    $success_message = "Stock updated successfully!";
                } else {
                    $error_message = "Failed to update product stock: " . $stmt->error;
                }
            } else {
                $error_message = "Failed to insert stock: " . $stmt->error;
            }
        }
    } else {
        $error_message = "Invalid product selected.";
    }
    $stmt->close();
}

// Fetch products for the dropdown
$products_query = "SELECT product_id, name FROM products";
$products_result = $conn->query($products_query);

// Fetch incoming stock transactions
$incoming_query = "
    SELECT 
        s.stock_id, 
        p.name AS product_name, 
        s.quantity, 
        s.created_at 
    FROM stock s
    JOIN products p ON s.product_id = p.product_id
    WHERE s.transaction_type = 'Incoming'
    ORDER BY s.created_at DESC";
$incoming_result = $conn->query($incoming_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incoming Stocks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Incoming Stocks</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Incoming Stock Form -->
        <form method="POST">
            <h2>Add Incoming Stock</h2>
            <div class="mb-3">
                <label for="product_id" class="form-label">Product</label>
                <select class="form-control" id="product_id" name="product_id" required>
                    <option value="">Select Product</option>
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <option value="<?= $product['product_id'] ?>">
                            <?= $product['name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Stock</button>
        </form>

        <!-- Incoming Stock Table -->
        <h2>Incoming Stock Transactions</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($incoming_result->num_rows > 0): ?>
                    <?php while ($row = $incoming_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['stock_id']) ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No incoming stock transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

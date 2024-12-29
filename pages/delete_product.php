<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is authorized (Manager only)
if ($_SESSION['role'] != 'Manager') {
    header("Location: error.php");
    exit;
}

$success_message = '';
$error_message = '';

// Fetch the product_id from URL
if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // Delete the product from the database
    $delete_query = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($delete_query);
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $success_message = "Product deleted successfully!";
        } else {
            $error_message = "Failed to delete product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
} else {
    $error_message = "No product_id provided.";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Delete Product</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <a href="manage_products.php" class="btn btn-secondary mt-3">Back to Products List</a>
    </div>
</body>
</html>

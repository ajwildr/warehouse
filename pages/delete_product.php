<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is authorized (Manager only)
if ($_SESSION['role'] != 'Manager') {
    header("Location: error.php");
    exit;
}

// Check if product_id is provided
if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // Delete the product from the database
    $delete_query = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($delete_query);
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect to the manage products page
header("Location: manage_products.php");
exit;

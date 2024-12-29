<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

// Messages for success or error
$success_message = '';
$error_message = '';

// Handle Supplier Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    $supplier_name = $_POST['supplier_name'];
    $contact_details = $_POST['contact_details'];
    $categories = $_POST['categories'];
    $email = $_POST['email'];

    $query = "INSERT INTO suppliers (name, contact_info, email, product_categories) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssss", $supplier_name, $contact_details, $email, $categories);
        if ($stmt->execute()) {
            $success_message = "Supplier added successfully!";
        } else {
            $error_message = "Failed to add supplier: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}

// Fetch all suppliers
$suppliers_query = "SELECT * FROM suppliers";
$suppliers_result = $conn->query($suppliers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Suppliers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Manage Suppliers</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Add Supplier Form -->
        <form method="POST">
            <h2>Add Supplier</h2>
            <div class="form-group">
                <label for="supplier_name">Supplier Name</label>
                <input type="text" id="supplier_name" name="supplier_name" required class="form-control">
            </div>
            <div class="form-group">
                <label for="contact_details">Contact Details</label>
                <textarea id="contact_details" name="contact_details" required class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label for="categories">Categories (Comma-separated)</label>
                <input type="text" id="categories" name="categories" required class="form-control" placeholder="E.g., Phones, Mixers">
            </div>
            <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
        </form>

        <!-- Supplier List -->
        <h2>Existing Suppliers</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Supplier ID</th>
                    <th>Supplier Name</th>
                    <th>Contact Details</th>
                    <th>Email</th>
                    <th>Categories</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $supplier['supplier_id'] ?></td>
                        <td><?= $supplier['name'] ?></td>
                        <td><?= $supplier['contact_info'] ?></td>
                        <td><?= $supplier['email'] ?></td>
                        <td><?= $supplier['product_categories'] ?></td>
                        <td>
                            <a href="edit_supplier.php?supplier_id=<?= $supplier['supplier_id'] ?>" class="btn btn-info">Edit</a>
                            <a href="delete_supplier.php?supplier_id=<?= $supplier['supplier_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

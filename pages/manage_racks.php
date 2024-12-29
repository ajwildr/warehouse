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

// Handle Assign Rack
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $rack_location = $_POST['rack_location'];

    $query = "INSERT INTO rack (product_id, rack_location, manager_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isi", $product_id, $rack_location, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success_message = "Rack assigned successfully, and barcode generated!";
        } else {
            $error_message = "Failed to assign rack: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}

// Fetch products without assigned racks
$products_query = "
    SELECT p.product_id, p.name, p.category 
    FROM products p
    WHERE NOT EXISTS (
        SELECT 1 FROM rack r WHERE r.product_id = p.product_id
    )
";
$products_result = $conn->query($products_query);

// Fetch all rack assignments
$racks_query = "
    SELECT r.rack_id, r.rack_location, p.name AS product_name, p.category, u.username AS manager_name
    FROM rack r
    JOIN products p ON r.product_id = p.product_id
    JOIN users u ON r.manager_id = u.user_id
";
$racks_result = $conn->query($racks_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Racks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Manage Racks</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Assign Rack Form -->
        <form method="POST">
            <h2>Assign Rack</h2>
            <div class="mb-3">
                <label for="product_id" class="form-label">Product</label>
                <select class="form-control" id="product_id" name="product_id" required>
                    <option value="">Select Product</option>
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <option value="<?= $product['product_id'] ?>">
                            <?= $product['name'] ?> (<?= $product['category'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="rack_location" class="form-label">Rack Location</label>
                <input type="text" class="form-control" id="rack_location" name="rack_location" required>
            </div>
            <button type="submit" class="btn btn-primary">Assign Rack</button>
        </form>

        <!-- List of Assigned Racks -->
        <h2>Assigned Racks</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Rack ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Rack Location</th>
                    <th>Assigned By</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php while ($rack = $racks_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $rack['rack_id'] ?></td>
                        <td><?= $rack['product_name'] ?></td>
                        <td><?= $rack['category'] ?></td>
                        <td><?= $rack['rack_location'] ?></td>
                        <td><?= $rack['manager_name'] ?></td>
                        
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

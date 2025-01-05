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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rack'])) {
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

// Handle Delete Rack
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rack'])) {
    $rack_id = intval($_POST['rack_id']);

    $delete_query = "DELETE FROM rack WHERE rack_id = ?";
    $stmt = $conn->prepare($delete_query);
    if ($stmt) {
        $stmt->bind_param("i", $rack_id);
        if ($stmt->execute()) {
            $success_message = "Rack deleted successfully!";
        } else {
            $error_message = "Failed to delete rack: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare delete statement: " . $conn->error;
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
        .form-control {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="manager_dashboard.php" class="back-button"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        <h1 class="mb-4">Manage Racks</h1>

        <!-- Success and Error Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Assign Rack Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Assign Rack</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="product_id">Product</label>
                        <select class="form-control" id="product_id" name="product_id" required>
                            <option value="">Select Product</option>
                            <?php while ($product = $products_result->fetch_assoc()): ?>
                                <option value="<?= $product['product_id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['category']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="rack_location">Rack Location</label>
                        <input type="text" class="form-control" id="rack_location" name="rack_location" required>
                    </div>
                    <button type="submit" name="assign_rack" class="btn btn-primary">Assign Rack</button>
                </form>
            </div>
        </div>

        <!-- List of Assigned Racks -->
        <h2>Assigned Racks</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Rack ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Rack Location</th>
                        <th>Assigned By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($rack = $racks_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($rack['rack_id']) ?></td>
                            <td><?= htmlspecialchars($rack['product_name']) ?></td>
                            <td><?= htmlspecialchars($rack['category']) ?></td>
                            <td><?= htmlspecialchars($rack['rack_location']) ?></td>
                            <td><?= htmlspecialchars($rack['manager_name']) ?></td>
                            <td>
                                <!-- Delete Button -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="rack_id" value="<?= $rack['rack_id'] ?>">
                                    <button type="submit" name="delete_rack" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this rack?');">
                                        Delete
                                    </button>
                                </form>
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

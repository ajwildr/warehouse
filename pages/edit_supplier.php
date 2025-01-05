<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

$supplier_id = $_GET['supplier_id'] ?? null;
$success_message = '';
$error_message = '';

// Check if supplier_id is provided
if (!$supplier_id) {
    header("Location: manage_suppliers.php");
    exit;
}

// Fetch supplier details
$query = "SELECT * FROM suppliers WHERE supplier_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();
    
    if (!$supplier) {
        header("Location: manage_suppliers.php");
        exit;
    }
} else {
    $error_message = "Failed to fetch supplier details: " . $conn->error;
}

// Handle supplier update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_supplier'])) {
    $supplier_name = $_POST['supplier_name'];
    $contact_details = $_POST['contact_details'];
    $categories = $_POST['categories'];
    $email = $_POST['email'];

    $query = "UPDATE suppliers SET name = ?, contact_info = ?, product_categories = ?, email = ? WHERE supplier_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssssi", $supplier_name, $contact_details, $categories, $email, $supplier_id);
        if ($stmt->execute()) {
            $success_message = "Supplier updated successfully!";
        } else {
            $error_message = "Failed to update supplier: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 50px;
            max-width: 600px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        .back-button {
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: #007bff;
            margin-bottom: 20px;
        }
        .back-button:hover {
            color: #0056b3;
        }
        .form-control {
            border-radius: 4px;
        }
        .alert {
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="manage_suppliers.php" class="back-button">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 1-.5.5H3.707l3.147 3.146a.5.5 0 0 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 0 1 .708.708L3.707 7.5H14.5A.5.5 0 0 1 15 8z"/>
            </svg>
            Back to Manage Suppliers
        </a>

        <h1 class="mb-4 text-center">Edit Supplier</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Edit Supplier Form -->
        <form method="POST">
            <div class="mb-3">
                <label for="supplier_name" class="form-label">Supplier Name</label>
                <input type="text" id="supplier_name" name="supplier_name" value="<?= htmlspecialchars($supplier['name']) ?>" required class="form-control">
            </div>
            <div class="mb-3">
                <label for="contact_details" class="form-label">Contact Details</label>
                <textarea id="contact_details" name="contact_details" required class="form-control" rows="3"><?= htmlspecialchars($supplier['contact_info']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($supplier['email']) ?>" required class="form-control">
            </div>
            <div class="mb-3">
                <label for="categories" class="form-label">Categories (Comma-separated)</label>
                <input type="text" id="categories" name="categories" value="<?= htmlspecialchars($supplier['product_categories']) ?>" required class="form-control" placeholder="E.g., Phones, Mixers">
            </div>
            <button type="submit" name="update_supplier" class="btn btn-primary w-100">Update Supplier</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Back Button Styles */
        .back-button {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            background-color: #333;
            color: white;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .back-button:hover {
            background-color: #555;
            color: white;
            text-decoration: none;
        }

        /* Form Control Focus */
        .form-control:focus {
            border-color: #666;
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        }

        /* Button Styles */
        .btn-primary {
            background-color: #333;
            border-color: #333;
        }

        .btn-primary:hover {
            background-color: #555;
            border-color: #555;
        }

        /* Table Adjustments */
        .table-dark {
            background-color: #333;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 0.5rem;
            }

            /* Stack buttons on mobile */
            .btn-group-sm {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-sm {
                width: 100%;
            }
        }

        /* Touch-friendly adjustments */
        @media (hover: none) {
            .btn, .form-control {
                min-height: 44px;
            }
        }
    </style>
</head>
<body>
    <a href="admin_dashboard.php" class="back-button">
        ← Back
    </a>

    <div class="container mt-5">
        <h1 class="mb-4">Manage Suppliers</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success_message) ?> </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error_message) ?> </div>
        <?php endif; ?>

        <!-- Add Supplier Form -->
        <form method="POST" class="mb-5">
            <h2 class="h4">Add Supplier</h2>
            <div class="mb-3">
                <label for="supplier_name" class="form-label">Supplier Name</label>
                <input type="text" id="supplier_name" name="supplier_name" required class="form-control">
            </div>
            <div class="mb-3">
                <label for="contact_details" class="form-label">Contact Details</label>
                <textarea id="contact_details" name="contact_details" required class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>
            <div class="mb-3">
                <label for="categories" class="form-label">Categories (Comma-separated)</label>
                <input type="text" id="categories" name="categories" required class="form-control" placeholder="E.g., Phones, Mixers">
            </div>
            <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
        </form>

        <!-- Supplier List -->
        <h2 class="h4">Existing Suppliers</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
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
                            <td><?= htmlspecialchars($supplier['supplier_id']) ?></td>
                            <td><?= htmlspecialchars($supplier['name']) ?></td>
                            <td><?= htmlspecialchars($supplier['contact_info']) ?></td>
                            <td><?= htmlspecialchars($supplier['email']) ?></td>
                            <td><?= htmlspecialchars($supplier['product_categories']) ?></td>
                            <td>
                                <div class="btn-group-sm">
                                    <a href="edit_supplier.php?supplier_id=<?= $supplier['supplier_id'] ?>" class="btn btn-info btn-sm">Edit</a>
                                    <a href="delete_supplier.php?supplier_id=<?= $supplier['supplier_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this supplier?')">Delete</a>
                                </div>
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
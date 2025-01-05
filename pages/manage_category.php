<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is logged in and has the Admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submission to add a new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name']);

    if (empty($cat_name)) {
        $error_message = "Category name cannot be empty.";
    } else {
        // Insert the new category into the database
        $insertQuery = "INSERT INTO categories (cat_name) VALUES (?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("s", $cat_name);

        if ($stmt->execute()) {
            $success_message = "Category added successfully!";
        } else {
            $error_message = "Error adding category: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $cat_id = intval($_POST['cat_id']);

    // Delete the category from the database
    $deleteQuery = "DELETE FROM categories WHERE cat_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $cat_id);

    if ($stmt->execute()) {
        $success_message = "Category deleted successfully!";
    } else {
        $error_message = "Error deleting category: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch all categories
$categories = [];
$query = "SELECT cat_id, cat_name FROM categories ORDER BY cat_id ASC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Category - Admin</title>
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
        }
        .back-button i {
            margin-right: 5px;
        }
        .back-button:hover {
            text-decoration: underline;
        }
        .table {
            margin-top: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-button mb-3"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        <h1 class="mb-4">Create Category</h1>

        <!-- Success and Error Messages -->
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <!-- Add Category Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">Add New Category</h2>
                <form method="POST">
                    <div class="form-group mb-3">
                        <label for="cat_name" class="form-label">Category Name</label>
                        <input type="text" id="cat_name" name="cat_name" class="form-control" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success">Add Category</button>
                </form>
            </div>
        </div>

        <!-- Category List -->
        <h2>Category List</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['cat_id']) ?></td>
                                <td><?= htmlspecialchars($category['cat_name']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="cat_id" value="<?= $category['cat_id'] ?>">
                                        <button type="submit" name="delete_category" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category?');">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Category - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Create Category</h1>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>

        <?php if ($success_message): ?>
            <div class="alert alert-success"> <?= htmlspecialchars($success_message) ?> </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"> <?= htmlspecialchars($error_message) ?> </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="cat_name">Category Name</label>
                <input type="text" id="cat_name" name="cat_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success">Add Category</button>
        </form>
    </div>
</body>
</html>

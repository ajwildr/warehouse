<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

$success_message = '';
$error_message = '';

// Fetch categories for the dropdown
$categories_query = "SELECT cat_name FROM categories";
$categories_result = $conn->query($categories_query);

// Fetch the user ID from the query string
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Fetch the user's current details
    $query = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
        } else {
            $error_message = "User not found!";
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }

    // Handle User Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password']; // Keep old password if not updated
        $role = $_POST['role'];
        $category = ($role === 'Manager' || $role === 'Worker') ? $_POST['category'] : null;

        $update_query = "UPDATE users SET username = ?, password = ?, role = ?, assigned_category = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        if ($update_stmt) {
            $update_stmt->bind_param("ssssi", $username, $password, $role, $category, $user_id);
            if ($update_stmt->execute()) {
                $success_message = "User updated successfully!";
            } else {
                $error_message = "Failed to update user: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $error_message = "Failed to prepare statement: " . $conn->error;
        }
    }
} else {
    $error_message = "User ID not specified!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit User</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Edit User Form -->
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= $user['username'] ?>" required class="form-control">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required onchange="toggleCategoryDropdown()">
                    <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Manager" <?= $user['role'] === 'Manager' ? 'selected' : '' ?>>Manager</option>
                    <option value="Accounting" <?= $user['role'] === 'Accounting' ? 'selected' : '' ?>>Accounting</option>
                    <option value="Worker" <?= $user['role'] === 'Worker' ? 'selected' : '' ?>>Worker</option>
                </select>
            </div>
            <div class="form-group" id="category-group" style="display: <?= ($user['role'] === 'Manager' || $user['role'] === 'Worker') ? 'block' : 'none' ?>;">
                <label for="category">Category (For Managers and Workers)</label>
                <select id="category" name="category" class="form-control">
                    <option value="">Select Category</option>
                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?= $category['cat_name'] ?>" <?= $user['assigned_category'] === $category['cat_name'] ? 'selected' : '' ?>>
                            <?= $category['cat_name'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
        </form>
    </div>

    <script>
        function toggleCategoryDropdown() {
            const role = document.getElementById('role').value;
            const categoryGroup = document.getElementById('category-group');
            if (role === 'Manager' || role === 'Worker') {
                categoryGroup.style.display = 'block';
            } else {
                categoryGroup.style.display = 'none';
            }
        }
    </script>
</body>
</html>

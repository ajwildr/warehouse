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
        $email = $_POST['email'];
        $password = $_POST['password'] ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password']; // Keep old password if not updated
        $role = $_POST['role'];
        $category = ($role === 'Manager' || $role === 'Worker') ? $_POST['category'] : null;

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format";
        } else {
            $update_query = "UPDATE users SET username = ?, email = ?, password = ?, role = ?, assigned_category = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            if ($update_stmt) {
                $update_stmt->bind_param("sssssi", $username, $email, $password, $role, $category, $user_id);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: Arial, sans-serif;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px;
        }
        .card-body { 
            padding: 30px; 
        }
        .form-label {
            font-weight: 500;
        }
        .btn-primary {
            padding: 8px 20px;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <a href="manage_users.php" class="btn btn-secondary mb-4">
                    <i class="bi bi-arrow-left"></i> Back to Users
                </a>
                
                <div class="card">
                    <div class="card-header">
                        <h1 class="text-center m-0">Edit User</h1>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?= $success_message ?></div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-control">
                                <div class="invalid-feedback">Please provide a username.</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address<span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" 
                                       required class="form-control" 
                                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select id="role" name="role" class="form-select" required onchange="toggleCategoryDropdown()">
                                    <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="Manager" <?= $user['role'] === 'Manager' ? 'selected' : '' ?>>Manager</option>
                                    <option value="Accounting" <?= $user['role'] === 'Accounting' ? 'selected' : '' ?>>Accounting</option>
                                    <option value="Worker" <?= $user['role'] === 'Worker' ? 'selected' : '' ?>>Worker</option>
                                </select>
                                <div class="invalid-feedback">Please select a role.</div>
                            </div>
                            <div class="mb-3" id="category-group" style="display: <?= ($user['role'] === 'Manager' || $user['role'] === 'Worker') ? 'block' : 'none' ?>;">
                                <label for="category" class="form-label">Category (For Managers and Workers)</label>
                                <select id="category" name="category" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?= $category['cat_name'] ?>" <?= $user['assigned_category'] === $category['cat_name'] ? 'selected' : '' ?>>
                                            <?= $category['cat_name'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="text-center mt-4">
                                <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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

        // Bootstrap form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
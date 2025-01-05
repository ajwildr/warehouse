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

// Fetch categories for the dropdown
$categories_query = "SELECT cat_id, cat_name FROM categories";
$categories_result = $conn->query($categories_query);

// Handle User Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $category = isset($_POST['category']) && $role !== 'Admin' && $role !== 'Accounting' ? $_POST['category'] : null;

    // Validate email if provided
    if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        $query = "INSERT INTO users (username, email, password, role, assigned_category) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("sssss", $username, $email, $password, $role, $category);
            if ($stmt->execute()) {
                $success_message = "User added successfully!";
            } else {
                $error_message = "Failed to add user: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Failed to prepare statement: " . $conn->error;
        }
    }
}

// Fetch all users
$users_query = "SELECT * FROM users";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        .form-section {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-section {
            margin-top: 30px;
        }
        .table {
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn {
            margin-right: 5px;
        }
        .alert {
            border-radius: 10px;
        }
        .back-button {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Added Back Button -->
        <div class="back-button">
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <h1 class="text-center mb-4">Manage Users</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"> <?= $success_message ?> </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"> <?= $error_message ?> </div>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="form-section">
            <h2 class="mb-4">Add User</h2>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">Username<span class="text-danger">*</span></label>
                    <input type="text" id="username" name="username" required class="form-control">
                </div>
                <div class="mb-3">
    <label for="email" class="form-label">Email Address<span class="text-danger">*</span></label>
    <input type="email" id="email" name="email" class="form-control" required
           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
           title="Please enter a valid email address">
</div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password<span class="text-danger">*</span></label>
                    <input type="password" id="password" name="password" required class="form-control">
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role<span class="text-danger">*</span></label>
                    <select id="role" name="role" class="form-select" required onchange="toggleCategoryDropdown()">
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Manager">Manager</option>
                        <option value="Accounting">Accounting</option>
                        <option value="Worker">Worker</option>
                    </select>
                </div>
                <div class="mb-3" id="category-group" style="display: none;">
                    <label for="category" class="form-label">Category (For Managers and Workers)</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">Select Category</option>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?= $category['cat_name'] ?>">
                                <?= $category['cat_name'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </form>
        </div>

        <!-- User List -->
        <div class="table-section">
            <h2 class="mb-4">Existing Users</h2>
            <table class="table table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td>
                                <?php if (!empty($user['email'])): ?>
                                    <?= htmlspecialchars($user['email']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Not provided</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= $user['assigned_category'] ?? 'N/A' ?></td>
                            <td>
                                <a href="edit_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-info btn-sm">Edit</a>
                                <a href="delete_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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

        // Client-side email validation
        document.getElementById('email').addEventListener('input', function(e) {
            const email = e.target.value;
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

            if (email && !emailRegex.test(email)) {
                e.target.setCustomValidity('Please enter a valid email address');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
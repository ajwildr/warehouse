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
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-control {
            margin-bottom: 15px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .email-status {
            font-style: italic;
            color: #666;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: .5rem;
            font-weight: 500;
        }
        .table td, .table th {
            padding: .75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        .input-required {
            color: red;
            margin-left: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Users</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Add User Form -->
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" novalidate>
            <h2>Add User</h2>
            <div class="form-group">
                <label for="username">Username<span class="input-required">*</span></label>
                <input type="text" id="username" name="username" required class="form-control">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Please enter a valid email address">
                <small class="form-text text-muted">Optional - Leave blank if not available</small>
            </div>
            <div class="form-group">
                <label for="password">Password<span class="input-required">*</span></label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>
            <div class="form-group">
                <label for="role">Role<span class="input-required">*</span></label>
                <select id="role" name="role" class="form-control" required onchange="toggleCategoryDropdown()">
                    <option value="">Select Role</option>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Worker">Worker</option>
                </select>
            </div>
            <div class="form-group" id="category-group" style="display: none;">
                <label for="category">Category (For Managers and Workers)</label>
                <select id="category" name="category" class="form-control">
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

        <!-- User List -->
        <h2>Existing Users</h2>
        <table class="table">
            <thead>
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
                                <span class="email-status">Not provided</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td><?= $user['assigned_category'] ?? 'N/A' ?></td>
                        <td>
                            <a href="edit_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-info">Edit</a>
                            <a href="delete_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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

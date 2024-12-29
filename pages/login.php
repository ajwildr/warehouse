<?php
session_start();
require '../includes/db_connect.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check for empty fields
    if (empty($username) || empty($password)) {
        $error_message = "Both fields are required.";
    } else {
        // Query to fetch user details
        $query = "SELECT user_id, username, password, role, assigned_category FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Set assigned_category for relevant roles
                if (in_array($user['role'], ['Manager', 'Worker']) && !empty($user['assigned_category'])) {
                    $_SESSION['assigned_category'] = $user['assigned_category'];
                }

                // Redirect based on role
                switch ($user['role']) {
                    case 'Admin':
                    case 'Manager':
                    case 'Accounting':
                    case 'Worker':
                        header("Location: dashboard.php");
                        break;
                    default:
                        $error_message = "Invalid role.";
                }
                exit;
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No user found with that username.";
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
    <title>Login - Warehouse Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if ($error_message): ?>
            <div class="error-message"><?= $error_message ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>
</html>

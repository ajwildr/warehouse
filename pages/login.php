<?php 
session_start(); 
require '../includes/db_connect.php';

$error_message = '';
$redirect_url = '';

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
                
                // Set redirect URL based on role
                switch ($user['role']) {
                    case 'Admin':
                        $redirect_url = 'admin_dashboard.php';
                        break;
                    case 'Manager':
                        $redirect_url = 'manager_dashboard.php';
                        break;
                    case 'Accounting':
                        $redirect_url = 'accounting_dashboard.php';
                        break;
                    case 'Worker':
                        $redirect_url = 'worker_dashboard.php';
                        break;
                    default:
                        $error_message = "Invalid role.";
                }
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, #4e73df, #36b9cc);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
            margin-bottom: 0;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating > .form-control {
            padding: 1rem 0.75rem;
        }

        .btn-login {
            background: linear-gradient(to right, #4e73df, #36b9cc);
            border: none;
            color: white;
            padding: 0.8rem;
            border-radius: 5px;
            width: 100%;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .error-alert {
            background-color: #fff3f3;
            border-left: 4px solid #dc3545;
            color: #dc3545;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }

        .input-group-text {
            background: transparent;
            cursor: pointer;
        }

        .password-toggle {
            cursor: pointer;
            color: #666;
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p class="text-muted">Please login to your account</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="needs-validation" novalidate>
            <div class="form-floating mb-3">
                <input type="text" 
                       class="form-control" 
                       id="username" 
                       name="username" 
                       placeholder="Username"
                       required>
                <label for="username">Username</label>
            </div>

            <div class="form-floating mb-4">
                <input type="password" 
                       class="form-control" 
                       id="password" 
                       name="password" 
                       placeholder="Password"
                       required>
                <label for="password">Password</label>
                <span class="password-toggle position-absolute top-50 end-0 translate-middle-y pe-3"
                      onclick="togglePassword()">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-login">
                Sign In
                <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Perform redirect if URL is set
        <?php if (!empty($redirect_url)): ?>
            window.location.href = '<?php echo $redirect_url; ?>';
        <?php endif; ?>

        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }

        // Form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>

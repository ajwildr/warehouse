<?php
require '../includes/db_connect.php'; // Ensure this includes your database connection script

// Define the user details
$username = 'admin1';
$password = password_hash('ajai', PASSWORD_BCRYPT); // Hash the password
$role = 'Admin';
$email = 'admin@example.com'; // Placeholder email
$assigned_category = null; // Admin doesn't need a category
$created_at = date('Y-m-d H:i:s'); // Current timestamp

// Insert query
$query = "INSERT INTO users (username, password, role, email, assigned_category, created_at) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("ssssss", $username, $password, $role, $email, $assigned_category, $created_at);
    if ($stmt->execute()) {
        echo "User 'admin' created successfully!";
    } else {
        echo "Error creating user: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

$conn->close();
?>

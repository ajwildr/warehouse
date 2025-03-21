<?php
require '../includes/db_connect.php';

// Create categories table
$sql_categories = "CREATE TABLE IF NOT EXISTS categories (
    cat_id INT NOT NULL AUTO_INCREMENT,
    cat_name VARCHAR(255) NOT NULL,
    PRIMARY KEY (cat_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_categories) === TRUE) {
    echo "Categories table created successfully<br>";
} else {
    echo "Error creating categories table: " . $conn->error . "<br>";
}

// Create products table
$sql_products = "CREATE TABLE IF NOT EXISTS products (
    product_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    supplier_id INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    current INT NOT NULL DEFAULT 0,
    min_limit INT NOT NULL DEFAULT 0,
    max_limit INT NOT NULL DEFAULT 0,
    PRIMARY KEY (product_id),
    KEY supplier_id (supplier_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_products) === TRUE) {
    echo "Products table created successfully<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Create rack table
$sql_rack = "CREATE TABLE IF NOT EXISTS rack (
    rack_id INT NOT NULL AUTO_INCREMENT,
    product_id INT NOT NULL,
    rack_location VARCHAR(50) NOT NULL,
    manager_id INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (rack_id),
    KEY product_id (product_id),
    KEY manager_id (manager_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_rack) === TRUE) {
    echo "Rack table created successfully<br>";
} else {
    echo "Error creating rack table: " . $conn->error . "<br>";
}

// Create stock table
$sql_stock = "CREATE TABLE IF NOT EXISTS stock (
    stock_id INT NOT NULL AUTO_INCREMENT,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    transaction_type ENUM('Incoming','Outgoing') NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (stock_id),
    KEY product_id (product_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_stock) === TRUE) {
    echo "Stock table created successfully<br>";
} else {
    echo "Error creating stock table: " . $conn->error . "<br>";
}

// Create suppliers table
$sql_suppliers = "CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_info VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    product_categories TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (supplier_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_suppliers) === TRUE) {
    echo "Suppliers table created successfully<br>";
} else {
    echo "Error creating suppliers table: " . $conn->error . "<br>";
}

// Create users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    user_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Manager','Accounting','Worker') NOT NULL,
    email VARCHAR(100) NOT NULL,
    assigned_category VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY username (username)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_users) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create tasks table
$sql_tasks = "CREATE TABLE IF NOT EXISTS tasks (
    task_id INT NOT NULL AUTO_INCREMENT,
    assigned_by INT NOT NULL,
    assigned_to INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id),
    KEY assigned_by (assigned_by),
    KEY assigned_to (assigned_to)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_tasks) === TRUE) {
    echo "Tasks table created successfully<br>";
} else {
    echo "Error creating tasks table: " . $conn->error . "<br>";
}

// Create notifications table
$sql_notifications = "CREATE TABLE IF NOT EXISTS notifications (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Unread', 'Read') DEFAULT 'Unread',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql_notifications) === TRUE) {
    echo "Notifications table created successfully<br>";
} else {
    echo "Error creating notifications table: " . $conn->error . "<br>";
}

// Insert sample data
// Categories
$categories = [
    ['electrinicccss'],
    ['Phones'],
    ['TV'],
    ['grocieries'],
    ['oops']
];

$stmt = $conn->prepare("INSERT INTO categories (cat_name) VALUES (?)");
foreach ($categories as $category) {
    $stmt->bind_param("s", $category[0]);
    $stmt->execute();
}

// Users - Note: Password 'ajai' is encrypted using password_hash()
$hashed_password = password_hash('ajai', PASSWORD_DEFAULT);
$users = [
    ['admin', 'Admin', 'm@gmail.com', 'Phones'],
    ['acc', 'Accounting', 'nothing@gmail.com', NULL],
    ['pmanager', 'Manager', '', 'Phones'],
    ['pworker', 'Worker', '', 'Phones'],
    ['Ajmalde', 'Accounting', 'jayai002@outlook.com', NULL],
    ['ajmal', 'Manager', 'ajai519451@gmail.com', 'grocieries']
];

$stmt = $conn->prepare("INSERT INTO users (username, password, role, email, assigned_category) VALUES (?, ?, ?, ?, ?)");
foreach ($users as $user) {
    $stmt->bind_param("sssss", $user[0], $hashed_password, $user[1], $user[2], $user[3]);
    $stmt->execute();
}

// Add more insert statements for other tables as needed...

$conn->close();
echo "Database setup completed!";
?>
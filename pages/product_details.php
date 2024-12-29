<?php
session_start();
require '../includes/db_connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Worker') {
    header("Location: error.php");
    exit;
}

// Get the rack ID from the query string
$rack_id = $_GET['rack_id'] ?? null;

if (!$rack_id) {
    die("Rack ID is required.");
}

// Query to fetch rack details along with product name and manager name
$query = "
    SELECT 
        r.rack_id, 
        r.rack_location, 
        r.created_at, 
        p.name AS product_name, 
        u.username AS manager_name
    FROM 
        rack r
    JOIN 
        products p ON r.product_id = p.product_id
    JOIN 
        users u ON r.manager_id = u.user_id
    WHERE 
        r.rack_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $rack_id); // Rack ID may contain alphanumeric characters
    $stmt->execute();
    $result = $stmt->get_result();
    $rack = $result->fetch_assoc(); // Fetch a single row
    $stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .card h2 {
            margin: 0 0 10px;
        }
        .card p {
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            text-align: center;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Product Details</h1>
        
        <?php if ($rack): ?>
            <div class="card">
                <h2>Product Name: <?= htmlspecialchars($rack['product_name']) ?></h2>
                <p><strong>Rack ID:</strong> <?= htmlspecialchars($rack['rack_id']) ?></p>
                <p><strong>Rack Location:</strong> <?= htmlspecialchars($rack['rack_location']) ?></p>
                <p><strong>Manager Name:</strong> <?= htmlspecialchars($rack['manager_name']) ?></p>
                <p><strong>Created At:</strong> <?= htmlspecialchars($rack['created_at']) ?></p>
            </div>
        <?php else: ?>
            <p>No details found for the provided Rack ID.</p>
        <?php endif; ?>

        <a href="scan_barcode.php" class="btn">Scan Another Barcode</a>
    </div>
</body>
</html>

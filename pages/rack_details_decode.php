<?php
session_start();
require '../includes/db_connect.php';

// Ensure the user is logged in and has the Worker role
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
    $stmt->bind_param("s", $rack_id);
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
    <title>Rack Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f7f9fc;
            min-height: 100vh;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 0 1rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #2C3E50;
            text-decoration: none;
            margin-bottom: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .card h2 {
            margin-bottom: 1rem;
            color: #2C3E50;
        }

        .card p {
            margin: 0.5rem 0;
            font-size: 1rem;
            color: #5A5A5A;
        }

        .btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 0.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="worker_dashboard.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
            Back to Dashboard
        </a>

        <h1 class="text-center mb-4">Rack Details</h1>
        
        <?php if ($rack): ?>
            <div class="card">
                <h2>Product Name: <?= htmlspecialchars($rack['product_name']) ?></h2>
                <p><strong>Rack ID:</strong> <?= htmlspecialchars($rack['rack_id']) ?></p>
                <p><strong>Rack Location:</strong> <?= htmlspecialchars($rack['rack_location']) ?></p>
                <p><strong>Manager Name:</strong> <?= htmlspecialchars($rack['manager_name']) ?></p>
                <p><strong>Created At:</strong> <?= htmlspecialchars($rack['created_at']) ?></p>
            </div>
        <?php else: ?>
            <p class="text-center text-danger">No details found for this Rack ID.</p>
        <?php endif; ?>

        <div class="text-center">
            <a href="scan_barcode.php" class="btn">Scan Another QR Code</a>
        </div>
    </div>
</body>
</html>

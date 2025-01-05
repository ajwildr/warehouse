<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    header("Location: error.php");
    exit;
}

$isAdmin = $_SESSION['role'] === 'Admin';
$rack_id = $_GET['rack_id'] ?? null;

if (!$rack_id) {
    die("Rack ID is required.");
}

$query = "
    SELECT 
        r.rack_id,
        r.rack_location,
        r.created_at as rack_created,
        p.product_id,
        p.name AS product_name,
        p.category,
        p.current as current_stock,
        p.min_limit,
        p.max_limit,
        p.created_at as product_created,
        u.username AS manager_name,
        s.name AS supplier_name,
        s.contact_info AS supplier_contact,
        s.email AS supplier_email
    FROM rack r
    JOIN products p ON r.product_id = p.product_id
    JOIN users u ON r.manager_id = u.user_id
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE r.rack_id = ?";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("s", $rack_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rackDetails = $result->fetch_assoc();
    $stmt->close();

    if ($isAdmin) {
        $stockQuery = "
            SELECT 
                stock_id,
                quantity,
                transaction_type,
                created_at
            FROM stock
            WHERE product_id = ?
            ORDER BY created_at DESC
            LIMIT 10";
        
        $stockStmt = $conn->prepare($stockQuery);
        $stockStmt->bind_param("i", $rackDetails['product_id']);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        $stockHistory = $stockResult->fetch_all(MYSQLI_ASSOC);
        $stockStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Rack Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .card {
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .section {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .stock-status {
            padding: 5px 10px;
            border-radius: 3px;
            color: white;
            display: inline-block;
        }
        .status-normal { background-color: #28a745; }
        .status-warning { background-color: #ffc107; color: black; }
        .status-critical { background-color: #dc3545; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background-color: white;
        }
        th, td {
            padding: 12px 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .admin-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detailed Rack Information</h1>
        
        <?php if ($rackDetails): ?>
            <div class="card">
                <div class="section">
                    <h2>Rack Information</h2>
                    <p><strong>Rack ID:</strong> <?= htmlspecialchars($rackDetails['rack_id']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($rackDetails['rack_location']) ?></p>
                    <p><strong>Created:</strong> <?= htmlspecialchars($rackDetails['rack_created']) ?></p>
                </div>

                <div class="section">
                    <h2>Product Information</h2>
                    <p><strong>Name:</strong> <?= htmlspecialchars($rackDetails['product_name']) ?></p>
                    <p><strong>Category:</strong> <?= htmlspecialchars($rackDetails['category']) ?></p>
                    <p><strong>Current Stock:</strong> 
                        <span class="stock-status <?php 
                            if ($rackDetails['current_stock'] <= $rackDetails['min_limit']) {
                                echo 'status-critical';
                            } elseif ($rackDetails['current_stock'] <= $rackDetails['min_limit'] * 1.5) {
                                echo 'status-warning';
                            } else {
                                echo 'status-normal';
                            }
                        ?>">
                            <?= htmlspecialchars($rackDetails['current_stock']) ?>
                        </span>
                    </p>
                    <p><strong>Stock Limits:</strong> Min: <?= htmlspecialchars($rackDetails['min_limit']) ?> | Max: <?= htmlspecialchars($rackDetails['max_limit']) ?></p>
                </div>

                <div class="section">
                    <h2>Management Information</h2>
                    <p><strong>Manager:</strong> <?= htmlspecialchars($rackDetails['manager_name']) ?></p>
                    <p><strong>Supplier:</strong> <?= htmlspecialchars($rackDetails['supplier_name']) ?></p>
                    <?php if ($isAdmin): ?>
                        <p><strong>Supplier Contact:</strong> <?= htmlspecialchars($rackDetails['supplier_contact']) ?></p>
                        <p><strong>Supplier Email:</strong> <?= htmlspecialchars($rackDetails['supplier_email']) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($isAdmin && isset($stockHistory)): ?>
                    <div class="section">
                        <h2>Stock History</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stockHistory as $record): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($record['created_at']) ?></td>
                                        <td><?= htmlspecialchars($record['transaction_type']) ?></td>
                                        <td><?= htmlspecialchars($record['quantity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

             
            </div>
        <?php else: ?>
            <p>No details found for this Rack ID.</p>
        <?php endif; ?>

        <a href="admin_manager_scan.php" class="btn">Scan Another QR Code</a>
    </div>
</body>
</html>

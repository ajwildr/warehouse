<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is logged in and has the Accounting role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Accounting') {
    header("Location: ../login.php");
    exit;
}

// Fetch stock transactions and product names
$sql = "
    SELECT 
        stock.stock_id,
        products.name AS product_name,
        stock.quantity,
        stock.transaction_type,
        stock.created_at
    FROM stock
    INNER JOIN products ON stock.product_id = products.product_id
    ORDER BY stock.created_at DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Logs - Warehouse Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="logs-container">
        <header>
            <h1>Stock Transactions</h1>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Transaction Type</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['stock_id']) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['quantity']) ?></td>
                                <td><?= htmlspecialchars($row['transaction_type']) ?></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No stock transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>

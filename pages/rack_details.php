<?php
session_start();
require '../includes/db_connect.php';

// Ensure the user is logged in and has the Worker role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Worker') {
    header("Location: error.php");
    exit;
}

// Get the product ID from the query string
$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    die("Product ID is required.");
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
        r.product_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $product_id);
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
    <title>Rack Details - CloudWare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #34495E;
            --accent-color: #3498DB;
            --success-color: #2ECC71;
            --warning-color: #F1C40F;
            --danger-color: #E74C3C;
            --light-bg: #ECF0F1;
            --dark-text: #2C3E50;
            --light-text: #ECF0F1;
        }

        body {
            background-color: var(--light-bg);
            min-height: 100vh;
            padding-bottom: 60px;
        }

        /* Back Button */
        .back-button {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .back-button:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateX(-5px);
        }

        /* Content Container */
        .content-container {
            padding: 2rem;
            padding-top: 4rem;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Rack Details Card */
        .rack-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .rack-card:hover {
            transform: translateY(-5px);
        }

        .rack-card h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 0.5rem;
        }

        .detail-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            font-size: 1.1rem;
            color: var(--dark-text);
            font-weight: 600;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            min-width: 160px;
            justify-content: center;
        }

        .btn-action.primary {
            background-color: var(--accent-color);
        }

        .btn-action.secondary {
            background-color: var(--secondary-color);
        }

        .btn-action:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Bottom Navigation (Mobile) */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 0.8rem;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-around;
            z-index: 1000;
        }

        .bottom-nav a {
            color: var(--dark-text);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.8rem;
        }

        .bottom-nav i {
            font-size: 1.5rem;
            margin-bottom: 0.2rem;
        }

        /* Responsive Adjustments */
        @media (min-width: 768px) {
            .bottom-nav {
                display: none;
            }

            body {
                padding-bottom: 2rem;
            }
        }

        @media (max-width: 767px) {
            .content-container {
                padding: 1rem;
                padding-top: 4rem;
            }

            .rack-card {
                padding: 1.5rem;
            }

            .detail-group {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }
        }

        /* Loading State */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="worker_list_product.php" class="back-button">
        <i class="bi bi-arrow-left"></i>
        <span>Back</span>
    </a>

    <div class="content-container">
        <?php if ($rack): ?>
            <div class="rack-card">
                <h2>
                    <i class="bi bi-box-seam me-2"></i>
                    <?= htmlspecialchars($rack['product_name']) ?>
                </h2>
                <div class="detail-group">
                    <div class="detail-item">
                        <span class="detail-label">Rack ID</span>
                        <span class="detail-value"><?= htmlspecialchars($rack['rack_id']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Location</span>
                        <span class="detail-value"><?= htmlspecialchars($rack['rack_location']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Manager</span>
                        <span class="detail-value"><?= htmlspecialchars($rack['manager_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Created At</span>
                        <span class="detail-value"><?= date('M d, Y H:i', strtotime($rack['created_at'])) ?></span>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="generate_barcode.php?rack_id=<?= urlencode($rack['rack_id']) ?>" class="btn-action primary">
                        <i class="bi bi-upc"></i>
                        Print Barcode
                    </a>
                    <a href="worker_list_product.php" class="btn-action secondary">
                        <i class="bi bi-list"></i>
                        Product List
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="rack-card">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    No rack details found for this product.
                </div>
                <div class="action-buttons">
                    <a href="worker_list_product.php" class="btn-action secondary">
                        <i class="bi bi-arrow-left"></i>
                        Back to Products
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="worker_dashboard.php">
            <i class="bi bi-house-door"></i>
            <span>Home</span>
        </a>
        <a href="scan_barcode.php">
            <i class="bi bi-upc-scan"></i>
            <span>Scan</span>
        </a>
        <a href="worker_list_product.php">
            <i class="bi bi-box-seam"></i>
            <span>Products</span>
        </a>
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require '../includes/db_connect.php';

// Ensure the user is an Accounting staff
if ($_SESSION['role'] != 'Accounting') {
    header("Location: error.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle outgoing stock submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Fetch product details
    $product_query = "SELECT name, current FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $new_quantity = $product['current'] - $quantity;

        // Check if there's enough stock
        if ($new_quantity < 0) {
            $error_message = "Insufficient stock available!";
        } else {
            // Insert into stock table
            $insert_stock_query = "INSERT INTO stock (product_id, quantity, transaction_type) VALUES (?, ?, 'Outgoing')";
            $stmt = $conn->prepare($insert_stock_query);
            $stmt->bind_param("ii", $product_id, $quantity);

            if ($stmt->execute()) {
                // Update the current quantity in the products table
                $update_product_query = "UPDATE products SET current = ? WHERE product_id = ?";
                $stmt = $conn->prepare($update_product_query);
                $stmt->bind_param("ii", $new_quantity, $product_id);
                if ($stmt->execute()) {
                    $success_message = "Stock updated successfully!";
                } else {
                    $error_message = "Failed to update product stock: " . $stmt->error;
                }
            } else {
                $error_message = "Failed to insert stock: " . $stmt->error;
            }
        }
    } else {
        $error_message = "Invalid product selected.";
    }
    $stmt->close();
}

// Fetch products for the dropdown
$products_query = "SELECT product_id, name, current FROM products";
$products_result = $conn->query($products_query);

// Fetch outgoing stock transactions
$outgoing_query = "
    SELECT 
        s.stock_id, 
        p.name AS product_name, 
        s.quantity, 
        s.created_at 
    FROM stock s
    JOIN products p ON s.product_id = p.product_id
    WHERE s.transaction_type = 'Outgoing'
    ORDER BY s.created_at DESC";
$outgoing_result = $conn->query($outgoing_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outgoing Stocks - CloudWare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .container {
            max-width: 1200px;
            padding: 2rem;
        }

        .card {
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 2rem;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }

        .back-button {
            text-decoration: none;
            color: var(--primary-color);
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .back-button:hover {
            color: var(--accent-color);
            transform: translateX(-5px);
        }

        .back-button i {
            margin-right: 0.5rem;
            font-size: 1.2em;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .table-responsive {
                border-radius: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="accounting_dashboard.php" class="back-button">
            <i class="bi bi-arrow-left-circle-fill"></i>
            Back to Dashboard
        </a>

        <div class="card">
            <div class="card-body">
                <h1 class="card-title mb-4">Outgoing Stocks</h1>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?= $success_message ?>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <!-- Outgoing Stock Form -->
                <form method="POST" class="mb-5">
                    <h4 class="mb-4">Record Outgoing Stock</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="product_id" class="form-label">Product</label>
                            <select class="form-select" id="product_id" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php while ($product = $products_result->fetch_assoc()): ?>
                                    <option value="<?= $product['product_id'] ?>" data-current="<?= $product['current'] ?>">
                                        <?= htmlspecialchars($product['name']) ?> 
                                        (Current: <?= htmlspecialchars($product['current']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-dash-circle me-2"></i>
                        Remove Stock
                    </button>
                </form>

                <!-- Outgoing Stock Table -->
                <h4 class="mb-4">Outgoing Stock Transactions</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($outgoing_result->num_rows > 0): ?>
                                <?php while ($row = $outgoing_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['stock_id']) ?></td>
                                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                                        <td>
                                            <span class="badge bg-danger">
                                                -<?= htmlspecialchars($row['quantity']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted d-block mb-2" style="font-size: 2rem;"></i>
                                        No outgoing stock transactions found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Client-side validation for quantity
    document.getElementById('quantity').addEventListener('input', function() {
        const productId = document.getElementById('product_id').value;
        if (!productId) return;
        
        const option = document.querySelector(`option[value="${productId}"]`);
        const currentStock = parseInt(option.dataset.current);
        const quantity = parseInt(this.value);
        
        if (quantity > currentStock) {
            this.setCustomValidity(`Quantity cannot exceed current stock (${currentStock})`);
        } else {
            this.setCustomValidity('');
        }
    });

    document.getElementById('product_id').addEventListener('change', function() {
        document.getElementById('quantity').dispatchEvent(new Event('input'));
    });
    </script>
</body>
</html>
<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is logged in and has the Accounting role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Accounting') {
    header("Location: ../login.php");
    exit;
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Fetch current stock with FOR UPDATE to lock the row
        $stmt = $conn->prepare("SELECT current FROM products WHERE product_id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $productData = $result->fetch_assoc();
        
        if (!$productData) {
            throw new Exception("Product not found");
        }
        
        $currentStock = $productData['current'];
        
        if ($quantity > $currentStock) {
            throw new Exception("Insufficient stock");
        }
        
        // Update stock table
        $insertStock = $conn->prepare("INSERT INTO stock (product_id, quantity, transaction_type) VALUES (?, ?, 'Outgoing')");
        $insertStock->bind_param("ii", $product_id, $quantity);
        $insertStock->execute();
        
        // Update products table
        $newStock = $currentStock - $quantity;
        $updateProduct = $conn->prepare("UPDATE products SET current = ? WHERE product_id = ?");
        $updateProduct->bind_param("ii", $newStock, $product_id);
        $updateProduct->execute();
        
        $conn->commit();
        
        // Fetch the new transaction for the response
        $stmt = $conn->prepare("
            SELECT s.stock_id, p.name AS product_name, s.quantity, s.created_at 
            FROM stock s
            JOIN products p ON s.product_id = p.product_id
            WHERE s.stock_id = LAST_INSERT_ID()
        ");
        $stmt->execute();
        $newTransaction = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Stock updated successfully',
            'transaction' => $newTransaction,
            'newStock' => $newStock
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Fetch products for the dropdown
$productQuery = "SELECT product_id, name, current FROM products ORDER BY name";
$productResult = $conn->query($productQuery);

// Fetch outgoing stock transactions
$outgoingQuery = "
    SELECT 
        s.stock_id, 
        p.name AS product_name, 
        s.quantity, 
        s.created_at 
    FROM stock s
    JOIN products p ON s.product_id = p.product_id
    WHERE s.transaction_type = 'Outgoing'
    ORDER BY s.created_at DESC";
$outgoingResult = $conn->query($outgoingQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outgoing Stock - Warehouse Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .notification {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            display: none;
        }
        .notification.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="outgoing-stock-container">
        <header>
            <h1>Manage Outgoing Stock</h1>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </header>
        <main>
            <div id="notification" class="notification"></div>
            
            <form id="outgoingStockForm">
                <div class="form-group">
                    <label for="product_id">Product</label>
                    <select name="product_id" id="product_id" required>
                        <option value="">Select a product</option>
                        <?php while ($product = $productResult->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($product['product_id']) ?>" 
                                    data-current="<?= htmlspecialchars($product['current']) ?>">
                                <?= htmlspecialchars($product['name']) ?> 
                                (Current: <?= htmlspecialchars($product['current']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="1" required>
                </div>
                <button type="submit" class="btn btn-success">Record Outgoing Stock</button>
            </form>

            <h2>Outgoing Stock Transactions</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="transactionsTable">
                    <?php if ($outgoingResult->num_rows > 0): ?>
                        <?php while ($row = $outgoingResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['stock_id']) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['quantity']) ?></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No outgoing stock transactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

    <script>
    document.getElementById('outgoingStockForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('ajax', true);
        
        const notification = document.getElementById('notification');
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            notification.className = `notification ${data.success ? 'success' : 'error'}`;
            notification.textContent = data.message;
            notification.style.display = 'block';
            
            if (data.success) {
                // Update the product dropdown
                const option = document.querySelector(`option[value="${formData.get('product_id')}"]`);
                option.dataset.current = data.newStock;
                option.textContent = `${option.textContent.split('(')[0]} (Current: ${data.newStock})`;
                
                // Add new transaction to table
                const tbody = document.getElementById('transactionsTable');
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>${data.transaction.stock_id}</td>
                    <td>${data.transaction.product_name}</td>
                    <td>${data.transaction.quantity}</td>
                    <td>${data.transaction.created_at}</td>
                `;
                
                // Insert at the top
                tbody.insertBefore(newRow, tbody.firstChild);
                
                // Reset form
                this.reset();
            }
            
            // Hide notification after 5 seconds
            setTimeout(() => {
                notification.style.display = 'none';
            }, 5000);
            
        } catch (error) {
            notification.className = 'notification error';
            notification.textContent = 'An error occurred. Please try again.';
            notification.style.display = 'block';
        } finally {
            submitButton.disabled = false;
        }
    });

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
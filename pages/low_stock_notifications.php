<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user details from session
$role = $_SESSION['role'];
$products = [];

// Modified query to include supplier information
if ($role === 'Admin') {
    $query = "SELECT p.name AS product_name, p.category, p.current, p.min_limit, 
              s.name AS supplier_name, s.email AS supplier_email, s.contact_info
              FROM products p
              LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
              WHERE p.current < p.min_limit";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} elseif ($role === 'Manager') {
    $assigned_category = $_SESSION['assigned_category'];
    $query = "SELECT p.name AS product_name, p.category, p.current, p.min_limit,
              s.name AS supplier_name, s.email AS supplier_email, s.contact_info
              FROM products p
              LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
              WHERE p.current < p.min_limit AND p.category = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $assigned_category);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle email opening
if (isset($_POST['send_email'])) {
    $to = $_POST['supplier_email'];
    $subject = str_replace(" ", "%20", "Low Stock Alert - " . $_POST['product_name']); // Proper space encoding for subject
    $message = str_replace("\n", "%0D%0A", $_POST['email_content']); // Proper line break encoding
    $message = str_replace(" ", "%20", $message); // Proper space encoding
    
    $mailtoLink = "mailto:" . $to . "?subject=" . $subject . "&body=" . $message;
    echo json_encode(['success' => true, 'mailtoLink' => $mailtoLink]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Notifications</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --danger-color: #dc2626;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--background-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-button {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #1d4ed8;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background: var(--card-background);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stock-indicator {
            display: flex;
            align-items: center;
            margin: 10px 0;
            gap: 10px;
        }

        .progress-bar {
            flex-grow: 1;
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--danger-color);
            transition: width 0.3s ease;
        }

        .email-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .email-button:hover {
            background-color: #1d4ed8;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .modal textarea {
            width: 100%;
            height: 200px;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 6px;
            background-color: #10b981;
            color: white;
            display: none;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .error-message {
            color: var(--danger-color);
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        

        <div class="cards-grid">
            <?php foreach ($products as $product): ?>
                <?php
                $stockPercentage = ($product['current'] / $product['min_limit']) * 100;
                $progressColor = $stockPercentage <= 50 ? '#dc2626' : '#f59e0b';
                ?>
                <div class="card">
                    <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                    <p>Category: <?= htmlspecialchars($product['category']) ?></p>
                    
                    <div class="stock-indicator">
                        <span>Stock:</span>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $stockPercentage ?>%; background-color: <?= $progressColor ?>"></div>
                        </div>
                        <span><?= $product['current'] ?>/<?= $product['min_limit'] ?></span>
                    </div>

                    <p>Supplier: <?= htmlspecialchars($product['supplier_name']) ?></p>
                    
                    <?php if ($product['supplier_email']): ?>
                        <button class="email-button" onclick="openEmailModal('<?= htmlspecialchars($product['product_name']) ?>', '<?= htmlspecialchars($product['supplier_email']) ?>' , <?= $product['current'] ?>, <?= $product['min_limit'] ?>)">
                            <i class="fas fa-envelope"></i> Contact Supplier
                        </button> 
                    <?php endif;  ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <h2>Send Email to Supplier</h2>
            <form id="emailForm">
                <input type="hidden" id="supplier_email" name="supplier_email">
                <input type="hidden" id="product_name" name="product_name">
                <textarea id="email_content" name="email_content"></textarea>

                <div class="error-message" id="emailError">
                    Failed to open mail client. Please try again.
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="email-button" style="background-color: #6b7280;" onclick="closeEmailModal()">Cancel</button>
                    <button type="submit" class="email-button">Send Email</button>
                </div>
            </form>
        </div>
    </div>

    <div id="notification" class="notification">Email client opened successfully!</div>

    <script>
        function openEmailModal(productName, supplierEmail, currentStock, minLimit) {
            const modal = document.getElementById('emailModal');
            const emailContent = document.getElementById('email_content');
            const supplierEmailInput = document.getElementById('supplier_email');
            const productNameInput = document.getElementById('product_name');
            document.getElementById('emailError').style.display = 'none';

            const template = `Dear Supplier,

We are writing to inform you about low stock levels for ${productName}.

Current Stock: ${currentStock}
Minimum Required: ${minLimit}

Please arrange for a new shipment at your earliest convenience.

Best regards,
Warehouse Management Team`;

            emailContent.value = template;
            supplierEmailInput.value = supplierEmail;
            productNameInput.value = productName;
            modal.style.display = 'flex';
        }

        function closeEmailModal() {
            document.getElementById('emailModal').style.display = 'none';
            document.getElementById('emailError').style.display = 'none';
        }

        function showNotification() {
            const notification = document.getElementById('notification');
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        document.getElementById('emailForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('send_email', true);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success && data.mailtoLink) {
                    window.location.href = data.mailtoLink;
                    closeEmailModal();
                    showNotification();
                } else {
                    document.getElementById('emailError').style.display = 'block';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('emailError').style.display = 'block';
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('emailModal');
            if (event.target === modal) {
                closeEmailModal();
            }
        }
    </script>
</body>
</html>
<?php 

session_start(); 
require __DIR__ . '/../vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Worker') {
    header("Location: login.php");
    exit;
}

$worker_id = $_SESSION['user_id'];

// Fetch the worker's assigned category from the users table
$query = "SELECT assigned_category FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$worker_category = $row['assigned_category'] ?? null;

if (!$worker_category) {
    die("Error: No category assigned to this worker.");
}

// Fetch products under the worker's assigned category
$product_query = "SELECT product_id, name FROM products WHERE category = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("s", $worker_category);
$stmt->execute();
$products_result = $stmt->get_result();
$products = []; // Initialize array to store products
while ($product = $products_result->fetch_assoc()) {
    $products[] = $product;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve product_id from form submission
    $product_id = $_POST['product_id'] ?? null;
    if (!$product_id) {
        die("Product selection is required.");
    }

    // Fetch the rack ID for the selected product
    $query = "SELECT rack_id FROM rack WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $rack_id = $row['rack_id'] ?? null;

    if (!$rack_id) {
        die("Rack not found for this product.");
    }

    // Retrieve the requested quantity from the form
    $quantity = $_POST['quantity'] ?? 0;
    
    // Validate the quantity
    $quantity = (string) $quantity;
    if (!ctype_digit($quantity) || (int)$quantity <= 0) {
        die("Invalid quantity. Please enter a positive integer.");
    }
    $quantity = (int)$quantity;
    
    // Create a PDF to include the QR codes
    $pdf = new FPDF();
    $barcodes_per_page = 20;
    $count = 0;
    
    for ($i = 0; $i < $quantity; $i++) {
        if ($count % $barcodes_per_page === 0) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "QR Codes for Rack ID: $rack_id", 0, 1, 'C');
        }
        
        $col = $count % 4;
        $row = floor(($count % $barcodes_per_page) / 4);
        
        $x = 10 + $col * 50;
        $y = 20 + $row * 40;
        
        // Create QR code
        $qrCode = new QrCode("Product ID: $product_id, Rack ID: $rack_id");
        $writer = new PngWriter();
        $qrCodeImageData = $writer->write($qrCode);
        
        $file_name = tempnam(sys_get_temp_dir(), 'qr_code_') . '.png';
        file_put_contents($file_name, $qrCodeImageData->getString());
        
        $pdf->Image($file_name, $x, $y, 30, 30);
        $pdf->SetXY($x, $y + 32);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(40, 10, "Rack ID: $rack_id", 0, 0, 'C');
        
        unlink($file_name);
        $count++;
    }
    
    $pdf->Output('I', "RackID_{$rack_id}_QRCodes.pdf");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Codes</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .btn-primary {
            padding: 0.5rem 2rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }
            
            .card {
                border-radius: 0;
                box-shadow: none;
            }
            
            .btn-back {
                position: relative;
                top: 0;
                left: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="btn btn-outline-secondary btn-back">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="card-title text-center mb-4">Generate QR Codes</h2>
                        <form method="POST" action="generate_barcode.php" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="product_id" class="form-label">Select Product:</label>
                                <select class="form-control" id="product_id" name="product_id" required>
                                    <option value="">-- Select a Product --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= $product['product_id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="quantity" class="form-label">Number of QR Codes needed:</label>
                                <input type="number" 
                                       class="form-control form-control-lg" 
                                       id="quantity" 
                                       name="quantity" 
                                       min="1" 
                                       required
                                       placeholder="Enter quantity">
                                <div class="invalid-feedback">
                                    Please enter a valid quantity (minimum 1).
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Generate QR Codes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form validation script -->
    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>


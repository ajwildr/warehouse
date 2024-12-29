<?php
session_start();
require __DIR__ . '/../vendor/autoload.php'; // Adjust the path if needed

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php'; // Include FPDF class

// Validate rack_id from the query string
$rack_id = $_GET['rack_id'] ?? null;

if (!$rack_id) {
    die("Rack ID is required.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve the requested quantity from the form
    $quantity = $_POST['quantity'] ?? 0;

    // Validate the quantity
    $quantity = (string) $quantity; // Cast to string for ctype_digit
    if (!ctype_digit($quantity) || (int)$quantity <= 0) {
        die("Invalid quantity. Please enter a positive integer.");
    }
    $quantity = (int)$quantity;

    // Create a PDF to include the QR codes
    $pdf = new FPDF();
    $barcodes_per_page = 20; // 4 rows * 5 columns = 20 QR codes per page
    $count = 0;

    for ($i = 0; $i < $quantity; $i++) {
        if ($count % $barcodes_per_page === 0) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "QR Codes for Rack ID: $rack_id", 0, 1, 'C');
        }

        // Calculate position for the current QR code
        $col = $count % 4; // 4 QR codes per row
        $row = floor(($count % $barcodes_per_page) / 4); // Row within the page

        $x = 10 + $col * 50; // Horizontal spacing
        $y = 20 + $row * 40; // Vertical spacing

        // Create a QR code instance
        $qrCode = new QrCode($rack_id);
        $writer = new PngWriter();

        // Generate the QR code image data
        $qrCodeImageData = $writer->write($qrCode);

        // Save the QR code image data to a temporary file
        $file_name = tempnam(sys_get_temp_dir(), 'qr_code_') . '.png';
        file_put_contents($file_name, $qrCodeImageData->getString());

        // Add the QR code image to the PDF
        $pdf->Image($file_name, $x, $y, 30, 30); // Adjust size: width 30, height 30
        $pdf->SetXY($x, $y + 32); // Adjust Rack ID text position below the QR code
        $pdf->SetFont('Arial', '', 8); // Smaller font for Rack ID
        $pdf->Cell(40, 10, "Rack ID: $rack_id", 0, 0, 'C');

        // Clean up the temporary QR code image file
        unlink($file_name);
        $count++;
    }

    // Output the PDF
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
    <style>
        .form-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            text-align: center;
        }
        .form-container input[type="number"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }
        .form-container button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Generate QR Codes for Rack ID: <?= htmlspecialchars($rack_id) ?></h1>
        <form method="POST" action="">
            <label for="quantity">Enter the number of QR Codes needed:</label>
            <input type="number" id="quantity" name="quantity" min="1" required>
            <button type="submit">Generate</button>
        </form>
    </div>
</body>
</html>

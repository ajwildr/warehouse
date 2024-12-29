<?php
require __DIR__ . '/vendor/autoload.php'; // Adjust the path if needed

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
require_once __DIR__ . '/vendor/setasign/fpdf/fpdf.php'; // Include FPDF class

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

    // Create QR Code instance
    $qrCode = new QrCode('6'); // The value you want to encode, e.g., "6"
    $writer = new PngWriter();

    // Generate the QR code image as a string
    $qrCodeImageData = $writer->write($qrCode);

    // Create a PDF to include the QR Code
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, "QR Code for Value: 6", 0, 1, 'C');

    // Save the QR code image data to a temporary file
    $file_name = tempnam(sys_get_temp_dir(), 'qr_code_') . '.png';
    file_put_contents($file_name, $qrCodeImageData->getString());

    // Add the QR code image to the PDF
    $pdf->Image($file_name, 50, 50, 100, 100); // Adjust size and position as needed

    // Clean up the temporary QR code image file
    unlink($file_name);

    // Output the PDF
    $pdf->Output('I', "QRCode_for_6.pdf");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Code</title>
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
        <h1>Generate QR Code for Value: 6</h1>
        <form method="POST" action="">
            <label for="quantity">Enter the number of QR Codes needed:</label>
            <input type="number" id="quantity" name="quantity" min="1" required>
            <button type="submit">Generate PDF</button>
        </form>
    </div>
</body>
</html>

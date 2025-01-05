<?php
session_start();
require __DIR__ . '/vendor/autoload.php'; // Adjust the path if needed

use Picqer\Barcode\BarcodeGeneratorPNG;
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

    // Generate barcodes and add them to a PDF
    $generator = new BarcodeGeneratorPNG();
    $pdf = new FPDF();
    $barcodes_per_page = 1; // 1 barcode per page
    $count = 0;

    for ($i = 0; $i < $quantity; $i++) {
        if ($count % $barcodes_per_page === 0) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "Barcode for Value: 6", 0, 1, 'C');
        }

        // Generate barcode image with a larger size
        $barcode_image = $generator->getBarcode('6', $generator::TYPE_CODE_128);

        // Save the barcode image to a temporary file
        $file_name = tempnam(sys_get_temp_dir(), 'barcode_') . '.png';
        file_put_contents($file_name, $barcode_image);

        // Add the barcode image to the PDF (larger size)
        $pdf->Image($file_name, 50, 50, 150, 60); // Adjust the width (150) and height (60)

        // Clean up temporary file
        unlink($file_name);
        $count++;
    }

    // Output the PDF
    $pdf->Output('I', "Barcode_for_6.pdf");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Barcode</title>
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
        <h1>Generate Barcode for Value: 6</h1>
        <form method="POST" action="">
            <label for="quantity">Enter the number of barcodes needed:</label>
            <input type="number" id="quantity" name="quantity" min="1" required>
            <button type="submit">Generate PDF</button>
        </form>
    </div>
</body>
</html>

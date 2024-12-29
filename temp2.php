<?php
session_start();
require __DIR__ . '/vendor/autoload.php'; // Adjust path if needed

use Zxing\QrReader;

$decoded_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['qr_image']['tmp_name'];
        
        try {
            // Attempt to decode the QR code
            $reader = new QrReader($file);
            $decoded_text = $reader->text();

            if (empty($decoded_text)) {
                $decoded_text = "No valid QR code found in the image.";
            }

        } catch (Exception $e) {
            $decoded_text = "Error scanning QR code: " . $e->getMessage();
            error_log("QR code scanning error: " . $e->getMessage());
        }
    } else {
        $upload_error = $_FILES['qr_image']['error'];
        $decoded_text = "Error uploading file (Code: $upload_error). Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Decode QR Code</title>
    <style>
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            text-align: center;
        }
        .form-container input[type="file"] {
            margin: 20px 0;
            padding: 10px;
            width: 100%;
            max-width: 300px;
        }
        .form-container button {
            padding: 12px 24px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Decode QR Code</h1>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="qr_image" accept="image/png, image/jpeg" required>
            <button type="submit">Decode QR Code</button>
        </form>

        <?php if (!empty($decoded_text)): ?>
            <div class="result">
                <h3>Decoded Result:</h3>
                <p><?= htmlspecialchars($decoded_text) ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

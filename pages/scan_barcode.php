<?php
session_start();
require '../includes/db_connect.php';

// Ensure the user is logged in and has the Worker role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Worker') {
    header("Location: error.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.4.0/html5-qrcode.min.js"></script>
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            text-align: center;
        }
        #qr-reader {
            width: 100%;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .upload-section {
            margin-top: 20px;
            text-align: center;
        }
        .upload-section input[type="file"] {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Scan or Upload QR Code</h1>
        <!-- QR code scanning section -->
        <div id="qr-reader"></div>
        <p id="qr-reader-results"></p>
        <form id="scan-form" method="GET" action="rack_details_decode.php" style="display: none;">
            <input type="hidden" id="rack_id" name="rack_id">
            <button type="submit" class="btn">View Rack Details</button>
        </form>

        <!-- QR code upload section -->
        <div class="upload-section">
            <h2>Upload QR Code</h2>
            <form id="upload-form" enctype="multipart/form-data" method="POST" action="decode_uploaded_qr.php">
                <input type="file" id="qr-file" name="qr-file" accept="image/*" required>
                <button type="submit" class="btn">Scan Uploaded QR</button>
            </form>
        </div>
    </div>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('qr-reader-results').textContent = `Scanned: ${decodedText}`;
            document.getElementById('rack_id').value = decodedText;
            document.getElementById('scan-form').style.display = 'block';
            qrReader.stop(); // Stop scanning after a successful scan
        }

        const qrReader = new Html5Qrcode("qr-reader");
        qrReader.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess
        );
    </script>
</body>
</html>

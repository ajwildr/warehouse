<?php
// admin_manager_scan.php
session_start();
require '../includes/db_connect.php';

// Check for admin or manager role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    header("Location: error.php");
    exit;
}

$isAdmin = $_SESSION['role'] === 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isAdmin ? 'Admin' : 'Manager' ?> - Scan QR Code</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.4.0/html5-qrcode.min.js"></script>
    <style>
        .container {
            max-width: 800px;
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
            margin: 5px;
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
        .role-indicator {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="role-indicator">
            <?= $isAdmin ? 'Admin Access' : 'Manager Access' ?>
        </div>
        <h1>Advanced QR Code Scanner</h1>

        <!-- QR code scanning section -->
        <div id="qr-reader"></div>
        <p id="qr-reader-results"></p>
        <form id="scan-form" method="GET" action="admin_manager_details.php" style="display: none;">
            <input type="hidden" id="rack_id" name="rack_id">
            <button type="submit" class="btn">View Detailed Information</button>
        </form>

        <!-- QR code upload section -->
        <div class="upload-section">
            <h2>Upload QR Code</h2>
            <form id="upload-form" enctype="multipart/form-data" method="POST" action="admin_manager_decode.php">
                <input type="file" id="qr-file" name="qr-file" accept="image/*" required>
                <button type="submit" class="btn">Process Uploaded QR</button>
            </form>
        </div>

        <?php if ($isAdmin): ?>
        <div class="admin-controls">
            <h2>Administrative Controls</h2>
            <a href="manage_racks.php" class="btn">Manage Racks</a>
            <a href="manage_users.php" class="btn">Manage Users</a>
            <a href="view_logs.php" class="btn">View Activity Logs</a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('qr-reader-results').textContent = `Scanned: ${decodedText}`;
            document.getElementById('rack_id').value = decodedText;
            document.getElementById('scan-form').style.display = 'block';
            qrReader.stop();
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
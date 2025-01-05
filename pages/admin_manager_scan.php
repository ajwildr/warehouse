<?php
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.4.0/html5-qrcode.min.js"></script>
    <style>
        body {
            background-color: #ECF0F1;
            min-height: 100vh;
        }

        .main-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .scan-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #2C3E50;
            text-decoration: none;
            margin-bottom: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }

        #qr-reader {
            width: 100%;
            max-width: 600px;
            margin: 0 auto 1.5rem;
        }

        .role-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #3498DB;
            color: white;
            border-radius: 2rem;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .upload-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        .file-input-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }

        .camera-container {
            width: 120px;
            height: 120px;
            border: 3px dashed #3498DB;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: all 0.3s ease;
            cursor: pointer;
            position: absolute;
            top: 0;
            left: 0;
        }

        .camera-container:hover {
            border-color: #2980B9;
            background-color: rgba(52, 152, 219, 0.1);
            transform: scale(1.05);
        }

        .camera-icon {
            font-size: 3.5rem;
            color: #3498DB;
            transition: transform 0.2s;
            pointer-events: none;
        }

        .camera-container:hover .camera-icon {
            color: #2980B9;
        }

        .file-input {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
        }

        .preview-container {
            margin-top: 2rem;
            display: none;
            text-align: center;
        }

        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 0.5rem;
            margin: 0 auto;
            display: block;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .file-info {
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            text-align: center;
            color: #2C3E50;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="<?= $isAdmin ? 'admin_dashboard.php' : 'manager_dashboard.php'; ?>" class="back-button">
            <i class="bi bi-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="scan-card">
            <div class="text-center">
                <span class="role-badge">
                    <?= $isAdmin ? 'Admin Access' : 'Manager Access' ?>
                </span>
                <h1 class="h3 mb-4">QR Code Scanner</h1>
            </div>

            <!-- QR Scanner Section -->
            <div id="qr-reader"></div>
            <p id="qr-reader-results" class="text-center text-muted"></p>
            <form id="scan-form" method="GET" action="admin_manager_details.php" style="display: none;" class="text-center">
                <input type="hidden" id="rack_id" name="rack_id">
                <button type="submit" class="btn btn-primary">View Details</button>
            </form>

            <!-- Upload Section -->
            <div class="upload-section">
                <h2 class="h4 text-center mb-4">Upload QR Code</h2>
                <form id="uploadForm" action="admin_manager_decode.php" method="POST" enctype="multipart/form-data">
                    <div class="text-center">
                        <div class="file-input-wrapper">
                            <input type="file" id="fileInput" name="qr-file" accept="image/*" class="file-input" required>
                            <div class="camera-container">
                                <i class="bi bi-camera camera-icon"></i>
                            </div>
                        </div>
                        <p class="mt-3 text-muted">Click the camera icon to upload a QR code image</p>
                    </div>

                    <!-- Image Preview Section -->
                    <div class="preview-container" id="previewContainer">
                        <img src="#" alt="Preview" class="preview-image" id="previewImage">
                        <div class="file-info" id="fileInfo"></div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">Process QR Code</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // QR Scanner initialization
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

        // File Upload Preview
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const fileInfo = document.getElementById('fileInfo');

        // Handle file selection and preview display
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();

                // Render image preview
                reader.onload = (event) => {
                    previewImage.src = event.target.result;
                    previewContainer.style.display = 'block';
                    fileInfo.innerHTML = `<i class="bi bi-file-image me-2"></i>${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                };

                // Handle errors
                reader.onerror = () => {
                    alert('Error loading the file. Please try again.');
                };

                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
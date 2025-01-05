<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is Worker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Worker') {
    header("Location: login.php");
    exit;
}

$assigned_category = $_SESSION['assigned_category'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudWare - Upload QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #ECF0F1;
            min-height: 100vh;
        }

        .main-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .upload-card {
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
        }

        .camera-container:hover .camera-icon {
            color: #2980B9;
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

        .submit-button {
            margin-top: 1.5rem;
            padding: 0.75rem 2.5rem;
            font-size: 1.1rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .preview-container.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="worker_dashboard.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="upload-card">
            <h2 class="text-center mb-4">Upload QR Code</h2>

            <form id="uploadForm" action="decode_uploaded_qr.php" method="POST" enctype="multipart/form-data">
                <div class="text-center">
                    <div class="camera-container" id="cameraIcon">
                        <i class="bi bi-camera camera-icon"></i>
                    </div>
                    <input type="file" id="fileInput" name="qr-file" accept="image/*" class="d-none" required>
                    <p class="mt-3 text-muted">Click the camera icon to upload QR code image</p>
                </div>

                <div class="preview-container" id="previewContainer">
                    <img src="#" alt="Preview" class="preview-image" id="previewImage">
                    <div class="file-info" id="fileInfo"></div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary submit-button">
                        Scan Uploaded QR
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const cameraIcon = document.getElementById('cameraIcon');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const fileInfo = document.getElementById('fileInfo');

        // Trigger file input on camera icon click
        cameraIcon.addEventListener('click', () => {
            fileInput.click();
        });

        // Display preview on file selection
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();

                reader.onload = (event) => {
                    previewImage.src = event.target.result;
                    previewContainer.classList.add('show');
                    fileInfo.innerHTML = `<i class="bi bi-file-image me-2"></i>${file.name} (${(file.size / 1024).toFixed(2)} KB)`;
                };

                reader.onerror = () => {
                    alert('Error loading file. Please try again.');
                };

                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

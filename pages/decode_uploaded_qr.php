<?php
session_start();
require '../includes/db_connect.php';
require '../vendor/autoload.php'; // Ensure you have a library like Endroid\QrCode for decoding

use Zxing\QrReader;

// Ensure the user is logged in and has the Worker role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Worker') {
    header("Location: error.php");
    exit;
}

// Handle uploaded file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['qr-file'])) {
    $file = $_FILES['qr-file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filePath = $file['tmp_name'];

        // Decode the QR code using Zxing or a similar library
        $qrReader = new QrReader($filePath); // Example using Zxing PHP wrapper
        $rackId = $qrReader->text();

        if ($rackId) {
            // Redirect to rack details with the decoded rack ID
            header("Location: rack_details_decode.php?rack_id=" . urlencode($rackId));
            exit;
        } else {
            die("Failed to decode the QR code. Please try again.");
        }
    } else {
        die("Error uploading file. Please try again.");
    }
} else {
    die("Invalid request.");
}
?>

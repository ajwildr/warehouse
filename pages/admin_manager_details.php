<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session with error checking
if (!session_start()) {
    error_log("Failed to start session");
    die("Session start failed");
}

// Log important variables
error_log("Session variables: " . print_r($_SESSION, true));
error_log("GET variables: " . print_r($_GET, true));

require '../includes/db_connect.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    error_log("Authentication failed - User ID: " . ($_SESSION['user_id'] ?? 'not set') . 
              ", Role: " . ($_SESSION['role'] ?? 'not set'));
    header("Location: error.php");
    exit;
}

$isAdmin = $_SESSION['role'] === 'Admin';
$rack_id = $_GET['rack_id'] ?? null;

if (!$rack_id) {
    error_log("No rack_id provided");
    die("Rack ID is required.");
}

// Test database connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// Log the query for debugging
$query = "
    SELECT 
        r.rack_id,
        r.rack_location,
        r.created_at as rack_created,
        p.product_id,
        p.name AS product_name,
        p.category,
        p.current as current_stock,
        p.min_limit,
        p.max_limit,
        p.created_at as product_created,
        u.username AS manager_name,
        s.name AS supplier_name,
        s.contact_info AS supplier_contact,
        s.email AS supplier_email
    FROM rack r
    JOIN products p ON r.product_id = p.product_id
    JOIN users u ON r.manager_id = u.user_id
    JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE r.rack_id = ?";

error_log("Executing query with rack_id: " . $rack_id);

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $rack_id);
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
$rackDetails = $result->fetch_assoc();
error_log("Query result: " . print_r($rackDetails, true));
$stmt->close();

// Rest of your HTML code remains the same
?>

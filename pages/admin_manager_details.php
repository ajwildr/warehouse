<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create a debug output array
$debug_output = [];

// Function to add debug message
function addDebug($message) {
    global $debug_output;
    $debug_output[] = $message;
    error_log($message);
}

// Start session with error checking
if (!session_start()) {
    addDebug("Failed to start session");
    die("Session start failed");
}

// Log important variables
addDebug("Session variables: " . print_r($_SESSION, true));
addDebug("GET variables: " . print_r($_GET, true));

// Test if includes directory exists
if (!file_exists('../includes/db_connect.php')) {
    addDebug("db_connect.php not found in expected location");
} else {
    addDebug("db_connect.php found");
}

require '../includes/db_connect.php';

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    addDebug("Authentication failed - User ID: " . ($_SESSION['user_id'] ?? 'not set') . 
            ", Role: " . ($_SESSION['role'] ?? 'not set'));
}

$isAdmin = $_SESSION['role'] === 'Admin';
$rack_id = $_GET['rack_id'] ?? null;

if (!$rack_id) {
    addDebug("No rack_id provided");
}

// Test database connection
if ($conn->connect_error) {
    addDebug("Database connection failed: " . $conn->connect_error);
} else {
    addDebug("Database connection successful");
}

// Log the query execution
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

addDebug("Executing query with rack_id: " . $rack_id);

$stmt = $conn->prepare($query);
if (!$stmt) {
    addDebug("Prepare failed: " . $conn->error);
} else {
    addDebug("Query prepared successfully");
    
    $stmt->bind_param("s", $rack_id);
    if (!$stmt->execute()) {
        addDebug("Execute failed: " . $stmt->error);
    } else {
        addDebug("Query executed successfully");
        
        $result = $stmt->get_result();
        $rackDetails = $result->fetch_assoc();
        addDebug("Query result: " . print_r($rackDetails, true));
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Advanced Rack Details</title>
    <style>
        .debug-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 20px;
            margin: 20px;
            border-radius: 5px;
        }
        .debug-message {
            font-family: monospace;
            margin: 5px 0;
            padding: 5px;
            background-color: #e9ecef;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .card {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="debug-section">
            <h2>Debug Information</h2>
            <?php foreach ($debug_output as $message): ?>
                <div class="debug-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($rackDetails)): ?>
            <div class="card">
                <h2>Rack Details Found</h2>
                <pre><?php print_r($rackDetails); ?></pre>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>No Rack Details Found</h2>
                <p>No data was retrieved for the given rack ID.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

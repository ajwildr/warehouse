<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any early errors
ob_start();

// Start session and log session status
session_start();
$session_status = session_status();
$session_debug = [
    'session_id' => session_id(),
    'session_status' => $session_status,
    'user_id_set' => isset($_SESSION['user_id']),
    'role_set' => isset($_SESSION['role']),
    'current_role' => $_SESSION['role'] ?? 'not set'
];

// Initialize error log array
$debug_log = [];
$debug_log['session'] = $session_debug;

try {
    require '../includes/db_connect.php';
    $debug_log['database'] = "Database connection successful";
} catch (Exception $e) {
    $debug_log['database_error'] = $e->getMessage();
    die("Database connection failed: " . $e->getMessage());
}

// Authentication check with logging
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    $debug_log['auth_error'] = [
        'message' => 'Authentication failed',
        'user_id' => $_SESSION['user_id'] ?? 'not set',
        'role' => $_SESSION['role'] ?? 'not set'
    ];
    header("Location: error.php");
    exit;
}

$isAdmin = $_SESSION['role'] === 'Admin';
$rack_id = $_GET['rack_id'] ?? null;

// Log rack_id status
$debug_log['rack_id'] = [
    'received' => $rack_id,
    'get_params' => $_GET
];

if (!$rack_id) {
    $debug_log['error'] = 'Rack ID is missing';
    die("Rack ID is required. Debug info: " . json_encode($debug_log));
}

// Comprehensive query based on role
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

$debug_log['query'] = $query;

try {
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $bind_result = $stmt->bind_param("s", $rack_id);
    if (!$bind_result) {
        throw new Exception("Binding parameters failed: " . $stmt->error);
    }

    $execute_result = $stmt->execute();
    if (!$execute_result) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Getting result failed: " . $stmt->error);
    }

    $rackDetails = $result->fetch_assoc();
    $debug_log['rack_details'] = $rackDetails ? 'Data found' : 'No data found';
    
    $stmt->close();

    // Get stock history if admin
    if ($isAdmin && $rackDetails) {
        try {
            $stockQuery = "
                SELECT 
                    stock_id,
                    quantity,
                    transaction_type,
                    created_at
                FROM stock
                WHERE product_id = ?
                ORDER BY created_at DESC
                LIMIT 10";
            
            $stockStmt = $conn->prepare($stockQuery);
            if (!$stockStmt) {
                throw new Exception("Stock history prepare failed: " . $conn->error);
            }

            $stockStmt->bind_param("i", $rackDetails['product_id']);
            $stockStmt->execute();
            $stockResult = $stockStmt->get_result();
            $stockHistory = $stockResult->fetch_all(MYSQLI_ASSOC);
            $debug_log['stock_history'] = count($stockHistory) . ' records found';
            $stockStmt->close();
        } catch (Exception $e) {
            $debug_log['stock_history_error'] = $e->getMessage();
        }
    }
} catch (Exception $e) {
    $debug_log['query_error'] = $e->getMessage();
    die("Error executing query: " . $e->getMessage() . "\nDebug info: " . json_encode($debug_log));
}

// Function to check if all required fields are present
function validateRackDetails($details) {
    $required_fields = ['rack_id', 'rack_location', 'product_name', 'category', 'current_stock'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($details[$field]) || $details[$field] === '') {
            $missing_fields[] = $field;
        }
    }
    
    return $missing_fields;
}

// Validate rack details
if ($rackDetails) {
    $missing_fields = validateRackDetails($rackDetails);
    if (!empty($missing_fields)) {
        $debug_log['validation_error'] = [
            'message' => 'Missing required fields',
            'fields' => $missing_fields
        ];
    }
}

// Display debug information for admins if there's an issue
$show_debug = isset($_GET['debug']) && $isAdmin;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Rack Details</title>
    <!-- Previous CSS styles remain the same -->
    <style>
        /* Previous styles remain the same */
        .debug-info {
            background-color: #f8f9fa;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .debug-info pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detailed Rack Information</h1>
        
        <?php if ($rackDetails): ?>
            <!-- Previous HTML content remains the same -->
        <?php else: ?>
            <div class="card">
                <p>No details found for Rack ID: <?= htmlspecialchars($rack_id) ?></p>
                <p>Please verify the Rack ID and try again.</p>
            </div>
        <?php endif; ?>

        <?php if ($show_debug): ?>
            <div class="debug-info">
                <h3>Debug Information</h3>
                <pre><?= htmlspecialchars(json_encode($debug_log, JSON_PRETTY_PRINT)) ?></pre>
            </div>
        <?php endif; ?>

        <a href="admin_manager_scan.php" class="btn">Scan Another QR Code</a>
        <?php if ($isAdmin): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['debug' => '1'])) ?>" class="btn">Show Debug Info</a>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// Capture any output buffering and add to debug log
$debug_log['output_buffer'] = ob_get_contents();
ob_end_flush();
?>

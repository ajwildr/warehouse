<?php
require '../includes/db_connect.php';

function displayTable($conn, $tableName) {
    echo "<h2>$tableName Table</h2>";
    
    // Get table structure
    $result = $conn->query("DESCRIBE $tableName");
    if ($result === FALSE) {
        echo "Error getting table structure: " . $conn->error;
        return;
    }
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get table data
    $result = $conn->query("SELECT * FROM $tableName");
    if ($result === FALSE) {
        echo "Error getting table data: " . $conn->error;
        return;
    }
    
    if ($result->num_rows > 0) {
        echo "<h3>Table Data:</h3>";
        echo "<table border='1'>";
        
        // Header row
        $firstRow = $result->fetch_assoc();
        echo "<tr>";
        foreach ($firstRow as $key => $value) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        // First print the first row we used for headers
        echo "<tr>";
        foreach ($firstRow as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
        
        // Then print the rest of the rows
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in table</p>";
    }
    echo "<hr>";
}

// Add some basic CSS for better presentation
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; margin-bottom: 20px; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    h2 { color: #333; margin-top: 30px; }
    hr { margin: 30px 0; border: none; border-top: 1px solid #ddd; }
</style>";

// Get all tables in the database
$result = $conn->query("SHOW TABLES");
if ($result === FALSE) {
    echo "Error getting tables: " . $conn->error;
    exit;
}

while ($row = $result->fetch_row()) {
    displayTable($conn, $row[0]);
}

$conn->close();
?>
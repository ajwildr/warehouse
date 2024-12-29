<?php
// db_connect.php
function getDBConnection() {
    $host = "maceproject.mysql.database.azure.com";
    $username = "ajai";
    $password = "Tatasky@123";
    $database = "warehouse";
    
    $conn = mysqli_connect($host, $username, $password, $database);
    
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    return $conn;
}

// Create a global connection object
$conn = getDBConnection();
?>

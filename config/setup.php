<?php
// Database credentials
$host = "localhost";
$username = "root";
$password = "";

try {
    // Create connection
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/setup.sql');
    $conn->exec($sql);
    
    echo "Database setup completed successfully!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 
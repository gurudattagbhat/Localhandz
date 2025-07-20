<?php
// Database connection configuration - EXAMPLE FILE
// Copy this file to db_connect.php and update with your actual database credentials

$host = "localhost"; // Change to your database host
$user = "your_database_username"; // Change to your MySQL username
$password = "your_database_password"; // Change to your MySQL password
$dbname = "local_handz"; // Change to your database name

$conn = new mysqli($host, $user, $password, $dbname);

// Check for errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

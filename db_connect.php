<?php
$host = "localhost"; // Change to your database host
$user = "root"; // Change to your MySQL username
$password = ""; // Change to your MySQL password
$dbname = "local_handz"; // Change to your database name

$conn = new mysqli($host, $user, $password, $dbname);

// Check for errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

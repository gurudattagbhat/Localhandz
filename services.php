<?php
session_start();
$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add') {
        // Simulate adding a service to the database
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = $_POST['price'];

        // Add to the database (you would normally insert into the database here)
        // For example: insert into services table

        // Respond success
        $response['status'] = 'success';
        echo json_encode($response);
    }
}
?>

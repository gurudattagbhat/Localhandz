<?php
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname' => 'local_handz',
    'charset' => 'utf8mb4'
];

$conn = new mysqli(
    $config['host'],
    $config['username'],
    $config['password'],
    $config['dbname']
);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset($config['charset']);
?>
<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $loggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    
    echo json_encode([
        'success' => true,
        'loggedIn' => $loggedIn,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 
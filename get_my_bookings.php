<?php
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'local_handz';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    // Get user info
    $stmt = $pdo->prepare('SELECT name, phone FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    // Fetch bookings for this user
    $stmt = $pdo->prepare('SELECT sr.*, s.name AS service_name, sp.name AS provider_name FROM service_requests sr LEFT JOIN services s ON sr.service_id = s.id LEFT JOIN service_providers sp ON sr.provider_id = sp.id WHERE sr.customer_name = ? AND sr.customer_phone = ? ORDER BY sr.request_date DESC');
    $stmt->execute([$user['name'], $user['phone']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'bookings' => $bookings]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 
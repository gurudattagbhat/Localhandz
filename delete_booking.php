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
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'Missing booking id']);
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
    // Only allow delete if booking belongs to user and is pending
    $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE id = ? AND customer_name = ? AND customer_phone = ? AND status = "pending"');
    $stmt->execute([$id, $user['name'], $user['phone']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) {
        echo json_encode(['success' => false, 'error' => 'Booking not found or cannot be deleted']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM service_requests WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 
<?php
session_start();
header('Content-Type: application/json');
$host = 'localhost';
$db   = 'local_handz';
$user = 'root';
$pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success'=>false,'error'=>'Please log in to use the cart.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($action === 'fetch') {
    $stmt = $pdo->prepare('SELECT c.id, c.service_id, c.provider_id, c.quantity, s.name AS service_name, s.price, s.photo, sp.name AS provider_name FROM cart c LEFT JOIN services s ON c.service_id=s.id LEFT JOIN service_providers sp ON c.provider_id=sp.id WHERE c.user_id=?');
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true, 'cart'=>$cart]);
    exit;
}

if ($action === 'remove') {
    $id = $data['id'] ?? null;
    if (!$id) { echo json_encode(['success'=>false,'error'=>'Missing cart id']); exit; }
    $stmt = $pdo->prepare('DELETE FROM cart WHERE id=? AND user_id=?');
    $stmt->execute([$id, $user_id]);
    echo json_encode(['success'=>true]);
    exit;
}

if ($action === 'add') {
    // Validate input
    $service_id = $data['service_id'] ?? null;
    $provider_id = $data['provider_id'] ?? null;
    $quantity = $data['quantity'] ?? 1;
    if (!$service_id || !$provider_id) {
        echo json_encode(['success'=>false,'error'=>'Missing service or provider']);
        exit;
    }
    // Prevent duplicate cart items for the same service/provider
    $stmt = $pdo->prepare('SELECT id FROM cart WHERE user_id=? AND service_id=? AND provider_id=?');
    $stmt->execute([$user_id, $service_id, $provider_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success'=>false,'error'=>'Item already in cart']);
        exit;
    }
    // Insert into cart
    $stmt = $pdo->prepare('INSERT INTO cart (user_id, service_id, provider_id, quantity) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $service_id, $provider_id, $quantity]);
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown action']);
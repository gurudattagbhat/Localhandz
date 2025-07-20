<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Prevent accidental output before headers
ob_start();

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

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    if (!$action) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if ($action === 'fetch') {
        $stmt = $pdo->prepare('SELECT sr.*, s.name AS service_name, s.photo AS service_photo, sp.name AS provider_name FROM service_requests sr LEFT JOIN services s ON sr.service_id = s.id LEFT JOIN service_providers sp ON sr.provider_id = sp.id WHERE sr.customer_name = ? AND sr.customer_phone = ? ORDER BY sr.request_date DESC');
        $stmt->execute([$user['name'], $user['phone']]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'bookings' => $bookings]);
        exit;
    }

    if ($action === 'update') {
        $id = $data['id'] ?? null;
        $address = array_key_exists('customer_address', $data) ? $data['customer_address'] : null;
        $request_date = array_key_exists('request_date', $data) ? $data['request_date'] : null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Missing booking id']);
            exit;
        }
        $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE id = ? AND customer_name = ? AND customer_phone = ? AND status = "pending"');
        $stmt->execute([$id, $user['name'], $user['phone']]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            echo json_encode(['success' => false, 'error' => 'Booking not found or not editable']);
            exit;
        }
        $fields = [];
        $params = [];
        if ($address !== null) {
            $fields[] = 'customer_address = ?';
            $params[] = $address;
        }
        if ($request_date !== null) {
            $fields[] = 'request_date = ?';
            $params[] = $request_date;
        }
        if (empty($fields)) {
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            exit;
        }
        $params[] = $id;
        $sql = 'UPDATE service_requests SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Missing booking id']);
            exit;
        }
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
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

ob_end_clean();
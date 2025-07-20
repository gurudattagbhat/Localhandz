<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit;
}
$stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
if ($service) {
    echo json_encode(['success' => true, 'service' => $service]);
} else {
    echo json_encode(['success' => false, 'message' => 'Service not found']);
}

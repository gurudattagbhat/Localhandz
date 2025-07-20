<?php
require 'db_connect.php';
$data = json_decode(file_get_contents('php://input'), true);

$service_id = intval($data['service_id'] ?? 0);
$provider_id = intval($data['provider_id'] ?? 0);
$rating = intval($data['rating'] ?? 0);
$comment = $data['comment'] ?? '';
$reviewer_name = $data['reviewer_name'] ?? null;

if ($service_id && $provider_id && $rating >= 1 && $rating <= 5 && $reviewer_name) {
    $stmt = $conn->prepare("INSERT INTO reviews (service_id, provider_id, reviewer_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisis", $service_id, $provider_id, $reviewer_name, $rating, $comment);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to submit review']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
?>
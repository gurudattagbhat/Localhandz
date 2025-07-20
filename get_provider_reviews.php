<?php
require 'db_connect.php';
$provider_id = intval($_GET['provider_id'] ?? 0);
$service_id = intval($_GET['service_id'] ?? 0);

if ($provider_id && $service_id) {
    $stmt = $conn->prepare("SELECT reviewer_name, rating, comment, created_at FROM reviews WHERE provider_id = ? AND service_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("ii", $provider_id, $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    echo json_encode(['success' => true, 'reviews' => $reviews]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid provider ID or service ID']);
}
?>
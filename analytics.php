<?php
ini_set('session.cookie_path', '/');
session_start();
error_log("ANALYTICS SESSION: " . print_r($_SESSION, true));
header('Content-Type: application/json');
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$provider_id = $_SESSION['user_id'];

// Total Revenue: sum of service prices for completed service_requests for this provider
$totalRevenue = 0;
$sql = "SELECT COALESCE(SUM(s.price), 0) FROM service_requests sr JOIN services s ON sr.service_id = s.id WHERE sr.provider_id = ? AND sr.status = 'completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$stmt->bind_result($totalRevenue);
$stmt->fetch();
$stmt->close();

// Total Orders
$totalOrders = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM service_requests WHERE provider_id = ?");
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$stmt->bind_result($totalOrders);
$stmt->fetch();
$stmt->close();
if ($totalOrders === null) $totalOrders = 0;

// Average Rating
$averageRating = 0;
$stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE provider_id = ?");
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$stmt->bind_result($averageRating);
$stmt->fetch();
$stmt->close();
$averageRating = round($averageRating, 1);

// Satisfaction Rate
$satisfactionRate = 0;
$totalReviews = 0;
$positiveReviews = 0;
$stmt = $conn->prepare("SELECT COUNT(*), SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) FROM reviews WHERE provider_id = ?");
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$stmt->bind_result($totalReviews, $positiveReviews);
$stmt->fetch();
$stmt->close();
if ($totalReviews > 0 && $positiveReviews !== null) {
    $satisfactionRate = round(($positiveReviews / $totalReviews) * 100);
}

// Output JSON
$response = [
    'success' => true,
    'summary' => [
        'totalRevenue' => $totalRevenue,
        'totalOrders' => $totalOrders,
        'averageRating' => $averageRating,
        'satisfactionRate' => $satisfactionRate
    ]
];
echo json_encode($response);
exit; 
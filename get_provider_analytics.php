<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 0;
if ($provider_id <= 0) {
    echo json_encode(['error' => 'Invalid provider_id']);
    exit;
}

// Total Revenue: sum of price for completed service_requests for this provider
$sqlRevenue = "SELECT SUM(s.price) AS total_revenue
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    WHERE sr.provider_id = ? AND sr.status = 'completed'";
$stmt = $conn->prepare($sqlRevenue);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total_revenue = $row['total_revenue'] ? floatval($row['total_revenue']) : 0;
$stmt->close();

// Total Orders: count of all service_requests for this provider
$sqlOrders = "SELECT COUNT(*) AS total_orders FROM service_requests WHERE provider_id = ?";
$stmt = $conn->prepare($sqlOrders);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total_orders = $row['total_orders'] ? intval($row['total_orders']) : 0;
$stmt->close();

// Average Rating: average of all ratings in reviews for this provider
$sqlRating = "SELECT AVG(rating) AS avg_rating FROM reviews WHERE provider_id = ?";
$stmt = $conn->prepare($sqlRating);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$avg_rating = $row['avg_rating'] !== null ? round(floatval($row['avg_rating']), 2) : null;
$stmt->close();

// Most Popular Service: service with most requests for this provider
$sqlPopular = "SELECT s.name, COUNT(*) AS cnt
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    WHERE sr.provider_id = ?
    GROUP BY sr.service_id
    ORDER BY cnt DESC
    LIMIT 1";
$stmt = $conn->prepare($sqlPopular);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$popular_service = $row ? $row['name'] : null;
$stmt->close();

// Revenue trend for last 6 months
$revenue_trend = [];
$sqlTrend = "SELECT DATE_FORMAT(sr.request_date, '%Y-%m') as month, SUM(s.price) as revenue
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    WHERE sr.provider_id = ? AND sr.status = 'completed'
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6";
$stmt = $conn->prepare($sqlTrend);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $revenue_trend[] = [
        'month' => $row['month'],
        'revenue' => floatval($row['revenue'])
    ];
}
$stmt->close();
$revenue_trend = array_reverse($revenue_trend);

// Order status distribution
$order_status = [];
$sqlStatus = "SELECT status, COUNT(*) as count FROM service_requests WHERE provider_id = ? GROUP BY status";
$stmt = $conn->prepare($sqlStatus);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $order_status[$row['status']] = intval($row['count']);
}
$stmt->close();

// Service popularity (top 5 services by order count)
$service_popularity = [];
$sqlServicePop = "SELECT s.name, COUNT(*) as cnt
    FROM service_requests sr
    JOIN services s ON sr.service_id = s.id
    WHERE sr.provider_id = ?
    GROUP BY sr.service_id
    ORDER BY cnt DESC
    LIMIT 5";
$stmt = $conn->prepare($sqlServicePop);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $service_popularity[] = [
        'service' => $row['name'],
        'count' => intval($row['cnt'])
    ];
}
$stmt->close();

// Output JSON
echo json_encode([
    'total_revenue' => $total_revenue,
    'total_orders' => $total_orders,
    'avg_rating' => $avg_rating,
    'popular_service' => $popular_service,
    'revenue_trend' => $revenue_trend,
    'order_status' => $order_status,
    'service_popularity' => $service_popularity
]);

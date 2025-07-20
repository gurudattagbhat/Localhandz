<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

function error_response($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 0;
if (!$provider_id) error_response('Missing or invalid provider_id');

$response = [
    'success' => true,
    'summary' => [
        'totalRevenue' => 0,
        'totalOrders' => 0,
        'averageRating' => 0,
        'satisfactionRate' => 0,
    ],
    'charts' => [
        'revenueTrend' => [ 'labels' => [], 'values' => [] ],
        'ordersStatus' => [ 'labels' => [], 'values' => [] ],
        'servicePopularity' => [ 'labels' => [], 'values' => [] ],
        'ratings' => [0,0,0,0,0],
    ],
    'recentBookings' => []
];

// Total Revenue (completed only)
$sql = "SELECT SUM(s.price) as total FROM service_requests r JOIN services s ON r.service_id = s.id WHERE r.provider_id = ? AND r.status = 'completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$stmt->bind_result($totalRevenue);
$stmt->fetch();
$response['summary']['totalRevenue'] = $totalRevenue ? floatval($totalRevenue) : 0;
$stmt->close();

// Total Orders (completed only)
$sql = "SELECT COUNT(*) as total FROM service_requests WHERE provider_id = ? AND status = 'completed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$stmt->bind_result($totalOrders);
$stmt->fetch();
$response['summary']['totalOrders'] = $totalOrders ? intval($totalOrders) : 0;
$stmt->close();

// Orders by Status
$sql = "SELECT status, COUNT(*) as count FROM service_requests WHERE provider_id = ? GROUP BY status";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$statusLabels = [];
$statusValues = [];
while ($row = $result->fetch_assoc()) {
    $statusLabels[] = ucfirst($row['status']);
    $statusValues[] = intval($row['count']);
}
$response['charts']['ordersStatus']['labels'] = $statusLabels;
$response['charts']['ordersStatus']['values'] = $statusValues;
$stmt->close();

// Revenue Trend (last 6 months)
$sql = "SELECT DATE_FORMAT(r.request_date, '%b %Y') as month, SUM(s.price) as total
        FROM service_requests r
        JOIN services s ON r.service_id = s.id
        WHERE r.provider_id = ? AND r.status = 'completed'
        GROUP BY YEAR(r.request_date), MONTH(r.request_date)
        ORDER BY r.request_date DESC
        LIMIT 6";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$months = [];
$revenues = [];
while ($row = $result->fetch_assoc()) {
    array_unshift($months, $row['month']);
    array_unshift($revenues, floatval($row['total']));
}
$response['charts']['revenueTrend']['labels'] = $months;
$response['charts']['revenueTrend']['values'] = $revenues;
$stmt->close();

// Service Popularity (completed only)
$sql = "SELECT s.name, COUNT(r.id) as count
        FROM services s
        LEFT JOIN service_requests r ON s.id = r.service_id AND r.status = 'completed'
        WHERE s.provider_id = ?
        GROUP BY s.id, s.name";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$serviceLabels = [];
$serviceCounts = [];
while ($row = $result->fetch_assoc()) {
    $serviceLabels[] = $row['name'];
    $serviceCounts[] = intval($row['count']);
}
$response['charts']['servicePopularity']['labels'] = $serviceLabels;
$response['charts']['servicePopularity']['values'] = $serviceCounts;
$stmt->close();

// Recent Bookings (last 5, completed only)
$sql = "SELECT r.id, r.customer_name, s.name as serviceName, r.request_date, r.status
        FROM service_requests r
        LEFT JOIN services s ON r.service_id = s.id
        WHERE r.provider_id = ? AND r.status = 'completed'
        ORDER BY r.request_date DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$recentBookings = [];
while ($row = $result->fetch_assoc()) {
    $recentBookings[] = [
        'id' => $row['id'],
        'customerName' => $row['customer_name'],
        'serviceName' => $row['serviceName'],
        'date' => $row['request_date'],
        'status' => ucfirst($row['status'])
    ];
}
$response['recentBookings'] = $recentBookings;
$stmt->close();

// Ratings (from reviews table for this provider)
$sql = "SELECT rating FROM reviews WHERE provider_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$ratings = [0,0,0,0,0]; // 5,4,3,2,1 stars
$totalRatings = 0;
$sumRatings = 0;
while ($row = $result->fetch_assoc()) {
    $star = intval($row['rating']);
    if ($star >= 1 && $star <= 5) {
        $ratings[5-$star]++;
        $totalRatings++;
        $sumRatings += $star;
    }
}
$response['charts']['ratings'] = $ratings;
$response['summary']['averageRating'] = $totalRatings ? round($sumRatings / $totalRatings, 2) : 0;

// Customer Satisfaction (percentage of 4 or 5 star reviews)
$satisfied = $ratings[0] + $ratings[1]; // 5 and 4 star
$response['summary']['satisfactionRate'] = $totalRatings ? round(($satisfied / $totalRatings) * 100, 1) : 0;

// Done
echo json_encode($response); 
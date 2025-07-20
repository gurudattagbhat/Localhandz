<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');
header('Content-Type: application/json');
require_once '../db_connect.php';

// Catch fatal errors and output JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server error. Check php_errors.log']);
    }
});

// Get provider ID from session
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$provider_id = $_SESSION['user_id'];

try {
    // Initialize response array
    $response = [
        'success' => true,
        'summary' => [],
        'charts' => [],
        'recentBookings' => []
    ];

    // 1. Get Summary Statistics
    // Total Revenue
    $sql = "SELECT COALESCE(SUM(amount), 0) as total_revenue FROM service_requests WHERE provider_id = ? AND status = 'completed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $revenue = $result->fetch_assoc();
    $response['summary']['totalRevenue'] = $revenue['total_revenue'] ?? 0;
    $stmt->close();

    // Total Orders
    $sql = "SELECT COUNT(*) as total_orders FROM service_requests WHERE provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_assoc();
    $response['summary']['totalOrders'] = $orders['total_orders'] ?? 0;
    $stmt->close();

    // Average Rating
    $sql = "SELECT COALESCE(AVG(rating), 0) as avg_rating FROM reviews WHERE provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rating = $result->fetch_assoc();
    $response['summary']['averageRating'] = round($rating['avg_rating'], 1);
    $stmt->close();

    // Satisfaction Rate (percentage of 4-5 star ratings)
    $sql = "SELECT COUNT(*) as total_reviews, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_reviews FROM reviews WHERE provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $satisfaction = $result->fetch_assoc();
    $response['summary']['satisfactionRate'] = ($satisfaction['total_reviews'] > 0) ? round(($satisfaction['positive_reviews'] / $satisfaction['total_reviews']) * 100) : 0;
    $stmt->close();

    // 2. Get Chart Data
    // Revenue Trend (last 6 months)
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(amount), 0) as revenue FROM service_requests WHERE provider_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $revenueTrend = [];
    while ($row = $result->fetch_assoc()) {
        $revenueTrend[] = $row;
    }
    $response['charts']['revenueTrend'] = [
        'labels' => array_column($revenueTrend, 'month'),
        'values' => array_column($revenueTrend, 'revenue')
    ];
    $stmt->close();

    // Orders by Status
    $sql = "SELECT status, COUNT(*) as count FROM service_requests WHERE provider_id = ? GROUP BY status";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ordersStatus = [];
    while ($row = $result->fetch_assoc()) {
        $ordersStatus[] = $row;
    }
    $response['charts']['ordersStatus'] = [
        'labels' => array_column($ordersStatus, 'status'),
        'values' => array_column($ordersStatus, 'count')
    ];
    $stmt->close();

    // Service Popularity
    $sql = "SELECT s.name, COUNT(sr.id) as order_count FROM service_requests sr JOIN services s ON sr.service_id = s.id WHERE sr.provider_id = ? GROUP BY s.id, s.name ORDER BY order_count DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $servicePopularity = [];
    while ($row = $result->fetch_assoc()) {
        $servicePopularity[] = $row;
    }
    $response['charts']['servicePopularity'] = [
        'labels' => array_column($servicePopularity, 'name'),
        'values' => array_column($servicePopularity, 'order_count')
    ];
    $stmt->close();

    // Ratings Distribution
    $sql = "SELECT rating, COUNT(*) as count FROM reviews WHERE provider_id = ? GROUP BY rating ORDER BY rating DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ratings = [];
    while ($row = $result->fetch_assoc()) {
        $ratings[] = $row;
    }
    // Initialize all possible ratings
    $ratingCounts = array_fill(1, 5, 0);
    foreach ($ratings as $rating) {
        $ratingCounts[$rating['rating']] = $rating['count'];
    }
    $response['charts']['ratings'] = array_values($ratingCounts);
    $stmt->close();

    // 3. Get Recent Bookings
    $sql = "SELECT sr.id, c.name as customer_name, s.name as service_name, sr.created_at as date, sr.amount, sr.status FROM service_requests sr JOIN customers c ON sr.customer_id = c.id JOIN services s ON sr.service_id = s.id WHERE sr.provider_id = ? ORDER BY sr.created_at DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recentBookings = [];
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
    $response['recentBookings'] = $recentBookings;
    $stmt->close();

    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    error_log($e);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}

<?php
require_once 'db_connection.php';

function getRevenueData($days = 30) {
    global $conn;
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$days days"));
    
    $query = "SELECT DATE(booking_date) as date, SUM(amount) as revenue 
              FROM bookings 
              WHERE booking_date BETWEEN ? AND ? 
              GROUP BY DATE(booking_date) 
              ORDER BY date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $labels = [];
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M d', strtotime($row['date']));
        $data[] = $row['revenue'];
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

function getServicePerformance() {
    global $conn;
    
    $query = "SELECT s.name, COUNT(b.id) as bookings, SUM(b.amount) as revenue 
              FROM services s 
              LEFT JOIN bookings b ON s.id = b.service_id 
              GROUP BY s.id 
              ORDER BY revenue DESC 
              LIMIT 5";
    
    $result = $conn->query($query);
    
    $labels = [];
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['name'];
        $data[] = $row['bookings'];
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

function getCustomerSatisfaction() {
    global $conn;
    
    $query = "SELECT 
                CASE 
                    WHEN rating >= 4.5 THEN 'Excellent'
                    WHEN rating >= 3.5 THEN 'Good'
                    WHEN rating >= 2.5 THEN 'Average'
                    WHEN rating >= 1.5 THEN 'Poor'
                    ELSE 'Very Poor'
                END as rating_category,
                COUNT(*) as count
              FROM reviews 
              GROUP BY rating_category 
              ORDER BY rating_category DESC";
    
    $result = $conn->query($query);
    
    $labels = [];
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['rating_category'];
        $data[] = $row['count'];
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

function getTopServices() {
    global $conn;
    
    $query = "SELECT s.name, SUM(b.amount) as revenue, 
              (SUM(b.amount) - LAG(SUM(b.amount)) OVER (ORDER BY SUM(b.amount) DESC)) / LAG(SUM(b.amount)) OVER (ORDER BY SUM(b.amount) DESC) * 100 as growth
              FROM services s 
              LEFT JOIN bookings b ON s.id = b.service_id 
              GROUP BY s.id 
              ORDER BY revenue DESC 
              LIMIT 3";
    
    $result = $conn->query($query);
    $services = [];
    
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'name' => $row['name'],
            'revenue' => $row['revenue'],
            'growth' => $row['growth'] ? number_format($row['growth'], 1) . '%' : 'N/A'
        ];
    }
    
    return $services;
}

function getRecentBookings() {
    global $conn;
    
    $query = "SELECT b.id, s.name as service, c.name as customer, b.booking_date, b.status 
              FROM bookings b 
              JOIN services s ON b.service_id = s.id 
              JOIN customers c ON b.customer_id = c.id 
              ORDER BY b.booking_date DESC 
              LIMIT 3";
    
    $result = $conn->query($query);
    $bookings = [];
    
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'service' => $row['service'],
            'customer' => $row['customer'],
            'date' => $row['booking_date'],
            'status' => $row['status']
        ];
    }
    
    return $bookings;
}

function getAnalyticsSummary() {
    global $conn;
    
    // Get current period data
    $currentStart = date('Y-m-d', strtotime('-30 days'));
    $currentEnd = date('Y-m-d');
    
    // Get previous period data
    $previousStart = date('Y-m-d', strtotime('-60 days'));
    $previousEnd = date('Y-m-d', strtotime('-30 days'));
    
    // Revenue
    $query = "SELECT 
                (SELECT SUM(amount) FROM bookings WHERE booking_date BETWEEN ? AND ?) as current_revenue,
                (SELECT SUM(amount) FROM bookings WHERE booking_date BETWEEN ? AND ?) as previous_revenue";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $currentStart, $currentEnd, $previousStart, $previousEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $currentRevenue = $row['current_revenue'] ?? 0;
    $previousRevenue = $row['previous_revenue'] ?? 0;
    $revenueGrowth = $previousRevenue ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
    
    // Bookings
    $query = "SELECT 
                (SELECT COUNT(*) FROM bookings WHERE booking_date BETWEEN ? AND ?) as current_bookings,
                (SELECT COUNT(*) FROM bookings WHERE booking_date BETWEEN ? AND ?) as previous_bookings";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $currentStart, $currentEnd, $previousStart, $previousEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $currentBookings = $row['current_bookings'] ?? 0;
    $previousBookings = $row['previous_bookings'] ?? 0;
    $bookingsGrowth = $previousBookings ? (($currentBookings - $previousBookings) / $previousBookings) * 100 : 0;
    
    // Rating
    $query = "SELECT AVG(rating) as avg_rating FROM reviews";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $avgRating = $row['avg_rating'] ?? 0;
    
    // Customers
    $query = "SELECT 
                (SELECT COUNT(*) FROM customers WHERE created_at BETWEEN ? AND ?) as current_customers,
                (SELECT COUNT(*) FROM customers WHERE created_at BETWEEN ? AND ?) as previous_customers";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $currentStart, $currentEnd, $previousStart, $previousEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $currentCustomers = $row['current_customers'] ?? 0;
    $previousCustomers = $row['previous_customers'] ?? 0;
    $customersGrowth = $previousCustomers ? (($currentCustomers - $previousCustomers) / $previousCustomers) * 100 : 0;
    
    return [
        'revenue' => [
            'amount' => $currentRevenue,
            'growth' => number_format($revenueGrowth, 1) . '%'
        ],
        'bookings' => [
            'amount' => $currentBookings,
            'growth' => number_format($bookingsGrowth, 1) . '%'
        ],
        'rating' => [
            'amount' => number_format($avgRating, 1),
            'growth' => 'N/A'
        ],
        'customers' => [
            'amount' => $currentCustomers,
            'growth' => number_format($customersGrowth, 1) . '%'
        ]
    ];
}

function getProviderRevenueData($provider_id, $days = 30) {
    global $conn;
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$days days"));
    $query = "SELECT DATE(sr.request_date) as date, SUM(s.price) as revenue
              FROM service_requests sr
              JOIN services s ON sr.service_id = s.id
              WHERE sr.provider_id = ? AND sr.status = 'completed' AND sr.request_date BETWEEN ? AND ?
              GROUP BY DATE(sr.request_date)
              ORDER BY date";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $provider_id, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = [];
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M d', strtotime($row['date']));
        $data[] = $row['revenue'];
    }
    return ['labels' => $labels, 'data' => $data];
}

function getProviderServicePerformance($provider_id) {
    global $conn;
    $query = "SELECT s.name, COUNT(b.id) as bookings
              FROM services s
              LEFT JOIN bookings b ON s.id = b.service_id
              WHERE s.provider_id = ?
              GROUP BY s.id
              ORDER BY bookings DESC
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = [];
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['name'];
        $data[] = $row['bookings'];
    }
    return ['labels' => $labels, 'data' => $data];
}

function getProviderCustomerSatisfaction($provider_id) {
    global $conn;
    $query = "SELECT
                CASE
                    WHEN rating >= 4.5 THEN 'Excellent'
                    WHEN rating >= 3.5 THEN 'Good'
                    WHEN rating >= 2.5 THEN 'Average'
                    WHEN rating >= 1.5 THEN 'Poor'
                    ELSE 'Very Poor'
                END as rating_category,
                COUNT(*) as count
              FROM reviews
              WHERE provider_id = ?
              GROUP BY rating_category
              ORDER BY rating_category DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = [];
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['rating_category'];
        $data[] = $row['count'];
    }
    return ['labels' => $labels, 'data' => $data];
}

function getProviderRecentBookings($provider_id) {
    global $conn;
    $query = "SELECT b.id, s.name as service, c.name as customer, b.booking_date, b.status
              FROM bookings b
              JOIN services s ON b.service_id = s.id
              JOIN customers c ON b.customer_id = c.id
              WHERE b.provider_id = ?
              ORDER BY b.booking_date DESC
              LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'service' => $row['service'],
            'customer' => $row['customer'],
            'date' => $row['booking_date'],
            'status' => $row['status']
        ];
    }
    return $bookings;
}

function getProviderAnalyticsSummary($provider_id) {
    global $conn;
    $currentStart = date('Y-m-d', strtotime('-30 days'));
    $currentEnd = date('Y-m-d');
    $previousStart = date('Y-m-d', strtotime('-60 days'));
    $previousEnd = date('Y-m-d', strtotime('-30 days'));
    // Revenue: sum of service price for completed orders
    $query = "SELECT
                (SELECT SUM(s.price) FROM service_requests sr JOIN services s ON sr.service_id = s.id WHERE sr.provider_id = ? AND sr.status = 'completed' AND sr.request_date BETWEEN ? AND ?) as current_revenue,
                (SELECT SUM(s.price) FROM service_requests sr JOIN services s ON sr.service_id = s.id WHERE sr.provider_id = ? AND sr.status = 'completed' AND sr.request_date BETWEEN ? AND ?) as previous_revenue";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ississ", $provider_id, $currentStart, $currentEnd, $provider_id, $previousStart, $previousEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $currentRevenue = $row['current_revenue'] ?? 0;
    $previousRevenue = $row['previous_revenue'] ?? 0;
    $revenueGrowth = $previousRevenue ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
    // Bookings
    $query = "SELECT
                (SELECT COUNT(*) FROM service_requests WHERE provider_id = ? AND status = 'completed' AND request_date BETWEEN ? AND ?) as current_bookings,
                (SELECT COUNT(*) FROM service_requests WHERE provider_id = ? AND status = 'completed' AND request_date BETWEEN ? AND ?) as previous_bookings";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ississ", $provider_id, $currentStart, $currentEnd, $provider_id, $previousStart, $previousEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $currentBookings = $row['current_bookings'] ?? 0;
    $previousBookings = $row['previous_bookings'] ?? 0;
    $bookingsGrowth = $previousBookings ? (($currentBookings - $previousBookings) / $previousBookings) * 100 : 0;
    // Rating
    $query = "SELECT AVG(rating) as avg_rating FROM reviews WHERE provider_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $avgRating = $row['avg_rating'] ?? 0;
    return [
        'revenue' => [
            'amount' => $currentRevenue,
            'growth' => number_format($revenueGrowth, 1) . '%'
        ],
        'bookings' => [
            'amount' => $currentBookings,
            'growth' => number_format($bookingsGrowth, 1) . '%'
        ],
        'rating' => [
            'amount' => number_format($avgRating, 1),
            'growth' => 'N/A'
        ]
    ];
}
?>
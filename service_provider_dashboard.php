<?php
/*
 * DATABASE CONFIGURATION REQUIRED:
 * Update database credentials below (lines 20-22)
 * Replace with your actual database credentials
 */

// DEBUG: Show all errors (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_path', '/');
session_start();
error_log("DASHBOARD SESSION: " . print_r($_SESSION, true));

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, return an error response
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "local_handz";  // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// --- Only handle registration if registration POST data is present ---
if (
    isset($_POST['name'], $_POST['email'], $_POST['phone'], $_POST['service'], $_POST['area'], $_POST['password'], $_POST['security_question'], $_POST['security_answer'])
) {
    // Registration logic
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $service = $_POST['service'];
    $area = $_POST['area'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];
    // Handle photo upload
    if (!empty($_FILES['photo']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= 2 * 1024 * 1024) {
            $photo = 'uploads/' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid photo. Only JPEG, PNG, and GIF files under 2MB are allowed.']);
            exit;
        }
    } else {
        $photo = 'images/default-profile.png';
    }
    // Save the photo URL in the database
    $sql = "INSERT INTO service_providers (name, email, phone, service, area, photo, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $name, $email, $phone, $service, $area, $photo, $password, $security_question, $security_answer);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save service provider details: ' . $stmt->error]);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
    exit;
}
// --- End registration logic ---

// Get user details
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM service_providers WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

// Fetch user details if exists
if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
    $user_name = htmlspecialchars($user['name']);
    $user_email = htmlspecialchars($user['email']);
    $profile_picture = $user['photo'] ?? 'images/default-profile.png';
} else {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Fetch service count
$sql_service_count = "SELECT COUNT(*) as total_services FROM services WHERE provider_id = ?";
$stmt_service_count = $conn->prepare($sql_service_count);
$stmt_service_count->bind_param("i", $user_id);
$stmt_service_count->execute();
$result_service_count = $stmt_service_count->get_result();
$service_count = $result_service_count->fetch_assoc()['total_services'] ?? 0;

// Fetch order count
$sql_order_count = "SELECT COUNT(*) as total_orders FROM service_requests WHERE provider_id = ?";
$stmt_order_count = $conn->prepare($sql_order_count);
$stmt_order_count->bind_param("i", $user_id);
$stmt_order_count->execute();
$result_order_count = $stmt_order_count->get_result();
$order_count = $result_order_count->fetch_assoc()['total_orders'] ?? 0;

// Fetch total earnings
$sql_total_earnings = "SELECT SUM(s.price) as total_earnings FROM service_requests sr JOIN services s ON sr.service_id = s.id WHERE sr.provider_id = ? AND sr.status = 'completed'";
$stmt_total_earnings = $conn->prepare($sql_total_earnings);
$stmt_total_earnings->bind_param("i", $user_id);
$stmt_total_earnings->execute();
$result_total_earnings = $stmt_total_earnings->get_result();
$total_earnings = $result_total_earnings->fetch_assoc()['total_earnings'] ?? 0;

// Fetch services
$sql_services = "SELECT id, name, category, price FROM services WHERE provider_id = ?";
$stmt_services = $conn->prepare($sql_services);
$stmt_services->bind_param("i", $user_id);
$stmt_services->execute();
$result_services = $stmt_services->get_result();
$services = [];
while ($row = $result_services->fetch_assoc()) {
    $services[] = $row;
}

// Fetch orders
$sql_orders = "SELECT sr.id as order_id, sr.customer_name, s.name as service_name, sr.status, sr.customer_address, sr.request_date, s.price 
               FROM service_requests sr
               LEFT JOIN services s ON sr.service_id = s.id
               WHERE sr.provider_id = ?";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
$orders = [];
while ($row = $result_orders->fetch_assoc()) {
    $orders[] = $row;
}

// --- Calculate Analytics ---
$analytics = [
    'completionRate' => 0,
    'popularService' => 'N/A'
];

// Calculate Completion Rate
$sql_all_orders_count = "SELECT COUNT(*) as total FROM service_requests WHERE provider_id = ?";
$stmt_all_count = $conn->prepare($sql_all_orders_count);
$stmt_all_count->bind_param("i", $user_id);
$stmt_all_count->execute();
$total_orders_for_rate = $stmt_all_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_all_count->close();

if ($total_orders_for_rate > 0) {
    $sql_completed_count = "SELECT COUNT(*) as completed FROM service_requests WHERE provider_id = ? AND status = 'completed'";
    $stmt_completed_count = $conn->prepare($sql_completed_count);
    $stmt_completed_count->bind_param("i", $user_id);
    $stmt_completed_count->execute();
    $completed_orders = $stmt_completed_count->get_result()->fetch_assoc()['completed'] ?? 0;
    $stmt_completed_count->close();
    $analytics['completionRate'] = round(($completed_orders / $total_orders_for_rate) * 100, 1); 
}

// Find Most Popular Service
$sql_popular = "SELECT s.name, COUNT(sr.id) as order_count 
                FROM service_requests sr 
                JOIN services s ON sr.service_id = s.id 
                WHERE sr.provider_id = ? 
                GROUP BY sr.service_id 
                ORDER BY order_count DESC 
                LIMIT 1";
$stmt_popular = $conn->prepare($sql_popular);
$stmt_popular->bind_param("i", $user_id);
$stmt_popular->execute();
$result_popular = $stmt_popular->get_result();
if ($result_popular->num_rows > 0) {
    $popular_row = $result_popular->fetch_assoc();
    $analytics['popularService'] = htmlspecialchars($popular_row['name']) . " (" . $popular_row['order_count'] . " orders)";
}
$stmt_popular->close();
// --- End Analytics Calculation ---

// Close the database connection
$conn->close();

// Return all data as JSON, including analytics
echo json_encode([
    'user' => [
        'id' => $user_id,
        'name' => $user_name,
        'email' => $user_email,
        'phone' => $user['phone'],
        'service' => $user['service'],
        'area' => $user['area'],
        'profilePicture' => $profile_picture,
    ],
    'stats' => [
        'totalServices' => $service_count,
        'totalOrders' => $order_count,
        'totalEarnings' => $total_earnings,
    ],
    'services' => $services,
    'orders' => $orders,
    'analytics' => $analytics
]);
?>

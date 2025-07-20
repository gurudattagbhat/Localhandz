<?php
// Include database connection file
include_once '../config/database.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the provider ID (should be stored in session after login)
    session_start();
    $provider_id = isset($_SESSION['provider_id']) ? $_SESSION['provider_id'] : 1; // Default to 1 for testing
    
    // Fetch total services
    $servicesQuery = "SELECT COUNT(*) as total FROM services WHERE provider_id = :provider_id";
    $servicesStmt = $db->prepare($servicesQuery);
    $servicesStmt->bindParam(':provider_id', $provider_id);
    $servicesStmt->execute();
    $servicesData = $servicesStmt->fetch(PDO::FETCH_ASSOC);
    $totalServices = $servicesData['total'] ? $servicesData['total'] : 0;
    
    // Fetch total appointments
    $appointmentsQuery = "SELECT COUNT(*) as total FROM bookings WHERE provider_id = :provider_id";
    $appointmentsStmt = $db->prepare($appointmentsQuery);
    $appointmentsStmt->bindParam(':provider_id', $provider_id);
    $appointmentsStmt->execute();
    $appointmentsData = $appointmentsStmt->fetch(PDO::FETCH_ASSOC);
    $totalAppointments = $appointmentsData['total'] ? $appointmentsData['total'] : 0;
    
    // Fetch total revenue
    $revenueQuery = "SELECT SUM(amount) as total FROM bookings WHERE provider_id = :provider_id AND status = 'completed'";
    $revenueStmt = $db->prepare($revenueQuery);
    $revenueStmt->bindParam(':provider_id', $provider_id);
    $revenueStmt->execute();
    $revenueData = $revenueStmt->fetch(PDO::FETCH_ASSOC);
    $totalRevenue = $revenueData['total'] ? $revenueData['total'] : 0;
    
    // Fetch average rating
    $ratingQuery = "SELECT AVG(rating) as average FROM ratings WHERE provider_id = :provider_id";
    $ratingStmt = $db->prepare($ratingQuery);
    $ratingStmt->bindParam(':provider_id', $provider_id);
    $ratingStmt->execute();
    $ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
    $averageRating = $ratingData['average'] ? round($ratingData['average'], 1) : 0;
    
    // Fetch total clients
    $clientsQuery = "SELECT COUNT(DISTINCT customer_id) as total FROM bookings WHERE provider_id = :provider_id";
    $clientsStmt = $db->prepare($clientsQuery);
    $clientsStmt->bindParam(':provider_id', $provider_id);
    $clientsStmt->execute();
    $clientsData = $clientsStmt->fetch(PDO::FETCH_ASSOC);
    $totalClients = $clientsData['total'] ? $clientsData['total'] : 0;
    
    // Dummy data for charts (replace with actual database queries when ready)
    $appointmentsData = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'values' => [5, 8, 12, 10, 15, 20]
    ];
    
    $revenueData = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'values' => [500, 800, 1200, 1000, 1500, 2000]
    ];
    
    $ratingData = [8, 12, 5, 3, 2]; // 5 stars, 4 stars, 3 stars, 2 stars, 1 star
    
    // Prepare the response
    $response = [
        'totalServices' => $totalServices,
        'totalAppointments' => $totalAppointments,
        'totalRevenue' => $totalRevenue,
        'averageRating' => $averageRating,
        'totalClients' => $totalClients,
        'appointmentsData' => $appointmentsData,
        'revenueData' => $revenueData,
        'ratingData' => $ratingData
    ];
    
    // Return the response as JSON
    echo json_encode($response);
    
} catch (Exception $e) {
    // Handle errors
    echo json_encode([
        'error' => true,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 
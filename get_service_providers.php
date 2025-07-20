<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $service_type = $_GET['service'] ?? '';
    if (empty($service_type)) {
        throw new Exception('Service type is required');
    }

    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Fetch service providers for the given service type (category)
    $stmt = $conn->prepare('
        SELECT 
            sp.id AS provider_id,
            sp.name AS provider_name,
            sp.service AS provider_service,
            s.id AS service_id,
            s.price AS service_price,
            s.name AS service_name,
            s.category AS service_category,
            s.photo AS service_photo
        FROM service_providers sp
        INNER JOIN services s ON sp.id = s.provider_id
        WHERE sp.service = :service_type OR s.category = :service_type
    ');
    $stmt->bindParam(':service_type', $service_type);
    $stmt->execute();

    $providers = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Fetch average rating and review count for this service
        $service_id = $row['service_id'];
        $ratingResult = $conn->query("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE service_id = $service_id");
        $ratingRow = $ratingResult->fetch(PDO::FETCH_ASSOC);
        $avg_rating = round($ratingRow['avg_rating'] ?? 0, 1);
        $review_count = $ratingRow['review_count'] ?? 0;

        $providers[] = [
            'id' => $row['provider_id'],
            'service_id' => $row['service_id'],
            'name' => $row['provider_name'],
            'image' => $row['service_photo'] ?: 'images/default-provider.jpg',
            'price' => $row['service_price'],
            'service_name' => $row['service_name'],
            'category' => $row['service_category'],
            'rating' => $avg_rating,
            'reviews' => $review_count,
            'availability' => [
                '09:00 AM', '11:00 AM', '01:00 PM', '03:00 PM', '05:00 PM'
            ] // Dummy slots
        ];
    }

    echo json_encode([
        'success' => true,
        'providers' => $providers
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
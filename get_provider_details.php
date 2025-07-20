<?php
header('Content-Type: application/json');

// Use the correct DB credentials for your local setup
$host = 'localhost';
$db   = 'local_handz'; // Correct DB name
$user = 'root';        // Default XAMPP/WAMP username
$pass = '';            // Default XAMPP/WAMP password (empty)

$provider_id = $_GET['provider_id'] ?? null;
$service_id = $_GET['service_id'] ?? null;

if (!$provider_id || !$service_id) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // JOIN service_providers and services
    $stmt = $pdo->prepare('
        SELECT 
            sp.id AS provider_id,
            sp.name AS provider_name,
            sp.service AS provider_service,
            s.id AS service_id,
            s.price AS service_price,
            s.name AS service_name,
            s.category AS service_category,
            s.photo AS service_photo,
            s.description AS service_description
        FROM service_providers sp
        INNER JOIN services s ON sp.id = s.provider_id
        WHERE sp.id = ? AND s.id = ?
        LIMIT 1
    ');
    $stmt->execute([$provider_id, $service_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Fetch average rating and review count for this service
        $service_id = $row['service_id'];
        $ratingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE service_id = ?");
        $ratingStmt->execute([$service_id]);
        $ratingRow = $ratingStmt->fetch(PDO::FETCH_ASSOC);
        $avg_rating = round($ratingRow['avg_rating'] ?? 0, 1);
        $review_count = $ratingRow['review_count'] ?? 0;

        $provider = [
            'id' => $row['provider_id'],
            'service_id' => $row['service_id'],
            'name' => $row['provider_name'],
            'service_name' => $row['service_name'],
            'image' => $row['service_photo'] ?: 'images/default-provider.jpg',
            'price' => $row['service_price'],
            'category' => $row['service_category'],
            'description' => $row['service_description'],
            'rating' => $avg_rating,
            'reviews' => $review_count
        ];
        echo json_encode(['success' => true, 'provider' => $provider]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Provider not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
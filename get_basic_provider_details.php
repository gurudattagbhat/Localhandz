<?php
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'local_handz';
$user = 'root';
$pass = '';

$provider_id = $_GET['provider_id'] ?? null;

if (!$provider_id) {
    echo json_encode(['success' => false, 'error' => 'Missing provider_id']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch basic provider info
    $stmt = $pdo->prepare('SELECT id, name, email, phone, service, area, photo FROM service_providers WHERE id = ? LIMIT 1');
    $stmt->execute([$provider_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // Try to get address from provider_settings if available
        $address = $row['area'];
        $settingsStmt = $pdo->prepare("SELECT setting_value FROM provider_settings WHERE provider_id = ? AND category = 'account' AND setting_key = 'address' LIMIT 1");
        $settingsStmt->execute([$provider_id]);
        $settingsRow = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        if ($settingsRow && !empty($settingsRow['setting_value'])) {
            $address = $settingsRow['setting_value'];
        }
        $provider = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'service' => $row['service'],
            'area' => $row['area'],
            'address' => $address,
            'photo' => $row['photo'] ? $row['photo'] : 'images/default-provider.jpg'
        ];
        echo json_encode(['success' => true, 'provider' => $provider]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Provider not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

<?php
// get_booked_times.php
header('Content-Type: application/json');
require_once 'db_connect.php';

$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$provider_id || !$date) {
    echo json_encode(['success' => false, 'error' => 'Missing provider_id or date']);
    exit;
}

// Only get bookings for the selected date (YYYY-MM-DD)
$date_start = $date . ' 00:00:00';
$date_end = $date . ' 23:59:59';

$sql = "SELECT request_date FROM service_requests WHERE provider_id = ? AND request_date BETWEEN ? AND ? AND status != 'cancelled'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $provider_id, $date_start, $date_end);
$stmt->execute();
$result = $stmt->get_result();

$booked_times = [];
while ($row = $result->fetch_assoc()) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $row['request_date']);
    if ($dt) {
        $booked_times[] = $dt->format('H:i');
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'booked_times' => $booked_times]);

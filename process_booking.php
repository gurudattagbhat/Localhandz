<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required_fields = ['provider_id', 'service_type', 'date', 'time', 'location'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert booking
        $stmt = $conn->prepare("
            INSERT INTO bookings (
                provider_id,
                customer_id,
                service_type,
                service_date,
                service_time,
                location,
                notes,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        $customer_id = $_SESSION['user_id'] ?? null;
        $notes = $data['notes'] ?? '';
        
        $stmt->bind_param(
            "iisssss",
            $data['provider_id'],
            $customer_id,
            $data['service_type'],
            $data['date'],
            $data['time'],
            $data['location'],
            $notes
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create booking");
        }

        $booking_id = $conn->insert_id;

        // Update provider's availability
        $stmt = $conn->prepare("
            UPDATE service_providers 
            SET availability = JSON_REMOVE(
                availability,
                JSON_UNQUOTE(JSON_SEARCH(availability, 'one', ?))
            )
            WHERE id = ?
        ");

        $stmt->bind_param("si", $data['time'], $data['provider_id']);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'booking_id' => $booking_id,
            'message' => 'Booking created successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?> 
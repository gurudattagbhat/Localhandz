<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    $requiredFields = ['customer', 'items', 'total'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert customer information
        $customer = $data['customer'];
        $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, address, city, state, zip_code) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", 
            $customer['name'],
            $customer['email'],
            $customer['phone'],
            $customer['address'],
            $customer['city'],
            $customer['state'],
            $customer['zip']
        );
        $stmt->execute();
        $customerId = $conn->insert_id;

        // Insert order
        $total = $data['total'];
        $status = 'pending';
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, status, created_at) 
                               VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ids", $customerId, $total, $status);
        $stmt->execute();
        $orderId = $conn->insert_id;

        // Insert order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, service_id, quantity, price) 
                               VALUES (?, ?, ?, ?)");
        
        foreach ($data['items'] as $item) {
            $stmt->bind_param("iiid", 
                $orderId,
                $item['id'],
                $item['quantity'],
                $item['price']
            );
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        // Return success response with order ID
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Order placed successfully'
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
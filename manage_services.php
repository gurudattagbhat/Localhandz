<?php
session_start();
require 'db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'fetchServices':
        fetchServices($conn, $user_id);
        break;

    case 'addService':
        error_log(print_r($_POST, true)); // Log the POST data
        error_log(print_r($_FILES, true)); // Log the uploaded files

        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        if ($price === false || $price < 0) { // Ensure price is valid non-negative number
            echo json_encode(['success' => false, 'error' => 'Invalid price format.']);
            exit;
        }
        $description = $_POST['description'] ?? '';
        $provider_id = $_SESSION['user_id']; // Assuming the provider is logged in
        $photo = '';

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $photo = 'uploads/' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        }

        $sql = "INSERT INTO services (provider_id, name, category, price, description, photo) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdss", $provider_id, $name, $category, $price, $description, $photo);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Service added successfully']);
        } else {
            error_log('Failed to add service: ' . $stmt->error); // Log the specific SQL error
            echo json_encode(['success' => false, 'error' => 'Failed to add service: ' . $stmt->error]);
        }
        exit;
        break;

    case 'removeService':
        removeService($conn, $user_id);
        break;

    case 'fetchServiceById':
        $id = $_POST['id'] ?? 0;
        $sql = "SELECT * FROM services WHERE id = ? AND provider_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $service = $result->fetch_assoc();
            echo json_encode(['success' => true, 'service' => $service]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Service not found']);
        }
        break;

    case 'updateService':
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $photo = '';
        $updatePhoto = false;

        if (!empty($_FILES['photo']['name'])) {
            $photo = 'uploads/' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
            $updatePhoto = true;
        }

        if ($updatePhoto) {
            $sql = "UPDATE services SET name = ?, category = ?, price = ?, description = ?, photo = ? WHERE id = ? AND provider_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdssii", $name, $category, $price, $description, $photo, $id, $user_id);
        } else {
            $sql = "UPDATE services SET name = ?, category = ?, price = ?, description = ? WHERE id = ? AND provider_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdsii", $name, $category, $price, $description, $id, $user_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Service updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update service: ' . $stmt->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

// Fetch services for the logged-in service provider
function fetchServices($conn, $user_id) {
    $sql = "SELECT id, name, category, price, description, photo FROM services WHERE provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }

    echo json_encode(['success' => true, 'services' => $services]);
}

// Add a new service
function addService($conn, $user_id) {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?? 0;

    // Validate input
    if (empty($name) || empty($category) || $price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid service details']);
        return;
    }

    // Prepare SQL query
    $sql = "INSERT INTO services (provider_id, name, category, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issd", $user_id, $name, $category, $price); // 'd' for decimal

    // Execute query and handle response
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Service added successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add service: ' . $stmt->error]);
    }
}

// Remove a service
function removeService($conn, $user_id) {
    $service_id = $_POST['id'] ?? 0;

    if ($service_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid service ID']);
        return;
    }

    $sql = "DELETE FROM services WHERE id = ? AND provider_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $service_id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Service removed successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove service']);
    }
}
?>
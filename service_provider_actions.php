<?php
session_start();
require 'db_connect.php';

// Check if the user is logged in for actions that require authentication
if (!isset($_SESSION['user_id']) && $_POST['action'] !== 'register') {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Get the action parameter
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'fetchDetails':
        fetchServiceProviderDetails($conn);
        break;

    case 'updateProfile':
        updateServiceProviderProfile($conn);
        break;

    case 'register':
        registerServiceProvider($conn);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

// Fetch service provider details
function fetchServiceProviderDetails($conn) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT id, name, email, phone, service, area, photo, security_question, security_answer FROM service_providers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }
}

// Update service provider profile
function updateServiceProviderProfile($conn) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $service = $_POST['service'];
    $area = $_POST['area'];
    $security_question = $_POST['security-question'];
    $security_answer = $_POST['security-answer'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Handle photo upload
    if (!empty($_FILES['photo']['name'])) {
        $photo = 'uploads/' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    } else {
        $photo = null;
    }

    // Update query
    $sql = "UPDATE service_providers SET name = ?, email = ?, phone = ?, service = ?, area = ?, security_question = ?, security_answer = ?";
    $params = [$name, $email, $phone, $service, $area, $security_question, $security_answer];

    if ($photo) {
        $sql .= ", photo = ?";
        $params[] = $photo;
    }

    if ($password) {
        $sql .= ", password = ?";
        $params[] = $password;
    }

    $sql .= " WHERE id = ?";
    $params[] = $user_id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat("s", count($params)), ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
    }
}

// Register a new service provider
function registerServiceProvider($conn) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $service = $_POST['service'];
    $area = $_POST['area'];
    $security_question = $_POST['security-question'];
    $security_answer = $_POST['security-answer'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Handle photo upload
    if (!empty($_FILES['photo']['name'])) {
        $photo = 'uploads/' . basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
    } else {
        $photo = 'images/default-profile.png'; // Default photo
    }

    // Insert query
    $sql = "INSERT INTO service_providers (name, email, phone, service, area, photo, password, security_question, security_answer) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssss", $name, $email, $phone, $service, $area, $photo, $password, $security_question, $security_answer);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to register']);
    }
}
?>
<?php
session_start();
header('Content-Type: application/json');

// Enable error logging (remove in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Include database connection
require 'db_connect.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid JSON input']));
}

// Validate inputs
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $input['password'] ?? '';
$role = $input['role'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid email format']));
}

if (empty($password)) {
    http_response_code(400);
    die(json_encode(['error' => 'Password is required']));
}

if (!in_array($role, ['user', 'service_provider'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid role specified']));
}

try {
    // Determine the table based on the role
    $table = ($role === 'user') ? 'users' : 'service_providers';
    $stmt = $conn->prepare("SELECT id, name, email, password FROM $table WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);

            // Set user data in session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $role
            ];

            // Respond with success and redirect URL
            echo json_encode([
                'success' => true,
                'user' => [
                    'name' => $user['name'],
                    'role' => $role
                ],
                'redirect' => ($role === 'user') ? 'index.html' : 'service_provider_dashboard.php'
            ]);
            exit();
        }
    }

    // Invalid credentials
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit();

} catch (Exception $e) {
    // Log the error and respond with a generic error message
    error_log('Login error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit();
}
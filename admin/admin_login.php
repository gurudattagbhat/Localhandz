<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$captcha = $_POST['captcha'] ?? '';

if (empty($username) || empty($password) || empty($captcha)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!isset($_SESSION['admin_captcha']) || strtolower($captcha) !== strtolower($_SESSION['admin_captcha'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid captcha']);
    exit;
}
unset($_SESSION['admin_captcha']);

try {
    // Check if admin table exists
    $check = $conn->query("SHOW TABLES LIKE 'admin'");
    if (!$check || $check->num_rows === 0) {
        error_log('Admin table does not exist in database!', 3, __DIR__ . '/../php_errors.log');
        echo json_encode(['success' => false, 'message' => 'Server error: admin table missing']);
        exit;
    }
    // Check if admin table is empty
    $check2 = $conn->query("SELECT COUNT(*) as cnt FROM admin");
    $row2 = $check2 ? $check2->fetch_assoc() : null;
    if (!$row2 || $row2['cnt'] == 0) {
        error_log('Admin table is empty!', 3, __DIR__ . '/../php_errors.log');
        echo json_encode(['success' => false, 'message' => 'Server error: no admin user']);
        exit;
    }
    $sql = "SELECT id, username FROM admin WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error . ' | SQL: ' . $sql, 3, __DIR__ . '/../php_errors.log');
        echo json_encode(['success' => false, 'message' => 'Server error (prepare)']);
        exit;
    }
    $stmt->bind_param("ss", $username, $password);
    if (!$stmt->execute()) {
        error_log('Execute failed: ' . $stmt->error . ' | SQL: ' . $sql, 3, __DIR__ . '/../php_errors.log');
        echo json_encode(['success' => false, 'message' => 'Server error (execute)']);
        exit;
    }
    $result = $stmt->get_result();
    if ($result === false) {
        error_log('Get result failed: ' . $stmt->error . ' | SQL: ' . $sql, 3, __DIR__ . '/../php_errors.log');
        echo json_encode(['success' => false, 'message' => 'Server error (result)']);
        exit;
    }
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_logged_in'] = true;
        echo json_encode(['success' => true]);
    } else {
        error_log('Invalid credentials for username: ' . $username, 3, __DIR__ . '/../php_errors.log');
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage(), 3, __DIR__ . '/../php_errors.log');
    echo json_encode(['success' => false, 'message' => 'Server error (exception)']);
}

<?php
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database configuration (should be in environment variables)
$config = [
    'host' => 'localhost',
    'dbname' => 'local_handz',
    'username' => 'root',
    'password' => ''
];

// Database connection with error handling
try {
    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['dbname']
    );
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Service unavailable']);
    exit();
}

// Main request handler
try {
    if (!isset($_GET['action'])) {
        throw new Exception('Action parameter required', 400);
    }

    $action = $_GET['action'];
    
    switch ($action) {
        case 'getServices':
            handleGetServices($conn);
            break;
            
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            handleLogin($conn);
            break;
            
        case 'getUserDetails':
            handleGetUserDetails($conn);
            break;
            
        case 'updateProfile':
            handleUpdateProfile($conn);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        default:
            throw new Exception('Invalid action', 400);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();

// Handler functions
function handleGetServices($conn) {
    $query = "SELECT id, name, description, image_url FROM services WHERE is_active = 1";
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('Failed to fetch services', 500);
    }
    
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    
    echo json_encode(['data' => $services]);
}

function handleLogin($conn) {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';
    $role = in_array($data['role'] ?? '', ['user', 'service_provider']) ? $data['role'] : 'user';
    
    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format', 400);
    }
    
    if (empty($password)) {
        throw new Exception('Password is required', 400);
    }
    
    // Login attempt limiting
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    
    if ($_SESSION['login_attempts'] > 5) {
        throw new Exception('Too many login attempts. Try again later.', 429);
    }
    
    // Prepare query based on role
    $table = $role === 'user' ? 'users' : 'service_providers';
    $stmt = $conn->prepare("SELECT id, name, email, password FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $_SESSION['login_attempts']++;
        throw new Exception('Invalid credentials', 401);
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password'])) {
        $_SESSION['login_attempts']++;
        throw new Exception('Invalid credentials', 401);
    }
    
    // Successful login
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $role;
    $_SESSION['login_attempts'] = 0;
    
    echo json_encode([
        'success' => true,
        'user' => [
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $role
        ]
    ]);
}

function handleGetUserDetails($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['loggedIn' => false]);
        exit;
    }
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'] ?? 'user';
    if ($role === 'service_provider') {
        $stmt = $conn->prepare('SELECT id, name, email, phone FROM service_providers WHERE id = ?');
    } else {
        $stmt = $conn->prepare('SELECT id, name, email, phone FROM users WHERE id = ?');
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['loggedIn' => true, 'user' => $user]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
}

function handleUpdateProfile($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'] ?? 'user';
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $security_question = $data['security_question'] ?? '';
    $security_answer = $data['security_answer'] ?? '';
    $password = $data['password'] ?? '';
    $fields = 'name=?, email=?, phone=?, security_question=?, security_answer=?';
    $params = [$name, $email, $phone, $security_question, $security_answer];
    $types = 'sssss';
    if ($password) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $fields .= ', password=?';
        $params[] = $password_hash;
        $types .= 's';
    }
    if ($role === 'service_provider') {
        $sql = "UPDATE service_providers SET $fields WHERE id=?";
    } else {
        $sql = "UPDATE users SET $fields WHERE id=?";
    }
    $params[] = $user_id;
    $types .= 'i';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
}

function handleLogout() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    echo json_encode(['success' => true]);
}
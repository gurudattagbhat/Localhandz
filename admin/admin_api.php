<?php
session_start();
error_log('POST: ' . print_r($_POST, true), 3, __DIR__ . '/../php_errors.log');
error_log('SESSION: ' . print_r($_SESSION, true), 3, __DIR__ . '/../php_errors.log');
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connect.php';
if (!isset($conn) || !$conn) { error_log('DB connection is null in admin_api.php', 3, __DIR__ . '/../php_errors.log'); }

// Check if admin is logged in
function checkAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
}

// Handle different actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'get_section':
        checkAdminLogin();
        handleGetSection();
        break;
    case 'get_stats':
        checkAdminLogin();
        handleGetStats();
        break;
    case 'delete_provider':
        checkAdminLogin();
        handleDeleteProvider();
        break;
    case 'delete_user':
        checkAdminLogin();
        handleDeleteUser();
        break;
    case 'delete_feedback':
        checkAdminLogin();
        handleDeleteFeedback();
        break;
    case 'delete_order':
        require_once __DIR__ . '/../db_connect.php';
        global $conn;
        $orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($orderId) {
            $stmt = $conn->prepare("DELETE FROM service_requests WHERE id = ?");
            $stmt->bind_param("i", $orderId);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        }
        exit;
    case 'get_admin_profile':
        checkAdminLogin();
        handleGetAdminProfile();
        break;
    case 'update_admin_profile':
        checkAdminLogin();
        handleUpdateAdminProfile();
        break;
    case 'get_provider_details':
        checkAdminLogin();
        handleGetProviderDetails();
        break;
    case 'get_providers':
        require_once __DIR__ . '/../db_connect.php';
        global $conn;
        $providers = [];
        $result = $conn->query("SELECT id, name, email, phone, service, area, photo, created_at FROM service_providers");
        while ($row = $result->fetch_assoc()) {
            $providers[] = $row;
        }
        echo json_encode(['providers' => $providers]);
        break;
    case 'get_users':
        require_once __DIR__ . '/../db_connect.php';
        global $conn;
        $users = [];
        $result = $conn->query("SELECT id, name, email, phone, created_at FROM users");
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['users' => $users]);
        break;
    case 'get_user_details':
        require_once __DIR__ . '/../db_connect.php';
        global $conn;
        $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }
        // User info
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        // Orders: match by BOTH customer_name AND customer_phone
        $stmt = $conn->prepare("SELECT * FROM service_requests WHERE customer_name = ? AND customer_phone = ?");
        $stmt->bind_param("ss", $user['name'], $user['phone']);
        $stmt->execute();
        $orders = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        // Reviews: match by BOTH reviewer_name AND (if available) user phone
        $stmt = $conn->prepare("SELECT * FROM reviews WHERE reviewer_name = ?");
        $stmt->bind_param("s", $user['name']);
        $stmt->execute();
        $reviews = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Only include review if the user's phone appears in the order history for this review
            // (best effort, since reviews table does not have phone/email)
            if (isset($user['phone']) && $user['phone'] !== '' && isset($row['service_id'])) {
                // Try to find a matching order for this service_id and user phone
                $orderCheck = $conn->prepare("SELECT id FROM service_requests WHERE service_id = ? AND customer_phone = ?");
                $orderCheck->bind_param("is", $row['service_id'], $user['phone']);
                $orderCheck->execute();
                $orderResult = $orderCheck->get_result();
                if ($orderResult->num_rows > 0) {
                    $reviews[] = $row;
                }
            } else {
                // Fallback: if no phone, just match by name (legacy data)
                $reviews[] = $row;
            }
        }
        echo json_encode([
            'success' => true,
            'user' => $user,
            'orders' => $orders,
            'reviews' => $reviews
        ]);
        exit;
    case 'get_orders':
        require_once __DIR__ . '/../db_connect.php';
        global $conn;
        $orders = [];
        $result = $conn->query("SELECT * FROM service_requests ORDER BY request_date DESC");
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        echo json_encode(['orders' => $orders]);
        break;
    case 'get_order_details':
        require_once __DIR__ . '/../db_connect.php';
        global $conn;
        $orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }
        // Order info
        $stmt = $conn->prepare("SELECT * FROM service_requests WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        // Provider info
        $provider = null;
        if (isset($order['provider_id'])) {
            $stmt = $conn->prepare("SELECT * FROM service_providers WHERE id = ?");
            $stmt->bind_param("i", $order['provider_id']);
            $stmt->execute();
            $provider = $stmt->get_result()->fetch_assoc();
        }
        // Service info
        $service = null;
        if (isset($order['service_id'])) {
            $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
            $stmt->bind_param("i", $order['service_id']);
            $stmt->execute();
            $service = $stmt->get_result()->fetch_assoc();
        }
        // Reviews for this order (by service_id and provider_id)
        $reviews = [];
        if (isset($order['service_id']) && isset($order['provider_id'])) {
            $stmt = $conn->prepare("SELECT * FROM reviews WHERE service_id = ? AND provider_id = ?");
            $stmt->bind_param("ii", $order['service_id'], $order['provider_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reviews[] = $row;
            }
        }
        echo json_encode([
            'success' => true,
            'order' => $order,
            'provider' => $provider,
            'service' => $service,
            'reviews' => $reviews
        ]);
        exit;
    case 'get_feedback':
        checkAdminLogin();
        require_once __DIR__ . '/../db_connect.php';
        global $conn;
        $feedback = [];
        $result = $conn->query("SELECT id, name, email, message, submitted_at FROM feedback ORDER BY submitted_at DESC");
        while ($row = $result->fetch_assoc()) {
            $feedback[] = $row;
        }
        echo json_encode(['success' => true, 'feedback' => $feedback]);
        exit;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleLogin() {
    require_once __DIR__ . '/../db_connect.php';
    global $conn;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    if (empty($username) || empty($password) || empty($captcha)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    if (!isset($_SESSION['admin_captcha']) || strtolower($captcha) !== strtolower($_SESSION['admin_captcha'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid captcha']);
        return;
    }
    unset($_SESSION['admin_captcha']);
    try {
        // Check if admin table exists
        $check = $conn->query("SHOW TABLES LIKE 'admin'");
        if (!$check || $check->num_rows === 0) {
            error_log('Admin table does not exist in database!', 3, __DIR__ . '/../php_errors.log');
            echo json_encode(['success' => false, 'message' => 'Server error: admin table missing']);
            return;
        }
        // Check if admin table is empty
        $check2 = $conn->query("SELECT COUNT(*) as cnt FROM admin");
        $row2 = $check2 ? $check2->fetch_assoc() : null;
        if (!$row2 || $row2['cnt'] == 0) {
            error_log('Admin table is empty!', 3, __DIR__ . '/../php_errors.log');
            echo json_encode(['success' => false, 'message' => 'Server error: no admin user'] );
            return;
        }
        $sql = "SELECT id, username FROM admin WHERE username = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('Prepare failed: ' . $conn->error . ' | SQL: ' . $sql, 3, __DIR__ . '/../php_errors.log');
            echo json_encode(['success' => false, 'message' => 'Server error (prepare)']);
            return;
        }
        $stmt->bind_param("ss", $username, $password);
        if (!$stmt->execute()) {
            error_log('Execute failed: ' . $stmt->error . ' | SQL: ' . $sql, 3, __DIR__ . '/../php_errors.log');
            echo json_encode(['success' => false, 'message' => 'Server error (execute)']);
            return;
        }
        $result = $stmt->get_result();
        if ($result === false) {
            error_log('Get result failed: ' . $stmt->error . ' | SQL: ' . $sql, 3, __DIR__ . '/../php_errors.log');
            echo json_encode(['success' => false, 'message' => 'Server error (result)']);
            return;
        }
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            echo json_encode(['success' => true]);
        } else {
            error_log('Invalid credentials for username: ' . $username, 3, __DIR__ . '/../php_errors.log');
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } catch (Exception $e) {
        error_log('Exception: ' . $e->getMessage(), 3, __DIR__ . '/../php_errors.log');
        echo json_encode(['success' => false, 'message' => 'Server error (exception)']);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true]);
}

function handleGetSection() {
    global $conn;
    require_once __DIR__ . '/../db_connect.php';
    
    $section = $_GET['section'] ?? '';
    $content = '';
    
    switch ($section) {
        case 'dashboard':
            $content = getDashboardContent();
            break;
        case 'providers':
            $content = getProvidersContent();
            break;
        case 'users':
            $content = getUsersContent();
            break;
        case 'orders':
            $content = getOrdersContent();
            break;
        case 'feedback':
            $content = getFeedbackContent();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid section']);
            return;
    }
    
    echo json_encode(['success' => true, 'content' => $content]);
}

function getDashboardContent() {
    global $conn;
    
    $stats = [
        'users' => 0,
        'providers' => 0,
        'orders' => 0,
        'feedback' => 0
    ];
    
    // Get total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['users'] = $result->fetch_assoc()['count'];
    
    // Get total providers
    $result = $conn->query("SELECT COUNT(*) as count FROM service_providers");
    $stats['providers'] = $result->fetch_assoc()['count'];
    
    // Get total orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $stats['orders'] = $result->fetch_assoc()['count'];
    
    // Get total feedback (from feedback table, not reviews)
    $result = $conn->query("SELECT COUNT(*) as count FROM feedback");
    $stats['feedback'] = $result ? $result->fetch_assoc()['count'] : 0;
    
    // Get recent activity
    $recentActivity = [];
    $result = $conn->query("
        SELECT 'order' as type, id, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    while ($row = $result->fetch_assoc()) {
        $recentActivity[] = $row;
    }
    
    ob_start();
    include 'templates/dashboard.php';
    return ob_get_clean();
}

function getProvidersContent() {
    global $conn;
    
    $providers = [];
    $result = $conn->query("
        SELECT sp.*, u.username, u.email 
        FROM service_providers sp 
        JOIN users u ON sp.user_id = u.id
    ");
    
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
    
    ob_start();
    include 'templates/providers.php';
    return ob_get_clean();
}

function getUsersContent() {
    global $conn;
    
    $users = [];
    $result = $conn->query("SELECT * FROM users");
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    ob_start();
    include 'templates/users.php';
    return ob_get_clean();
}

function getOrdersContent() {
    global $conn;
    
    $orders = [];
    $result = $conn->query("
        SELECT o.*, u.username as customer_name, sp.business_name as provider_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN service_providers sp ON o.provider_id = sp.id
        ORDER BY o.created_at DESC
    ");
    
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    ob_start();
    include 'templates/orders.php';
    return ob_get_clean();
}

function getFeedbackContent() {
    global $conn;
    
    $feedback = [];
    $result = $conn->query("
        SELECT f.*, u.username as user_name, sp.business_name as provider_name
        FROM feedback f
        JOIN users u ON f.user_id = u.id
        JOIN service_providers sp ON f.provider_id = sp.id
        ORDER BY f.created_at DESC
    ");
    
    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }
    
    ob_start();
    include 'templates/feedback.php';
    return ob_get_clean();
}

function handleGetStats() {
    global $conn;
    require_once __DIR__ . '/../db_connect.php';
    $stats = [
        'users' => 0,
        'providers' => 0,
        'orders' => 0,
        'feedback' => 0
    ];
    // Get total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['users'] = $result ? $result->fetch_assoc()['count'] : 0;
    // Get total providers
    $result = $conn->query("SELECT COUNT(*) as count FROM service_providers");
    $stats['providers'] = $result ? $result->fetch_assoc()['count'] : 0;
    // Get total orders (service_requests)
    $result = $conn->query("SELECT COUNT(*) as count FROM service_requests");
    $stats['orders'] = $result ? $result->fetch_assoc()['count'] : 0;
    // Get total feedback (from feedback table, not reviews)
    $result = $conn->query("SELECT COUNT(*) as count FROM feedback");
    $stats['feedback'] = $result ? $result->fetch_assoc()['count'] : 0;
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

function handleDeleteProvider() {
    global $conn;
    require_once __DIR__ . '/../db_connect.php';
    
    $providerId = $_GET['id'] ?? 0;
    
    if ($providerId) {
        $stmt = $conn->prepare("DELETE FROM service_providers WHERE id = ?");
        $stmt->bind_param("i", $providerId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete provider']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid provider ID']);
    }
}

function handleDeleteUser() {
    global $conn;
    require_once __DIR__ . '/../db_connect.php';
    
    $userId = $_GET['id'] ?? 0;
    
    if ($userId) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    }
}

function handleDeleteFeedback() {
    global $conn;
    require_once __DIR__ . '/../db_connect.php';
    $feedbackId = $_GET['id'] ?? 0;
    if (!$feedbackId && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['id'])) {
            $feedbackId = intval($input['id']);
        }
    }
    if ($feedbackId) {
        $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
        $stmt->bind_param("i", $feedbackId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete feedback']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
    }
}

function handleGetAdminProfile() {
    require_once __DIR__ . '/../db_connect.php';
    global $conn;
    $admin_id = $_SESSION['admin_id'];
    $result = $conn->query("SELECT id, username FROM admin WHERE id = " . intval($admin_id));
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'profile' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not fetch admin profile.']);
    }
    exit;
}

function handleUpdateAdminProfile() {
    require_once __DIR__ . '/../db_connect.php';
    global $conn;
    $admin_id = $_SESSION['admin_id'];
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$username) {
        echo json_encode(['success' => false, 'message' => 'Username is required.']);
        exit;
    }
    $set = "username = '" . $conn->real_escape_string($username) . "'";
    if ($password) {
        $set .= ", password = '" . $conn->real_escape_string($password) . "'";
    }
    $sql = "UPDATE admin SET $set WHERE id = " . intval($admin_id);
    if ($conn->query($sql)) {
        $_SESSION['admin_username'] = $username;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed.']);
    }
    exit;
}

function handleGetProviderDetails() {
    require_once __DIR__ . '/../db_connect.php';
    global $conn;
    $providerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$providerId) {
        echo json_encode(['success' => false, 'message' => 'Invalid provider ID']);
        exit;
    }
    // Provider info
    $stmt = $conn->prepare("SELECT * FROM service_providers WHERE id = ?");
    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $provider = $stmt->get_result()->fetch_assoc();
    if (!$provider) {
        echo json_encode(['success' => false, 'message' => 'Provider not found']);
        exit;
    }
    // Services
    $stmt = $conn->prepare("SELECT * FROM services WHERE provider_id = ?");
    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $services = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    // Reviews
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE provider_id = ?");
    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $reviews = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    echo json_encode([
        'success' => true,
        'provider' => $provider,
        'services' => $services,
        'reviews' => $reviews
    ]);
    exit;
}

if ($action === 'check_admin_session') {
    $logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    echo json_encode([
        'logged_in' => $logged_in,
        'username' => $logged_in ? $_SESSION['admin_username'] : null
    ]);
    exit;
}

if ($action === 'admin_logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}
<?php
// Security functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

// File upload functions
function handleFileUpload($file, $targetDir = '../assets/uploads/') {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Invalid file parameters.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return null;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    if ($file['size'] > 5000000) { // 5MB limit
        throw new RuntimeException('Exceeded filesize limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $validTypes = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($finfo->file($file['tmp_name']), $validTypes)) {
        throw new RuntimeException('Invalid file format.');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = sprintf('%s.%s', sha1_file($file['tmp_name']), $extension);
    $targetPath = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return 'assets/uploads/' . $filename;
}

// Session functions
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /templates/auth/login.php');
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Response functions
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function errorResponse($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

function successResponse($message, $data = null) {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}
?> 
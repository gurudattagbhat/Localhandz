<?php
/*
 * EMAIL CONFIGURATION REQUIRED:
 * Before using the forgot password feature, you need to configure email settings:
 * 
 * 1. Replace "your_email@gmail.com" with your actual Gmail address (line ~119)
 * 2. Replace "your_app_password" with your Gmail App Password (line ~120)
 * 3. To get Gmail App Password:
 *    - Enable 2-factor authentication on your Google account
 *    - Go to Google Account Settings > Security > App passwords
 *    - Generate an app password for "Mail"
 */
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include database connection and PHPMailer
require 'db_connect.php';
require 'vendor/autoload.php';

// Get the action from the query string
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'sendOtp':
        sendOtp();
        break;
    case 'verifyOtp':
        verifyOtp();
        break;
    case 'verifyAnswer':
        verifyAnswer();
        break;
    case 'resetPassword':
        resetPassword();
        break;
    case 'getSecurityQuestion':
        getSecurityQuestion();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// Function to get the security question based on email and role
function getSecurityQuestion() {
    global $conn;
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $role = $input['role'] ?? 'user';
    if (!$username) {
        http_response_code(400);
        echo json_encode(['error' => 'Username (email) required']);
        return;
    }
    if ($role === 'service_provider') {
        $stmt = $conn->prepare("SELECT security_question, email FROM service_providers WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT security_question, email FROM users WHERE email = ?");
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $_SESSION['otp_email'] = $row['email'];
        echo json_encode(['success' => true, 'question' => $row['security_question']]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
}

// Function to send OTP to the user's email using PHPMailer
function sendOtp() {
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);
    $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $role = $input['role'] ?? 'user';
    $_SESSION['reset_role'] = $role;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        return;
    }

    // Choose table based on role
    if ($role === 'service_provider') {
        $stmt = $conn->prepare("SELECT id FROM service_providers WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Email not found']);
        return;
    }

    // Generate a random OTP
    $otp = rand(100000, 999999);

    // Store the OTP and expiry in the session
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_expiry'] = time() + 90;

    // Send the OTP via PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Enable SMTP debugging
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->Debugoutput = function ($str, $level) {
            error_log("SMTP Debug: $str");
        };

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com'; // Replace with your email
        $mail->Password = 'your_app_password'; // Replace with your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@localhandz.com', 'Localhandz');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP for Password Reset - Localhandz';
        $mail->Body = "
            <p>Dear User,</p>
            <p>Your OTP for resetting your password on <b>Localhandz</b> is:</p>
            <h2>$otp</h2>
            <p>If you did not request this OTP, please ignore this email.</p>
            <p><b>Note:</b> This is an automated email. Please do not reply to this email.</p>
            <p>Thank you,<br>Localhandz Team</p>
        ";
        $mail->AltBody = "Dear User,\n\nYour OTP for resetting your password on Localhandz is: $otp\n\nIf you did not request this OTP, please ignore this email.\n\nNote: This is an automated email. Please do not reply to this email.\n\nThank you,\nLocalhandz Team";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send OTP. Mailer Error: ' . $mail->ErrorInfo]);
    }
}

// Function to verify the OTP
function verifyOtp() {
    $input = json_decode(file_get_contents('php://input'), true);
    $otp = $input['otp'] ?? '';

    if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_expiry'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Session expired. Please request a new OTP.']);
        return;
    }

    if (time() > $_SESSION['otp_expiry']) {
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        http_response_code(400);
        echo json_encode(['error' => 'OTP expired. Please request a new OTP.']);
        return;
    }

    if ((string)$_SESSION['otp'] === (string)$otp) {
        unset($_SESSION['otp']);
        unset($_SESSION['otp_expiry']);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid OTP']);
    }
}

// Function to verify the security question answer
function verifyAnswer() {
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);
    $answer = $input['answer'] ?? '';
    $role = $_SESSION['reset_role'] ?? 'user';
    $email = $_SESSION['otp_email'] ?? '';

    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Session expired. Please try again.']);
        return;
    }

    if ($role === 'service_provider') {
        $stmt = $conn->prepare("SELECT security_answer FROM service_providers WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT security_answer FROM users WHERE email = ?");
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (strtolower($user['security_answer']) === strtolower($answer)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Incorrect answer']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
}

// Function to reset the password
function resetPassword() {
    global $conn;

    $input = json_decode(file_get_contents('php://input'), true);
    $newPassword = $input['newPassword'] ?? '';
    $role = $_SESSION['reset_role'] ?? 'user';
    $email = $_SESSION['otp_email'] ?? '';

    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Session expired. Please try again.']);
        return;
    }

    if (strlen($newPassword) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters long.']);
        return;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    if ($role === 'service_provider') {
        $stmt = $conn->prepare("UPDATE service_providers SET password = ? WHERE email = ?");
    } else {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    }
    $stmt->bind_param('ss', $hashedPassword, $email);

    if ($stmt->execute()) {
        // Send password changed email
        sendPasswordChangedMail($email);
        echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to reset password']);
    }
}

// Function to send password changed email
function sendPasswordChangedMail($email) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com'; // Replace with your email
        $mail->Password = 'your_app_password'; // Replace with your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('no-reply@localhandz.com', 'Localhandz');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your Localhandz password has been changed';
        $mail->Body = '<p>Your password has been changed successfully. If you did not perform this action, please contact support immediately.</p>';
        $mail->AltBody = 'Your password has been changed successfully. If you did not perform this action, please contact support immediately.';
        $mail->send();
    } catch (Exception $e) {
        error_log('Password changed mail error: ' . $mail->ErrorInfo);
    }
}
?>
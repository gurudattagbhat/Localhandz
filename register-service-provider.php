<?php
/*
 * EMAIL CONFIGURATION REQUIRED:
 * Before using the service provider registration, you need to configure email settings:
 * 
 * 1. Replace "your_email@gmail.com" with your actual Gmail address (lines ~88 & ~92)
 * 2. Replace "your_app_password" with your Gmail App Password (line ~89)
 * 3. To get Gmail App Password:
 *    - Enable 2-factor authentication on your Google account
 *    - Go to Google Account Settings > Security > App passwords
 *    - Generate an app password for "Mail"
 */

require_once 'db_connect.php';
session_start();

$response = ['success' => false, 'message' => 'An unexpected error occurred'];

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    $required_fields = [
        'name', 'email', 'phone', 'password', 'security-question', 'security-answer'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Please fill in all required fields");
        }
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $security_question = trim($_POST['security-question']);
    $security_answer = trim($_POST['security-answer']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    if (strlen($password) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle profile photo upload
    if (!empty($_FILES['photo']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= 2 * 1024 * 1024) {
            $photo = 'uploads/' . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);
        } else {
            throw new Exception("Invalid photo. Only JPEG, PNG, and GIF files under 2MB are allowed.");
        }
    } else {
        $photo = 'images/default-profile.png'; // Default photo if none is uploaded
    }

    // Check for existing email or phone
    $check_sql = "SELECT id FROM service_providers WHERE email = ? OR phone = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $email, $phone);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        $response['success'] = false;
        $response['message'] = 'Account already exists with this email or phone number.';
        header('Content-Type: application/json');
        echo json_encode($response);
        $conn->close();
        exit();
    }
    $check_stmt->close();

    // Insert provider
    $insert_sql = "INSERT INTO service_providers (
        name, email, phone, photo, password, security_question, security_answer
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param(
        "sssssss",
        $name, $email, $phone, $photo, $hashed_password, $security_question, $security_answer
    );

    if ($insert_stmt->execute()) {
        // Send welcome email
        require_once __DIR__ . '/vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@gmail.com'; // Replace with your email
            $mail->Password = 'your_app_password'; // Replace with your Gmail app password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('your_email@gmail.com', 'Local Handz'); // Replace with your email
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to Local Handz!';
            $mail->Body = '<h2>Welcome, ' . htmlspecialchars($name) . '!</h2><p>Thank you for registering as a service provider on Local Handz. We are excited to have you join our platform!</p><p>You can now manage your services and bookings from your dashboard.</p><br><p>Best regards,<br>Local Handz Team</p>';
            $mail->send();
        } catch (Exception $e) {
            // Log or ignore email errors, do not block registration
        }
        $response['success'] = true;
        $response['message'] = 'Registration successful';
    } else {
        $response['message'] = 'Failed to register. Please try again.';
    }

    $insert_stmt->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Registration Error: " . $e->getMessage());
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
exit;
?>

<?php
/*
 * EMAIL CONFIGURATION REQUIRED:
 * Before using this booking service, you need to configure email settings:
 * 
 * 1. Replace "your_email@gmail.com" with your actual Gmail address (lines ~76 & ~109)
 * 2. Replace "your_app_password" with your Gmail App Password (lines ~77 & ~110)
 * 3. To get Gmail App Password:
 *    - Enable 2-factor authentication on your Google account
 *    - Go to Google Account Settings > Security > App passwords
 *    - Generate an app password for "Mail"
 * 
 * DATABASE CONFIGURATION REQUIRED:
 * Update database credentials below with your actual values
 */
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Replace with your actual DB credentials
$host = 'localhost';
$db   = 'local_handz'; // updated to your local database
$user = 'root'; // default local user
$pass = ''; // default local password (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get POST data
    $provider_id = $_POST['provider_id'] ?? null;
    $service_id = $_POST['service_id'] ?? null;
    $customer_address = $_POST['service-location'] ?? null;
    $request_date = $_POST['request_date'] ?? null;

    // Get logged-in user ID from session
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$provider_id || !$service_id || !$customer_address || !$user_id || !$request_date) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    // Fetch user info from users table
    $stmt = $pdo->prepare('SELECT name, phone, email FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $customer_name = $user['name'];
    $customer_phone = $user['phone'];
    $user_email = $user['email'];

    // Fetch provider info
    $stmt = $pdo->prepare('SELECT name, email, phone FROM service_providers WHERE id = ?');
    $stmt->execute([$provider_id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    $provider_name = $provider['name'] ?? '';
    $provider_email = $provider['email'] ?? '';
    $provider_phone = $provider['phone'] ?? '';

    // Fetch service info
    $stmt = $pdo->prepare('SELECT name, category, price FROM services WHERE id = ?');
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    $service_name = $service['name'] ?? '';
    $service_category = $service['category'] ?? '';
    $service_price = $service['price'] ?? '';

    // Insert into service_requests
    $stmt = $pdo->prepare('INSERT INTO service_requests (service_id, provider_id, customer_name, customer_phone, customer_address, request_date) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$service_id, $provider_id, $customer_name, $customer_phone, $customer_address, $request_date]);

    // Send email notifications
    require_once __DIR__ . '/vendor/autoload.php';
    $mail_errors = [];
    // Provider notification
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com'; // Replace with your email
        $mail->Password = 'your_app_password'; // Replace with your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('no-reply@localhandz.com', 'Localhandz');
        $mail->addAddress($provider_email, $provider_name);
        $mail->isHTML(true);
        $mail->Subject = 'New Booking Received on Localhandz';
        $mail->Body = "<p>Dear $provider_name,</p>"
            . "<p>You have received a new booking:</p>"
            . "<ul>"
            . "<li><b>Service:</b> $service_name ($service_category)</li>"
            . "<li><b>Date/Time:</b> $request_date</li>"
            . "<li><b>Customer Name:</b> $customer_name</li>"
            . "<li><b>Customer Phone:</b> $customer_phone</li>"
            . "<li><b>Customer Email:</b> $user_email</li>"
            . "<li><b>Service Address:</b> $customer_address</li>"
            . "<li><b>Price:</b> ₹$service_price</li>"
            . "</ul>"
            . "<p>Please log in to your dashboard to accept or manage this booking.</p>"
            . "<p>Thank you,<br>Localhandz Team</p>";
        $mail->AltBody = "New booking for $service_name ($service_category) on $request_date. Customer: $customer_name, $customer_phone, $user_email. Address: $customer_address. Price: ₹$service_price.";
        $mail->send();
    } catch (Exception $e) {
        $mail_errors[] = 'Provider mail error: ' . $e->getMessage();
    }

    // User confirmation
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com'; // Replace with your email
        $mail->Password = 'your_app_password'; // Replace with your Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('no-reply@localhandz.com', 'Localhandz');
        $mail->addAddress($user_email, $customer_name);
        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation - Localhandz';
        $mail->Body = "<p>Dear $customer_name,</p>"
            . "<p>Your booking has been placed successfully:</p>"
            . "<ul>"
            . "<li><b>Service:</b> $service_name ($service_category)</li>"
            . "<li><b>Date/Time:</b> $request_date</li>"
            . "<li><b>Provider:</b> $provider_name</li>"
            . "<li><b>Provider Phone:</b> $provider_phone</li>"
            . "<li><b>Provider Email:</b> $provider_email</li>"
            . "<li><b>Service Address:</b> $customer_address</li>"
            . "<li><b>Price:</b> ₹$service_price</li>"
            . "</ul>"
            . "<p>The provider will contact you soon. You can view your bookings in your dashboard.</p>"
            . "<p>Thank you for using Localhandz!</p>";
        $mail->AltBody = "Booking confirmed for $service_name ($service_category) on $request_date. Provider: $provider_name, $provider_phone, $provider_email. Address: $customer_address. Price: ₹$service_price.";
        $mail->send();
    } catch (Exception $e) {
        $mail_errors[] = 'User mail error: ' . $e->getMessage();
    }

    echo json_encode(['success' => true, 'mail_errors' => $mail_errors]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
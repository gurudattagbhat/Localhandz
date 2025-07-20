<?php
/*
 * EMAIL CONFIGURATION REQUIRED:
 * Before using the booking update feature, you need to configure email settings:
 * 
 * 1. Replace "your_email@gmail.com" with your actual Gmail address (line ~48)
 * 2. Replace "your_app_password" with your Gmail App Password (line ~49)
 * 3. To get Gmail App Password:
 *    - Enable 2-factor authentication on your Google account
 *    - Go to Google Account Settings > Security > App passwords
 *    - Generate an app password for "Mail"
 * 
 * DATABASE CONFIGURATION REQUIRED:
 * Update database credentials below (lines 5-8)
 */
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$db   = 'local_handz';
$user = 'root';
$pass = '';

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    // --- Provider status update (from dashboard) ---
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        // Update status
        $stmt = $pdo->prepare('UPDATE service_requests SET status = ? WHERE id = ?');
        $stmt->execute([$status, $order_id]);
        // Fetch full order details for email
        $stmt = $pdo->prepare('
            SELECT sr.id, sr.request_date, sr.customer_name, sr.customer_phone, sr.customer_address, sr.status,
                   s.name AS service_name, s.category AS service_category, s.price AS service_price,
                   sp.name AS provider_name, sp.email AS provider_email, u.email AS customer_email
            FROM service_requests sr
            JOIN services s ON sr.service_id = s.id
            JOIN service_providers sp ON sr.provider_id = sp.id
            JOIN users u ON sr.customer_name = u.name AND sr.customer_phone = u.phone
            WHERE sr.id = ?
        ');
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order && !empty($order['customer_email'])) {
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
                $mail->addAddress($order['customer_email']);
                $mail->isHTML(true);
                // Friendly status message
                $statusMsg = '';
                if ($status === 'accepted') $statusMsg = 'Your booking has been <b>accepted</b>!';
                elseif ($status === 'completed') $statusMsg = 'Your service is <b>completed</b>!';
                elseif ($status === 'cancelled') $statusMsg = 'Your booking has been <b>cancelled</b>.';
                else $statusMsg = 'Your booking status is now: <b>' . ucfirst($status) . '</b>.';
                $mail->Subject = 'Update: Your Localhandz Booking';
                $mail->Body = "<p>Dear {$order['customer_name']},</p>"
                    . "<p>$statusMsg</p>"
                    . "<h4>Order Details:</h4>"
                    . "<ul>"
                    . "<li><b>Order ID:</b> {$order['id']}</li>"
                    . "<li><b>Service:</b> {$order['service_name']} ({$order['service_category']})</li>"
                    . "<li><b>Provider:</b> {$order['provider_name']}</li>"
                    . "<li><b>Date & Time:</b> {$order['request_date']}</li>"
                    . "<li><b>Location:</b> {$order['customer_address']}</li>"
                    . "<li><b>Amount:</b> ₹{$order['service_price']}</li>"
                    . "</ul>"
                    . "<p>If you have any questions, please contact support.</p>"
                    . "<p>Thank you,<br>Localhandz Team</p>";
                $mail->AltBody = "Dear {$order['customer_name']},\n$statusMsg\nOrder ID: {$order['id']}\nService: {$order['service_name']} ({$order['service_category']})\nProvider: {$order['provider_name']}\nDate & Time: {$order['request_date']}\nLocation: {$order['customer_address']}\nAmount: ₹{$order['service_price']}\nThank you, Localhandz Team";
                $mail->send();
            } catch (Exception $e) {
                error_log('Order status mail error: ' . $mail->ErrorInfo);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
    // --- Customer address update (from customer) ---
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $address = $data['customer_address'] ?? null;
    if ($id && $address) {
        // Get user info
        $stmt = $pdo->prepare('SELECT name, phone FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }
        // Only allow update if booking belongs to user and is pending
        $stmt = $pdo->prepare('SELECT * FROM service_requests WHERE id = ? AND customer_name = ? AND customer_phone = ? AND status = "pending"');
        $stmt->execute([$id, $user['name'], $user['phone']]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$booking) {
            echo json_encode(['success' => false, 'error' => 'Booking not found or not editable']);
            exit;
        }
        $stmt = $pdo->prepare('UPDATE service_requests SET customer_address = ? WHERE id = ?');
        $stmt->execute([$address, $id]);
        echo json_encode(['success' => true]);
        exit;
    }
    // If neither case matched
    echo json_encode(['success' => false, 'error' => 'Missing fields']);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
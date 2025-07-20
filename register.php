<?php
/*
 * EMAIL CONFIGURATION REQUIRED:
 * Before using the registration feature, you need to configure email settings:
 * 
 * 1. Replace "your_email@gmail.com" with your actual Gmail address (lines ~89 & ~93)
 * 2. Replace "your_app_password" with your Gmail App Password (line ~90)
 * 3. To get Gmail App Password:
 *    - Enable 2-factor authentication on your Google account
 *    - Go to Google Account Settings > Security > App passwords
 *    - Generate an app password for "Mail"
 */

// Database connection details
$host = "localhost"; // Change if needed
$dbname = "local_handz"; // Your database name
$username = "root"; // Your DB username
$password = ""; // Your DB password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];
    $securityQuestion = $_POST["security-question"];
    $securityAnswer = trim($_POST["security-answer"]);

    // Validation
    $errors = [];

    if (empty($name)) {
        $errors[] = "Full name is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must be 10 digits.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 8 || 
        !preg_match("/[A-Z]/", $password) || 
        !preg_match("/[a-z]/", $password) || 
        !preg_match("/[0-9]/", $password) || 
        !preg_match("/[@$!%*?&]/", $password)) {
        $errors[] = "Password must be at least 8 characters, include an uppercase, lowercase, number, and special character.";
    }
    if (empty($securityQuestion)) {
        $errors[] = "Security question is required.";
    }
    if (empty($securityAnswer)) {
        $errors[] = "Security answer is required.";
    }

    // If validation fails, show errors
    if (!empty($errors)) {
        // Return errors as plain text (no HTML)
        echo implode("\n", $errors);
        exit();
    } else {
        // Check for existing email or phone
        $check_sql = "SELECT id FROM users WHERE email = ? OR phone = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $phone);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            echo "Account already exists with this email or phone number.";
            exit();
        }
        $check_stmt->close();

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $phone, $hashedPassword, $securityQuestion, $securityAnswer);

        if ($stmt->execute()) {
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
                $mail->Body = '<h2>Welcome, ' . htmlspecialchars($name) . '!</h2><p>Thank you for registering with Local Handz. We are excited to have you on board!</p><p>You can now book trusted home services easily from your dashboard.</p><br><p>Best regards,<br>Local Handz Team</p>';
                $mail->send();
            } catch (Exception $e) {
                // Log or ignore email errors, do not block registration
            }
            echo "Registration successful";
            exit();
        } else {
            echo "Error: " . $stmt->error;
            exit();
        }

        $stmt->close();
    }
}

$conn->close();
?>

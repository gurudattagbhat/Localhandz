<?php
session_start();

if (!isset($_SESSION['registration_success'])) {
    header("Location: register-service-provider.html");
    exit();
}

$email = $_SESSION['provider_email'];
unset($_SESSION['registration_success']);
unset($_SESSION['provider_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Local Handz</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h1 {
            color: #4CAF50;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registration Successful!</h1>
        <p>Thank you for registering as a service provider with Local Handz.</p>
        <p>An email has been sent to <strong><?php echo htmlspecialchars($email); ?></strong> with further instructions.</p>
        <p>Our team will review your application and get back to you shortly.</p>
        <a href="login.html" class="btn">Login to Your Account</a>
    </div>
</body>
</html>
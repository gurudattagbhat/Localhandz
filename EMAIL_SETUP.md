# Email Configuration Setup Guide

## Overview
This project uses PHPMailer to send emails for various features like user registration, password reset, and booking notifications. All email credentials have been removed for security purposes.

## Files Requiring Email Configuration

### 1. Book Service Notifications (`book_service.php`)
**Lines to update:**
- Line ~90: `$mail->Username = 'your_email@gmail.com';`
- Line ~91: `$mail->Password = 'your_app_password';`
- Line ~123: `$mail->Username = 'your_email@gmail.com';`
- Line ~124: `$mail->Password = 'your_app_password';`

### 2. User Registration (`register.php`)
**Lines to update:**
- Line ~101: `$mail->Username = 'your_email@gmail.com';`
- Line ~102: `$mail->Password = 'your_app_password';`
- Line ~105: `$mail->setFrom('your_email@gmail.com', 'Local Handz');`

### 3. Service Provider Registration (`register-service-provider.php`)
**Lines to update:**
- Line ~100: `$mail->Username = 'your_email@gmail.com';`
- Line ~101: `$mail->Password = 'your_app_password';`
- Line ~104: `$mail->setFrom('your_email@gmail.com', 'Local Handz');`

### 4. Password Reset (`forgot-password.php`)
**Lines to update:**
- Line ~130: `$mail->Username = 'your_email@gmail.com';` (OTP email)
- Line ~131: `$mail->Password = 'your_app_password';`
- Line ~275: `$mail->Username = 'your_email@gmail.com';` (Password changed confirmation)
- Line ~276: `$mail->Password = 'your_app_password';`

### 5. Booking Status Updates (`update_booking.php`) ⚠️ **NEW**
**Lines to update:**
- Line ~63: `$mail->Username = 'your_email@gmail.com';`
- Line ~64: `$mail->Password = 'your_app_password';`

### 6. Contact Information (`index.html`)
**Line to update:**
- Line ~275: Replace `your-contact@email.com` with your business email

## How to Get Gmail App Password

### Step 1: Enable 2-Factor Authentication
1. Go to [Google Account Settings](https://myaccount.google.com/)
2. Navigate to **Security** → **2-Step Verification**
3. Follow the setup process to enable 2FA

### Step 2: Generate App Password
1. In Google Account Settings, go to **Security**
2. Under "How you sign in to Google," select **App passwords**
3. Select **Mail** from the dropdown
4. Copy the generated 16-character password

### Step 3: Configure Your Files
Replace the following placeholders in your PHP files:
- `your_email@gmail.com` → Your actual Gmail address
- `your_app_password` → The 16-character app password from Step 2

## Email Features in the Project

### 1. Welcome Emails
- **User Registration**: Sent when new users register
- **Service Provider Registration**: Sent when new providers register

### 2. Booking Notifications
- **Provider Notification**: Sent to service providers when they receive a booking
- **Customer Confirmation**: Sent to customers confirming their booking
- **Status Updates**: Sent to customers when booking status changes (accepted/completed/cancelled) ⚠️ **NEW**

### 3. Password Reset
- **OTP Email**: Sends a 6-digit OTP for password reset verification
- **Password Changed**: Confirmation email after successful password reset ⚠️ **NEW**

## SMTP Configuration Details

All email configurations use the following SMTP settings:
```php
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

## Security Best Practices

### 1. Environment Variables (Recommended)
Instead of hardcoding credentials, use environment variables:

```php
$mail->Username = $_ENV['GMAIL_USERNAME'] ?? 'your_email@gmail.com';
$mail->Password = $_ENV['GMAIL_APP_PASSWORD'] ?? 'your_app_password';
```

### 2. Configuration File
Create a separate config file (add to .gitignore):

**config/email_config.php:**
```php
<?php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your_email@gmail.com',
    'smtp_password' => 'your_app_password',
    'from_email' => 'your_email@gmail.com',
    'from_name' => 'Local Handz'
];
?>
```

### 3. Error Handling
The project includes try-catch blocks for email sending to prevent registration/booking failures if email service is unavailable.

## Troubleshooting

### Common Issues:

1. **"Invalid credentials" error**
   - Verify 2FA is enabled
   - Generate a new app password
   - Ensure you're using the app password, not your regular Gmail password

2. **"Connection refused" error**
   - Check if your hosting provider blocks SMTP on port 587
   - Try port 465 with `PHPMailer::ENCRYPTION_SMTPS`

3. **Emails not being received**
   - Check spam/junk folders
   - Verify the recipient email address
   - Check Gmail's sent folder

### Testing Email Configuration

Create a simple test script:
```php
<?php
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
// Add your configuration here
$mail->addAddress('test@example.com');
$mail->Subject = 'Test Email';
$mail->Body = 'Test message';

if ($mail->send()) {
    echo "Email sent successfully!";
} else {
    echo "Error: " . $mail->ErrorInfo;
}
?>
```

## Alternative Email Services

If you prefer not to use Gmail, you can configure other SMTP providers:

### SendGrid
```php
$mail->Host = 'smtp.sendgrid.net';
$mail->Port = 587;
```

### Mailgun
```php
$mail->Host = 'smtp.mailgun.org';
$mail->Port = 587;
```

### AWS SES
```php
$mail->Host = 'email-smtp.region.amazonaws.com';
$mail->Port = 587;
```

Remember to update the username and password accordingly for each service.

# Local Handz - Configuration Setup Guide

## Overview
This project requires several configuration steps before it can be deployed. All sensitive information has been removed for security purposes.

## Required Configurations

### 1. Database Configuration ✅ **READY TO USE**
**All database connections are configured with default WAMP/XAMPP values:**
- Username: `root`
- Password: `` (empty)
- Database: `local_handz`

**No configuration needed for local development!** All database files are ready to use:
- `db_connect.php` ✅
- `config/database.php` ✅  
- `book_service.php` ✅
- `register.php` ✅
- `update_booking.php` ✅
- `service_provider_dashboard.php` ✅
- `get_provider_details.php` ✅
- `get_basic_provider_details.php` ✅
- `get_my_bookings.php` ✅
- `my_bookings_api.php` ✅
- `delete_booking.php` ✅
- `includes/db_connect.php` ✅
- `includes/db_connection.php` ✅

**For production only:** Update credentials in the above files as needed.

### 2. reCAPTCHA Configuration
The project uses Google reCAPTCHA v3 for security. See `RECAPTCHA_SETUP.md` for detailed instructions.

### 3. Email Configuration ⚠️ **IMPORTANT**
**Multiple files require email configuration.** See `EMAIL_SETUP.md` for complete instructions.

**Quick Summary:**
- `book_service.php` - Booking notifications (4 locations)
- `register.php` - User welcome emails (3 locations)  
- `register-service-provider.php` - Provider welcome emails (3 locations)
- `forgot-password.php` - Password reset OTP emails (4 locations) ⚠️ **UPDATED**
- `update_booking.php` - Booking status update emails (2 locations) ⚠️ **NEW**
- `index.html` - Contact email display (1 location)

**Required Steps:**
1. Enable Gmail 2-factor authentication
2. Generate Gmail App Password
3. Replace `your_email@gmail.com` and `your_app_password` in all files
4. Update contact email in `index.html`

### 4. Database Setup
1. Import the database schema: `config/setup.sql` or `local_handz.sql`
2. Update database connection settings
3. Test the connection

## Security Notes
- Never commit actual credentials to version control
- Use environment variables for production deployments
- Regularly rotate passwords and API keys
- Keep log files out of the web directory
- Use HTTPS in production

## File Structure
```
├── db_connect.php              # Database connection (configure)
├── config/
│   ├── database.php           # PDO database class (configure)
│   └── database.example.php   # Example configuration
├── includes/                  # Additional database connections ⚠️ **NEW**
│   ├── db_connect.php         # Alternative DB config (configure)
│   └── db_connection.php      # Legacy DB config (configure)
├── book_service.php           # Email service + DB (configure - 4 email + 2 DB locations)
├── register.php               # User registration emails + DB (configure - 3 email + 2 DB locations)
├── register-service-provider.php # Provider registration emails (configure - 3 locations)
├── forgot-password.php        # Password reset emails (configure - 4 locations) ⚠️ **UPDATED**
├── update_booking.php         # Booking status emails + DB (configure - 2 email + 2 DB locations) ⚠️ **NEW**
├── service_provider_dashboard.php # DB credentials (configure - 2 locations) ⚠️ **NEW**
├── get_provider_details.php   # Database credentials (configure - 2 locations) ⚠️ **UPDATED**
├── get_basic_provider_details.php # Database credentials (configure - 2 locations) ⚠️ **NEW**
├── get_my_bookings.php        # Database credentials (configure - 2 locations) ⚠️ **NEW**
├── my_bookings_api.php        # Database credentials (configure - 2 locations) ⚠️ **NEW**
├── delete_booking.php         # Database credentials (configure - 2 locations) ⚠️ **NEW**
├── index.html                 # Contact email (configure - 1 location)
├── login.html                 # reCAPTCHA keys needed
├── login.php                  # reCAPTCHA secret key needed
├── RECAPTCHA_SETUP.md         # reCAPTCHA setup guide
├── EMAIL_SETUP.md             # Email configuration guide
└── .gitignore                 # Excludes sensitive files
```

## Quick Start
1. Clone the repository
2. Copy `.example` files and configure them
3. Set up reCAPTCHA (see RECAPTCHA_SETUP.md)
4. Configure email settings (see EMAIL_SETUP.md) ⚠️ **CRITICAL**
5. Import database schema
6. Test the application

## Support
- For reCAPTCHA setup: `RECAPTCHA_SETUP.md`
- For email configuration: `EMAIL_SETUP.md` ⚠️ **REQUIRED FOR ALL FEATURES**

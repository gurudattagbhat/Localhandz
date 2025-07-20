# 🚀 Quick Installation Guide

## Prerequisites
- WAMP/XAMPP/LAMP server
- PHP 7.4+ with `pdo_mysql`, `openssl`, `mbstring`
- MySQL 5.7+ or MariaDB 10.2+
- Composer

## 5-Minute Setup

### 1. Get the Code
```bash
git clone <repository-url>
cd local-handz
composer install
```

### 2. Database Setup
```sql
CREATE DATABASE local_handz;
USE local_handz;
SOURCE local_handz.sql;
```

### 3. Configuration Status

#### ✅ Database - Ready to Go!
All database connections are pre-configured for local development:
- Host: `localhost`
- Username: `root`
- Password: *(empty)*
- Database: `local_handz`

#### ⚠️ Email Configuration Required
Before using booking/registration features:
1. See `EMAIL_SETUP.md` for Gmail SMTP setup
2. Update 6 files with your email credentials

#### ⚠️ reCAPTCHA Required  
For login security:
1. See `RECAPTCHA_SETUP.md` for Google reCAPTCHA setup
2. Add keys to `login.html` and `login.php`

### 4. Launch
```bash
# Start WAMP/XAMPP
# Navigate to: http://localhost/local-handz/
```

## What Works Immediately
- ✅ Database connections
- ✅ User interface and navigation
- ✅ Admin panel access
- ✅ File uploads and static content

## What Needs Configuration
- ❌ Email notifications (booking confirmations, registration emails)
- ❌ Login security (reCAPTCHA verification)

## Complete Setup Guide
For detailed instructions, see:
- `README.md` - Full documentation
- `CONFIGURATION_SETUP.md` - Complete configuration guide
- `EMAIL_SETUP.md` - Email configuration details
- `RECAPTCHA_SETUP.md` - reCAPTCHA setup instructions

---

**Ready to connect your community! 🏠✨**

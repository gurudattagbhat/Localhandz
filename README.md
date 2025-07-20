# Local Handz - Home Services Platform 🏠🔧

Local Handz is a comprehensive web application that connects customers with local service providers (plumbers, electricians, cleaners, carpenters, and more). Built with PHP and MySQL, it features modern UI/UX, secure user authentication, booking management, provider dashboards, and admin controls.

## ✨ Key Features

- **Customer Portal**: Browse services, book appointments, manage bookings
- **Provider Dashboard**: Manage services, view bookings, track analytics  
- **Admin Panel**: Comprehensive management of users, providers, and orders
- **Real-time Notifications**: Email notifications for bookings and updates
- **Secure Authentication**: Password hashing, session management, input validation
- **Mobile-Friendly**: Responsive design for all devices

---

## 🚀 Quick Start

### Prerequisites
- **WAMP/XAMPP/LAMP** server environment
- **PHP 7.4+** with extensions: `pdo_mysql`, `openssl`, `mbstring`
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Composer** (for dependency management)

### Installation

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd local-handz
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   - Create database: `local_handz`
   - Import schema: `config/setup.sql` or `local_handz.sql`
   ```sql
   CREATE DATABASE local_handz;
   USE local_handz;
   SOURCE local_handz.sql;
   ```

4. **Configure Email & reCAPTCHA** ⚠️ **Required**
   - Follow `EMAIL_SETUP.md` for email configuration
   - Follow `RECAPTCHA_SETUP.md` for Google reCAPTCHA setup
   - See `CONFIGURATION_SETUP.md` for complete setup guide

5. **Start Server**
   ```bash
   # Start WAMP/XAMPP services
   # Access: http://localhost/local-handz/
   ```

### Database Configuration ✅ Ready!
All database connections are pre-configured with default WAMP/XAMPP settings:
- **Host**: `localhost`
- **Username**: `root` 
- **Password**: *(empty)*
- **Database**: `local_handz`

---

## 📁 Project Structure

```
local_handz/
├── 📄 Core Pages
│   ├── index.html              # Landing page
│   ├── login.html/php          # User authentication  
│   ├── register.html/php       # User registration
│   ├── services.html           # Service catalog
│   ├── my_bookings.html        # Customer bookings
│   └── profile.html            # User profile
│
├── 🔧 Provider System  
│   ├── register-service-provider.html/php
│   ├── service_provider_dashboard.html/php
│   ├── provider_details.html
│   └── service_booking.html
│
├── 👑 Admin Panel
│   ├── admin/admin_login.html/php
│   ├── admin/admin_dashboard.html
│   └── admin/[management pages]
│
├── 🔌 API Endpoints
│   ├── book_service.php        # Booking processing
│   ├── get_services.php        # Service data
│   ├── get_provider_*.php      # Provider APIs
│   └── api/[additional APIs]
│
├── ⚙️ Configuration
│   ├── config/database.php     # DB settings ✅
│   ├── db_connect.php          # Main DB connection ✅
│   ├── EMAIL_SETUP.md          # Email config guide
│   ├── RECAPTCHA_SETUP.md      # reCAPTCHA guide
│   └── CONFIGURATION_SETUP.md  # Complete setup
│
├── 🎨 Assets
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript
│   ├── images/                 # Static images
│   └── uploads/                # User uploads
│
└── 📦 Dependencies
    ├── composer.json           # PHP dependencies
    ├── vendor/                 # Composer packages
    └── THIRD_PARTY_LICENSES.md # License info
```

---

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+, MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Email**: PHPMailer 6.9+ (SMTP)
- **Security**: Google reCAPTCHA v3, Password Hashing
- **Dependencies**: Composer, PHPMailer
- **Server**: Apache (WAMP/XAMPP/LAMP)

---

## 🎯 How to Use

### 👤 For Customers

1. **Registration & Login**
   - Visit `register.html` to create an account
   - Fill in: name, email, phone, password
   - Email verification and duplicate checking included
   - Login at `login.html` with your credentials

2. **Browse & Book Services**
   - Explore services on homepage or `services.html`
   - View available providers for each service
   - Click "Book Now" and fill booking details
   - Receive email confirmation and booking ID
   - Track bookings in `my_bookings.html`

3. **Manage Bookings**
   - View booking history and status
   - Update addresses for pending bookings
   - Receive email updates on status changes

### 🔧 For Service Providers

1. **Provider Registration**
   - Register at `register-service-provider.html`
   - Complete profile with services offered
   - Receive welcome email with login details

2. **Dashboard Management**
   - Access `service_provider_dashboard.html`
   - View and manage incoming bookings
   - Update booking status (accept/complete/cancel)
   - Track earnings and analytics
   - Manage service listings

### 👑 For Administrators

1. **Admin Access**
   - Login at `admin/admin_login.html`
   - Comprehensive dashboard for system management

2. **Management Features**
   - User and provider management
   - Order tracking and analytics
   - Feedback and review moderation
   - System-wide settings and reports

---

## 🔒 Security Features

- **Authentication**: Secure password hashing with PHP `password_hash()`
- **Session Management**: Proper session handling and timeout
- **Input Validation**: Client and server-side validation
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Output sanitization and escaping
- **CSRF Protection**: Form token validation
- **reCAPTCHA**: Google reCAPTCHA v3 integration
- **Email Security**: PHPMailer with SMTP authentication

---

## 📧 Configuration Required

Before deployment, configure these essential components:

### 1. Email Configuration ⚠️ **Required**
**Files needing email setup** (see `EMAIL_SETUP.md`):
- `book_service.php` - Booking notifications
- `register.php` - User welcome emails
- `register-service-provider.php` - Provider welcome emails  
- `forgot-password.php` - Password reset emails
- `update_booking.php` - Status update notifications
- `index.html` - Contact email display

### 2. reCAPTCHA Setup ⚠️ **Required**
**Files needing reCAPTCHA keys** (see `RECAPTCHA_SETUP.md`):
- `login.html` - Frontend integration
- `login.php` - Backend verification

### 3. Database ✅ **Ready**
All database connections pre-configured for local development.

---

## 🐛 Troubleshooting

### Email Issues
- **Emails not sending**: Check Gmail app password in PHP files
- **SMTP errors**: Verify Gmail 2FA is enabled and app password is correct
- **Delivery issues**: Check spam folder, verify recipient addresses

### Database Issues  
- **Connection errors**: Verify MySQL service is running
- **Import errors**: Check SQL file syntax and database permissions
- **Query errors**: Enable error reporting in PHP for debugging

### General Issues
- **File uploads**: Check `uploads/` directory permissions (755)
- **JavaScript errors**: Open browser console for debugging
- **CSS not loading**: Verify file paths and server configuration
- **Session issues**: Check PHP session configuration

---

## 📝 Development Notes

### File Organization
- **Core logic**: Root directory PHP files
- **API endpoints**: Separate API files for AJAX calls  
- **Includes**: Reusable components in `includes/`
- **Assets**: Static resources in organized subdirectories

### Database Schema
- **Users**: Customer accounts and authentication
- **Service Providers**: Provider profiles and services
- **Services**: Service catalog with categories and pricing
- **Bookings**: Service requests and booking management
- **Admin**: Administrative controls and analytics

### Security Considerations
- All user inputs are validated and sanitized
- Database queries use prepared statements
- Passwords are hashed using PHP's secure functions
- Session management follows security best practices
- File uploads are restricted and validated

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License & Credits

### Third-Party Libraries
- **PHPMailer**: LGPL 2.1 License (see `THIRD_PARTY_LICENSES.md`)
- **Font Awesome**: Free License
- **Google reCAPTCHA**: Google Terms of Service

### Project License
This project is open source. See individual file headers for specific licensing information.

---

## 📞 Support

- **Setup Issues**: Check `CONFIGURATION_SETUP.md`
- **Email Problems**: See `EMAIL_SETUP.md`  
- **reCAPTCHA Issues**: See `RECAPTCHA_SETUP.md`
- **General Help**: Open an issue in the repository

---

**Local Handz** - Connecting communities with trusted local services 🏠✨

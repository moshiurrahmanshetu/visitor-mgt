# VAMS - Visitor Access Management System

A comprehensive web-based visitor management system built with PHP, MySQL, and Bootstrap 5.

## Phase 1: Authentication System

This phase implements the foundational authentication system including:
- User registration and login
- Role-based access control (Admin, Receptionist, Security, Employee)
- Session management with security features
- Profile management (edit profile, change password, change avatar)
- Collapsible sidebar navigation
- Responsive admin panel layout

## Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: Raw PHP (no frameworks)
- **Database**: MySQL with PDO
- **Icons**: Bootstrap Icons
- **Fonts**: Inter (Google Fonts)

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache/Nginx) - XAMPP/WAMP recommended for local development
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Installation Instructions

### 1. Project Setup

1. Clone or download this project to your web server directory:
   - For XAMPP: `C:\xampp\htdocs\visitor-mgt`
   - For WAMP: `C:\wamp64\www\visitor-mgt`
   - For Linux/MAMP: `/Applications/MAMP/htdocs/visitor-mgt`

2. Ensure the following directories exist and are writable:
   - `assets/uploads/avatars`
   - `logs`

### 2. Database Configuration

1. Open phpMyAdmin (http://localhost/phpmyadmin) or your MySQL client

2. Create a new database named `vams`:
   ```sql
   CREATE DATABASE vams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Import the SQL file:
   - Navigate to the `database` folder
   - Open `01_users_roles.sql`
   - Import it into the `vams` database using phpMyAdmin's Import feature
   - Or run via MySQL CLI: `mysql -u root -p vams < database/01_users_roles.sql`

4. Verify the tables were created:
   - `roles` table with 4 seed roles
   - `users` table with 1 default admin user

### 3. Configure Database Connection

Edit the file `config/db.php` and update the database credentials if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vams');
define('DB_USER', 'root');      // Your MySQL username
define('DB_PASS', '');          // Your MySQL password
```

**Note**: Default XAMPP/WAMP credentials are usually `root` with empty password.

### 4. Configure Application Settings (Optional)

Edit `config/constants.php` to customize application settings:

```php
define('APP_NAME', 'VAMS - Visitor Access Management System');
define('SESSION_LIFETIME', 3600);  // Session timeout in seconds
define('MAX_FILE_SIZE', 2 * 1024 * 1024);  // Max upload size (2MB)
```

### 5. Set Directory Permissions

Ensure the following directories have write permissions:

- `assets/uploads/avatars` - for user avatar uploads
- `logs` - for error logs

**For Windows**: These should already be writable. If not, right-click the folder → Properties → Security → Edit permissions.

**For Linux/Mac**:
```bash
chmod 755 assets/uploads/avatars
chmod 755 logs
```

### 6. Run the Application

1. Start your web server (Apache) and MySQL from XAMPP/WAMP control panel

2. Open your browser and navigate to:
   ```
   http://localhost/visitor-mgt
   ```

3. You will be redirected to the login page

## Default Login Credentials

```
Email: admin@vams.com
Password: Admin@123
```

**Important**: Change the default admin password immediately after first login!

## Project Structure

```
visitor-mgt/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   ├── js/
│   │   └── sidebar.js         # Sidebar toggle functionality
│   ├── images/                # Static images
│   └── uploads/
│       └── avatars/           # User avatar uploads
├── config/
│   ├── constants.php          # Application constants
│   └── db.php                 # Database connection & helpers
├── includes/
│   ├── auth_check.php         # Session/role guard
│   ├── header.php              # HTML head & opening body
│   ├── footer.php              # Closing body & scripts
│   ├── navbar.php              # Top navigation bar
│   └── sidebar.php             # Collapsible sidebar
├── modules/
│   ├── auth/
│   │   ├── login.php           # Login page
│   │   ├── logout.php          # Logout handler
│   │   ├── register.php        # User registration (admin-only scaffold)
│   │   └── forgot_password.php # Password reset (scaffold)
│   └── profile/
│       ├── profile.php         # Profile view/edit
│       ├── change_password.php # Change password
│       └── change_avatar.php   # Upload avatar
├── dashboard/
│   └── index.php               # Dashboard shell
├── database/
│   └── 01_users_roles.sql      # Database schema
├── logs/
│   └── error.log               # Error logs (auto-created)
├── index.php                   # Root redirect
└── README.md                   # This file
```

## Features Implemented (Phase 1)

### Authentication
- Secure login with email and password
- Password hashing using bcrypt (password_hash/password_verify)
- Session management with session_regenerate_id()
- "Remember Me" functionality
- Automatic logout on session expiration
- CSRF token protection on all forms

### Authorization
- Role-based access control (4 roles: Admin, Receptionist, Security, Employee)
- Session guard for protected pages
- Role-check helper functions (requireRole, hasRole, hasAnyRole)

### User Management
- Profile viewing and editing
- Email and phone number updates
- Password change with validation
- Avatar upload with image validation
- File type and size restrictions

### UI/UX
- Clean, professional Bootstrap 5 interface
- Collapsible sidebar with smooth transitions
- Responsive design (mobile-friendly)
- Flash messages for success/error feedback
- Form validation (client-side and server-side)
- Consistent color theme with CSS variables

## Security Features

- **SQL Injection Prevention**: All database queries use PDO with prepared statements
- **XSS Prevention**: Output sanitization with htmlspecialchars()
- **CSRF Protection**: Token-based validation on all forms
- **Session Security**: session_regenerate_id() on login, secure session configuration
- **Password Security**: bcrypt hashing, never stored in plain text
- **File Upload Security**: Type validation, size limits, unique filenames
- **Error Handling**: Generic error messages to users, detailed logging to files

## Database Schema

### Roles Table
- `id` - Primary key
- `role_name` - Unique role name (Admin, Receptionist, Security, Employee)
- `description` - Role description
- `created_at` - Timestamp

### Users Table
- `id` - Primary key
- `employee_id` - Employee code
- `full_name` - User's full name
- `email` - Unique email address
- `phone` - Phone number
- `password_hash` - Bcrypt hashed password
- `role_id` - Foreign key to roles table
- `avatar` - Avatar filename
- `status` - Account status (active/inactive)
- `created_at` - Account creation timestamp
- `updated_at` - Last update timestamp

## Future Phases

The following features will be implemented in future phases:

- **Phase 2**: Visitor Management (registration, profiles, history)
- **Phase 3**: Visit Management (scheduling, check-in/check-out)
- **Phase 4**: Approval System (workflow for visit approvals)
- **Phase 5**: Reports and Analytics (visitor statistics, export)
- **Phase 6**: Advanced Features (notifications, badges, QR codes)

## Troubleshooting

### Database Connection Failed
- Verify MySQL is running in XAMPP/WAMP
- Check credentials in `config/db.php`
- Ensure database `vams` exists
- Check MySQL error logs

### Session Not Working
- Verify PHP session directory is writable
- Check session settings in `php.ini`
- Clear browser cookies

### File Upload Not Working
- Ensure `assets/uploads/avatars` directory exists and is writable
- Check PHP upload_max_filesize and post_max_size in `php.ini`
- Verify file size doesn't exceed MAX_FILE_SIZE in constants.php

### CSS/JS Not Loading
- Verify BASE_URL in constants.php is correct
- Check file paths in header.php and_footer.php
- Clear browser cache

### Permission Denied Errors
- On Windows: Run editor as administrator
- On Linux/Mac: Use chmod to set proper permissions
- Check file/folder ownership

## Development Notes

- All PHP code follows PSR-12 coding standards where applicable
- Database queries use PDO with prepared statements exclusively
- Session management follows PHP security best practices
- Error logging is implemented for debugging (check `logs/error.log`)
- DEBUG_MODE in constants.php can be set to false for production

## Support

For issues or questions related to this phase, please refer to:
- Database schema: `database/01_users_roles.sql`
- Configuration: `config/constants.php` and `config/db.php`
- Error logs: `logs/error.log`

## License

This project is proprietary and confidential. All rights reserved.

---

**Version**: 1.0.0 (Phase 1)
**Last Updated**: August 2026

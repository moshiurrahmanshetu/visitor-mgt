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

## Phase 2: Visitor Management

This phase implements visitor registration and management including:
- Add new visitors with photo upload
- Visitor list with search, filter, and pagination
- View visitor details
- Edit visitor information
- Soft delete and restore visitors
- Duplicate phone number detection via AJAX
- Role-based access control (Admin/Receptionist can add/edit/delete, Security/Employee can view only)

## Phase 3: Visit Management and Approval Workflow

This phase implements visit scheduling and approval including:
- Create visits with visitor search (AJAX-based)
- Visit list with search, filter, pagination, and role-based visibility
- View visit details with visitor and host information
- Edit visit details (Pending status only)
- Cancel visits (any status except Checked Out)
- Approval workflow (Approve/Reject with reason)
- Role-based access control:
  - Admin: Full access to all operations
  - Receptionist: Create, view, edit (Pending), cancel visits
  - Security: Read-only access to all visits
  - Employee (Host): View own visits, approve/reject own pending visits
- Dashboard widgets per role (Pending Approvals, Today's Visits, Approved Visits Today)

## Phase 4: Check-In / Check-Out and Visitor Pass Generation

This phase implements visitor arrival/departure tracking and pass issuance including:
- Check-In page with AJAX search for approved visits (today only)
- Check-Out page with AJAX search for checked-in visitors
- Currently Inside list with live duration display (auto-refresh every 60 seconds)
- Visitor Pass View/Print page with professional badge design
- Pass number generation (PASS-XXXXXX format, post-insert using LAST_INSERT_ID())
- Duration badge color coding (green < 1h, yellow 1-3h, red > 3h)
- Print CSS for pass card (hides sidebar/navbar, shows only pass when printing)
- Visit View page updated to show pass info when applicable
- Role-based access control:
  - Admin: Full check-in/check-out access
  - Security: Full check-in/check-out access (primary role)
  - Receptionist: Full check-in/check-out access
  - Employee: Read-only access to Currently Inside (filtered to own visits)
- Database updates:
  - New visitor_passes table with FK to visits and users
  - visits.is_currently_inside flag for fast "who is inside" queries

## Phase 5: Visitor Logs & Reports

This phase implements comprehensive visitor reporting and analytics including:
- Report Dashboard with role-based quick-access cards (Today's Visitors, Currently Inside, Completed Today, Pending, Rejected)
- Visitor History search/filter with multiple criteria (name, phone, code, host, department, date range, status)
- Server-side pagination (20 records per page) for all report tables
- Date-Wise Report with summary counts (Total, Approved, Rejected, Checked In, Checked Out, Cancelled, Pending)
- CSV Export for Visitor History and Date-Wise reports (streamed directly to browser, no server storage)
- Role-based access control:
  - Admin: Full access to all reports and history
  - Receptionist: Full access to all reports and history
  - Security: Report Dashboard only (operational reports)
  - Employee: Redirected to own visit list (no report dashboard access)
- All queries use prepared statements with dynamic WHERE clause building
- CSRF token validation on export forms

## Phase 6: User & Role Management

This phase implements Admin-only user management including:
- User List with search/filter/pagination (15 records per page)
- Active Users and Deleted Users tabs (soft delete with restore option)
- Add User with password validation (min 8 chars, 1 letter + 1 number)
- Edit User (no password field - password changes via Reset Password)
- View User Details with activity summary (Receptionist: visitors added/visits created; Employee: visits hosted/approved/rejected)
- Reset Password (Admin sets new password directly, no email flow)
- Activate/Deactivate User (toggles status, self-protection enforced)
- Soft Delete/Restore User (is_deleted flag, self-protection enforced)
- Self-protection rules: Admin cannot deactivate, delete, or demote own account
- Login security check: status='active' AND is_deleted=0 required for login
- Role-based access control: Admin only (all other roles redirected with error)
- Database update: is_deleted column added to users table with index
- Password show/hide toggle on Add User and Reset Password forms
- Avatar upload with validation (JPG/PNG, max 2MB, unique filename)
- Bug fix: Role assignment now works correctly with hidden input fields for self-protection

## Phase 7: Site Settings

This phase implements system-wide configuration settings including:
- Site Settings page (Admin only) with key-value storage design
- App Name and App Logo (JPG/PNG, max 1MB, stored in /assets/uploads/settings/)
- Company Information (Name, Address, Phone, Email)
- System Configuration (Pass Validity Hours, Items Per Page)
- Settings helper function `get_setting($key)` for retrieving values
- Dynamic app name and logo in header and sidebar (reflects saved settings)
- Settings stored in site_settings table with key-value design for easy future expansion
- Role-based access control: Admin only (all other roles redirected with error)
- Logo upload with validation and old file deletion pattern
- CSRF token validation on settings form
- Server-side validation for email format and numeric ranges

## Phase 8: Dynamic Role & Permission Management

This phase implements a fully dynamic role and permission system including:
- Permissions table with all system permissions (fixed reference list, not user-editable)
- Role_permissions table for assigning permissions to roles (key-value design)
- Role Management module (Admin only via `roles.manage` permission):
  - Role List with user count and permission count
  - Add Role (redirects to Edit Permissions after creation)
  - Edit Role Info (role name and description)
  - Edit Permissions (checkboxes grouped by module, transaction-based atomic updates)
  - Delete Role (hard delete, only if zero users assigned, CASCADE cleans up role_permissions)
- Permission-based access control system:
  - `has_permission($permission_key)` function for checking permissions
  - `require_permission($permission_key)` for gating access
  - Permissions loaded into session at login for performance
  - Permission changes apply on next login (not real-time session refresh)
- Migration from hardcoded role checks to dynamic permission checks:
  - All existing pages migrated from `check_role(['Admin', ...])` to `has_permission('module.action')`
  - Sidebar dynamically shows/hides menu items based on permissions
  - Existing 4 roles seeded with permissions matching their original behavior
- Database table: permissions (id, permission_key, module_name, description)
- Database table: role_permissions (id, role_id, permission_id, UNIQUE on role_id+permission_id)
- CSRF token validation on all role management forms
- Permission keys follow pattern: `module.action` (e.g., `visitors.add`, `visits.approve_reject`)
- Module groups: Visitors, Visits, CheckInOut, Reports, Users, Settings, Roles

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
   - `assets/uploads/visitors`
   - `logs`

### 2. Database Configuration

1. Open phpMyAdmin (http://localhost/phpmyadmin) or your MySQL client

2. Create a new database named `vams`:
   ```sql
   CREATE DATABASE vams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Import the SQL files (in order):
   - Navigate to the `database` folder
   - Import `01_users_roles.sql` first (creates roles and users tables)
   - Then import `02_visitors.sql` (creates visitors table)
   - Then import `03_visits.sql` (creates visits table)
   - Then import `04_checkinout.sql` (creates visitor_passes table and adds is_currently_inside to visits)
   - Then import `06_users_alter.sql` (adds is_deleted column to users table)
   - Then import `07_site_settings.sql` (creates site_settings table with seed data)
   - Then import `08_permissions.sql` (creates permissions and role_permissions tables with seed data)
   - Use phpMyAdmin's Import feature or MySQL CLI:
     ```bash
     mysql -u root -p vams < database/01_users_roles.sql
     mysql -u root -p vams < database/02_visitors.sql
     mysql -u root -p vams < database/03_visits.sql
     mysql -u root -p vams < database/04_checkinout.sql
     mysql -u root -p vams < database/06_users_alter.sql
     mysql -u root -p vams < database/07_site_settings.sql
     mysql -u root -p vams < database/08_permissions.sql
     ```

4. Verify the tables were created:
   - `roles` table with 4 seed roles
   - `users` table with 1 default admin user (with is_deleted column)
   - `visitors` table (empty, ready for visitor records)
   - `visits` table (empty, ready for visit records, with is_currently_inside column)
   - `visitor_passes` table (empty, ready for pass records)
   - `site_settings` table with seed configuration data
   - `permissions` table with all system permissions
   - `role_permissions` table with seeded role-permission mappings

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
- `assets/uploads/visitors` - for visitor photo uploads
- `logs` - for error logs

**For Windows**: These should already be writable. If not, right-click the folder → Properties → Security → Edit permissions.

**For Linux/Mac**:
```bash
chmod 755 assets/uploads/avatars
chmod 755 assets/uploads/visitors
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
│       ├── avatars/           # User avatar uploads
│       └── settings/          # Site settings logo uploads
├── config/
│   ├── constants.php          # Application constants
│   └── db.php                 # Database connection and helpers
├── includes/
│   ├── auth_check.php         # Session/role guard
│   ├── header.php              # HTML head & opening body
│   ├── footer.php              # Closing body & scripts
│   ├── navbar.php              # Top navigation bar
│   ├── sidebar.php             # Collapsible sidebar
│   ├── settings_helper.php    # Settings helper functions
│   └── permission_check.php   # Permission check functions
├── modules/
│   ├── auth/
│   │   ├── login.php           # Login page
│   │   ├── logout.php          # Logout handler
│   │   ├── register.php        # User registration (admin-only scaffold)
│   │   └── forgot_password.php # Password reset (scaffold)
│   ├── profile/
│   │   ├── profile.php         # Profile view/edit
│   │   ├── change_password.php # Change password
│   │   └── change_avatar.php   # Upload avatar
│   └── visitors/
│       ├── add.php             # Add new visitor
│       ├── list.php            # Visitor list with search/filter/pagination
│       ├── view.php            # View visitor details
│       ├── edit.php            # Edit visitor
│       ├── delete.php          # Soft delete visitor
│       ├── restore.php         # Restore deleted visitor (Admin only)
│       └── check_phone.php     # AJAX endpoint for duplicate phone check
│   └── visits/
│       ├── add.php             # Create visit with visitor search
│       ├── list.php            # Visit list with search/filter/pagination
│       ├── view.php            # View visit details
│       ├── edit.php            # Edit visit (Pending only)
│       ├── cancel.php          # Cancel visit
│       ├── approve.php         # Approve visit
│       ├── reject.php          # Reject visit with reason
│       └── search_visitor.php  # AJAX endpoint for visitor search
│   └── checkinout/
│       ├── checkin.php         # Check-In visitor
│       ├── checkout.php        # Check-Out visitor
│       ├── currently_inside.php # Currently Inside list
│       ├── pass.php            # Visitor Pass View/Print
│       └── search_visit.php    # AJAX endpoint for visit search
│   └── reports/
│       ├── index.php           # Report Dashboard
│       ├── visitor_history.php # Visitor History search/filter
│       ├── date_wise.php       # Date-Wise Report
│       └── export_csv.php      # CSV Export handler
│   └── users/
│       ├── add.php             # Add User
│       ├── list.php            # User List with tabs
│       ├── view.php            # View User Details
│       ├── edit.php            # Edit User
│       ├── reset_password.php  # Reset Password
│       ├── toggle_status.php   # Activate/Deactivate
│       ├── delete.php          # Soft Delete
│       └── restore.php         # Restore User
│   └── settings/
│       └── index.php           # Site settings page
│   └── roles/
│       ├── list.php            # Role list
│       ├── add.php             # Add role
│       ├── edit.php            # Edit role info
│       ├── permissions.php     # Edit role permissions
│       └── delete.php          # Delete role
├── dashboard/
│   └── index.php               # Dashboard shell
├── database/
│   ├── 01_users_roles.sql      # Users and roles schema
│   ├── 02_visitors.sql         # Visitors schema
│   ├── 03_visits.sql          # Visits schema
│   ├── 04_checkinout.sql      # Visitor passes schema and visits table update
│   ├── 06_users_alter.sql     # User management alter (is_deleted column)
│   ├── 07_site_settings.sql   # Site settings table
│   └── 08_permissions.sql     # Permissions and role_permissions tables
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

## Features Implemented (Phase 2)

### Visitor Management
- Add new visitors with comprehensive information
- Auto-generated visitor codes (VIS-XXXXXX format)
- Photo upload with validation (JPG/PNG, max 2MB)
- Visitor list with pagination (15 records per page)
- Search by name, phone, or visitor code
- Filter by company, ID type, and date range
- View detailed visitor information
- Edit visitor details with photo replacement
- Soft delete visitors (no hard deletion)
- Restore deleted visitors (Admin only)
- AJAX duplicate phone number detection
- Role-based access control:
  - Admin/Receptionist: Full access (add, edit, delete, restore)
  - Security/Employee: Read-only access (view and search only)

## Features Implemented (Phase 3)

### Visit Management
- Create visits with AJAX-based visitor search
- Auto-generated visit codes (VST-XXXXXX format)
- Visit list with pagination (15 records per page)
- Search by visit code or visitor name
- Filter by status, date range, and host
- View detailed visit information with visitor and host details
- Edit visit details (Pending status only)
- Cancel visits (any status except Checked Out)
- Approval workflow with modal confirmations
- Reject visits with required reason (minimum 10 characters)
- Role-based access control:
  - Admin: Full access to all operations
  - Receptionist: Create, view, edit (Pending), cancel visits
  - Security: Read-only access to all visits
  - Employee (Host): View own visits, approve/reject own pending visits
- Dashboard widgets per role:
  - Employee: Pending Approvals count
  - Admin/Receptionist: Today's Visits and Pending Approvals (All)
  - Security: Approved Visits Today

## Features Implemented (Phase 4)

### Check-In / Check-Out
- Check-In page with AJAX search for approved visits (today only)
- Check-Out page with AJAX search for checked-in visitors
- Currently Inside list with live duration display
- Auto-refresh duration every 60 seconds using vanilla JS setInterval
- Duration badge color coding (green < 1h, yellow 1-3h, red > 3h)
- Visitor Pass View/Print page with professional badge design
- Pass number generation (PASS-XXXXXX format, post-insert using LAST_INSERT_ID())
- Print CSS for pass card (hides sidebar/navbar, shows only pass when printing)
- Visit View page shows pass info when visit is Checked In or Checked Out
- Role-based access control:
  - Admin: Full check-in/check-out access
  - Security: Full check-in/check-out access (primary role)
  - Receptionist: Full check-in/check-out access
  - Employee: Read-only access to Currently Inside (filtered to own visits)

## Features Implemented (Phase 5)

### Visitor Logs & Reports
- Report Dashboard with role-based quick-access cards
- Today's Visitors count (links to filtered history for Admin/Receptionist)
- Currently Inside count (links to Phase 4's currently_inside.php)
- Completed Today count (links to filtered history)
- Pending Visits count (links to Phase 3's visit list)
- Rejected Visits count (Admin/Receptionist only)
- Visitor History search with multiple filters (name, phone, code, host, department, date range, status)
- Server-side pagination (20 records per page)
- Date-Wise Report with summary counts by status
- CSV Export for Visitor History and Date-Wise reports
- Role-based access control:
  - Admin: Full access to all reports and history
  - Receptionist: Full access to all reports and history
  - Security: Report Dashboard only (operational reports)
  - Employee: Redirected to own visit list (no report dashboard)
- All queries use prepared statements with dynamic WHERE clause building
- CSRF token validation on export forms

## Features Implemented (Phase 6)

### User & Role Management
- User List with search/filter/pagination (15 records per page)
- Active Users and Deleted Users tabs (soft delete with restore option)
- Add User with password validation (min 8 chars, 1 letter + 1 number)
- Edit User (no password field - password changes via Reset Password)
- View User Details with activity summary (Receptionist: visitors added/visits created; Employee: visits hosted/approved/rejected)
- Reset Password (Admin sets new password directly, no email flow)
- Activate/Deactivate User (toggles status, self-protection enforced)
- Soft Delete/Restore User (is_deleted flag, self-protection enforced)
- Self-protection rules: Admin cannot deactivate, delete, or demote own account
- Login security check: status='active' AND is_deleted=0 required for login
- Role-based access control: Admin only (all other roles redirected with error)
- Password show/hide toggle on Add User and Reset Password forms
- Avatar upload with validation (JPG/PNG, max 2MB, unique filename)
- Role badge colors: Admin=red, Receptionist=blue, Security=orange, Employee=green
- Status badge colors: Active=green, Inactive=gray

## Features Implemented (Phase 7)

### Site Settings
- Site Settings page (Admin only) with key-value storage design
- App Name and App Logo (JPG/PNG, max 1MB, stored in /assets/uploads/settings/)
- Company Information (Name, Address, Phone, Email)
- System Configuration (Pass Validity Hours: 1-72, Items Per Page: 5-100)
- Settings helper function `get_setting($key)` for retrieving values with fallback
- Dynamic app name and logo in header and sidebar (reflects saved settings immediately)
- Settings stored in site_settings table with key-value design for easy future expansion
- Role-based access control: Admin only (all other roles redirected with error)
- Logo upload with validation and old file deletion pattern
- CSRF token validation on settings form
- Server-side validation for email format and numeric ranges

### UI/UX
- Clean, professional Bootstrap 5 interface
- Collapsible sidebar with smooth transitions
- Responsive design (mobile-friendly)
- Flash messages for success/error feedback
- Form validation (client-side and server-side)
- Consistent color theme with CSS variables

## Adding New Permissions

To add a new permission for a future module:

1. **Add to permissions table**: Insert a new row in the `permissions` table with:
   - `permission_key`: Follow pattern `module.action` (e.g., `newmodule.view`)
   - `module_name`: The module name for grouping in UI (e.g., 'NewModule')
   - `description`: Human-readable description

2. **Assign to roles**: Use the Role Management module to assign the new permission to appropriate roles via the Edit Permissions page.

3. **Use in code**: Call `require_permission('newmodule.view')` or `has_permission('newmodule.view')` in your PHP files to gate access.

4. **Update sidebar**: Add menu items in `/includes/sidebar.php` wrapped in `<?php if (has_permission('newmodule.view')): ?>` blocks.

**Note**: The `permissions` table is a fixed reference list - do not allow users to add/delete permissions directly. Only role-permission assignments are user-editable via the Role Management module.

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

### Visitors Table
- `id` - Primary key
- `visitor_code` - Unique visitor code (auto-generated: VIS-XXXXXX)
- `full_name` - Visitor's full name
- `phone` - Phone number (indexed for search)
- `email` - Email address (indexed for search)
- `company_name` - Company/organization name
- `address` - Physical address
- `photo` - Photo filename
- `id_type` - ID type (NID, Passport, Driving License, Other)
- `id_number` - ID number
- `emergency_contact` - Emergency contact phone
- `is_deleted` - Soft delete flag (0=active, 1=deleted)
- `created_by` - Foreign key to users table (creator)
- `created_at` - Record creation timestamp
- `updated_at` - Last update timestamp

### Visits Table
- `id` - Primary key
- `visit_code` - Unique visit code (auto-generated: VST-XXXXXX)
- `visitor_id` - Foreign key to visitors table (CASCADE on delete)
- `host_id` - Foreign key to users table (RESTRICT on delete)
- `department` - Department name
- `visit_date` - Visit date (indexed for search)
- `expected_time` - Expected arrival time
- `purpose` - Purpose of visit
- `number_of_visitors` - Number of visitors (default: 1)
- `notes` - Additional notes
- `status` - Visit status (Pending, Approved, Rejected, Checked In, Checked Out, Cancelled)
- `is_currently_inside` - Flag indicating if visitor is currently inside (0/1)
- `rejection_reason` - Reason for rejection
- `approved_by` - Foreign key to users table (approver)
- `approved_at` - Approval timestamp
- `created_by` - Foreign key to users table (creator)
- `created_at` - Record creation timestamp
- `updated_at` - Last update timestamp

### Visitor Passes Table
- `id` - Primary key
- `pass_number` - Unique pass number (auto-generated: PASS-XXXXXX)
- `visit_id` - Foreign key to visits table (CASCADE on delete)
- `check_in_time` - Check-in timestamp
- `check_out_time` - Check-out timestamp (NULL if still inside)
- `checked_in_by` - Foreign key to users table (user who checked in)
- `checked_out_by` - Foreign key to users table (user who checked out)
- `created_at` - Record creation timestamp

## Future Phases

The following features will be implemented in future phases:

- **Phase 7**: Advanced Features (notifications, badges, QR codes)

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
- Ensure `assets/uploads/avatars` and `assets/uploads/visitors` directories exist and are writable
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
- Database schema: `database/01_users_roles.sql`, `database/02_visitors.sql`, `database/03_visits.sql`, `database/04_checkinout.sql`, `database/06_users_alter.sql`, `database/07_site_settings.sql`, and `database/08_permissions.sql`
- Configuration: `config/constants.php` and `config/db.php`
- Error logs: `logs/error.log`

## License

This project is proprietary and confidential. All rights reserved.

---

**Version**: 8.0.0 (Phase 8)
**Last Updated**: August 2026

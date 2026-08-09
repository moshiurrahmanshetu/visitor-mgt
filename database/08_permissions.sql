-- Visitor Access Management System (VAMS)
-- Dynamic Role & Permission Management
-- This file creates the permissions and role_permissions tables

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(100) NOT NULL UNIQUE,
    module_name VARCHAR(50) NOT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed permissions table with all system permissions
INSERT INTO permissions (permission_key, module_name, description) VALUES
-- Visitors module
('visitors.view', 'Visitors', 'View visitor list and details'),
('visitors.add', 'Visitors', 'Add new visitors'),
('visitors.edit', 'Visitors', 'Edit visitor information'),
('visitors.delete', 'Visitors', 'Delete visitors (soft delete)'),
-- Visits module
('visits.view', 'Visits', 'View visit list and details'),
('visits.add', 'Visits', 'Create new visit requests'),
('visits.edit', 'Visits', 'Edit visit information'),
('visits.cancel', 'Visits', 'Cancel visit requests'),
('visits.approve_reject', 'Visits', 'Approve or reject visit requests'),
-- CheckInOut module
('checkinout.checkin', 'CheckInOut', 'Check in visitors'),
('checkinout.checkout', 'CheckInOut', 'Check out visitors'),
('checkinout.view', 'CheckInOut', 'View check-in/out records and currently inside list'),
-- Reports module
('reports.view_own', 'Reports', 'View own visit reports (Employee only)'),
('reports.view_all', 'Reports', 'View all visitor reports and analytics'),
('reports.export', 'Reports', 'Export reports to CSV'),
-- Users module
('users.view', 'Users', 'View user list and details'),
('users.add', 'Users', 'Add new users'),
('users.edit', 'Users', 'Edit user information'),
('users.delete', 'Users', 'Delete users (soft delete)'),
('users.reset_password', 'Users', 'Reset user passwords'),
-- Settings module
('settings.manage', 'Settings', 'Manage site-wide settings'),
-- Roles module
('roles.manage', 'Roles', 'Manage roles and permissions');

-- Seed role_permissions to replicate current hardcoded behavior

-- Admin role: ALL permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Receptionist role: visitors.view/add/edit/delete, visits.view/add/edit/cancel, checkinout.checkin/checkout/view, reports.view_all/export
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_key IN (
    'visitors.view', 'visitors.add', 'visitors.edit', 'visitors.delete',
    'visits.view', 'visits.add', 'visits.edit', 'visits.cancel',
    'checkinout.checkin', 'checkinout.checkout', 'checkinout.view',
    'reports.view_all', 'reports.export'
);

-- Security role: visitors.view, visits.view, checkinout.checkin/checkout/view, reports.view_all
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key IN (
    'visitors.view',
    'visits.view',
    'checkinout.checkin', 'checkinout.checkout', 'checkinout.view',
    'reports.view_all'
);

-- Employee role: visits.view, visits.approve_reject, checkinout.view, reports.view_own
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_key IN (
    'visits.view',
    'visits.approve_reject',
    'checkinout.view',
    'reports.view_own'
);

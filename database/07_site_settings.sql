-- Visitor Access Management System (VAMS)
-- Site Settings Module
-- This file creates the site_settings table for system-wide configuration

CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed data
INSERT INTO site_settings (setting_key, setting_value) VALUES
('app_name', 'Visitor Access Management System'),
('app_logo', NULL),
('company_name', NULL),
('company_address', NULL),
('company_phone', NULL),
('company_email', NULL),
('pass_validity_hours', '12'),
('items_per_page', '15');

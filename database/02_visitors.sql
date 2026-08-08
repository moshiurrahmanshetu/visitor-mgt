-- Visitor Access Management System (VAMS)
-- Phase 2: Visitor Management Database Schema
-- This file creates the visitors table

-- Create visitors table
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NULL,
    company_name VARCHAR(150) NULL,
    address TEXT NULL,
    photo VARCHAR(255) NULL DEFAULT NULL,
    id_type ENUM('NID', 'Passport', 'Driving License', 'Other') NULL,
    id_number VARCHAR(50) NULL,
    emergency_contact VARCHAR(20) NULL,
    is_deleted TINYINT(1) DEFAULT 0,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_visitor_code (visitor_code),
    INDEX idx_company_name (company_name),
    INDEX idx_is_deleted (is_deleted),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Insert dummy visitors for testing (remove in production)
-- INSERT INTO visitors (full_name, phone, email, company_name, address, id_type, id_number, emergency_contact, created_by) VALUES
-- ('John Doe', '+1234567890', 'john@example.com', 'Acme Corp', '123 Main St', 'NID', '1234567890123', '+0987654321', 1),
-- ('Jane Smith', '+2345678901', 'jane@example.com', 'Tech Solutions', '456 Oak Ave', 'Passport', 'AB1234567', '+3456789012', 1),
-- ('Bob Johnson', '+3456789012', NULL, 'Global Industries', '789 Pine Rd', 'Driving License', 'DL9876543', '+4567890123', 1);

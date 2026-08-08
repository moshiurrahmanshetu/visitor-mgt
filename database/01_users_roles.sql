-- Visitor Access Management System (VAMS)
-- Phase 1: Users and Roles Database Schema
-- This file creates the roles and users tables with seed data

-- Create database if not exists (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS vams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE vams;

-- Drop existing tables if they exist (for clean re-import)
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- Create roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    avatar VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_email (email),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed data for roles
INSERT INTO roles (role_name, description) VALUES
('Admin', 'Full system access with all privileges'),
('Receptionist', 'Manages visitor check-in/out and basic visitor information'),
('Security', 'Approves visitor requests and manages security-related tasks'),
('Employee', 'Basic access for employees to view their own visitor history');

-- Insert default admin user
-- Password: Admin@123 (bcrypt hash)
INSERT INTO users (employee_id, full_name, email, phone, password_hash, role_id, avatar, status) VALUES
('ADM001', 'System Administrator', 'admin@vams.com', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NULL, 'active');

-- Note: The bcrypt hash above is for "Admin@123"
-- To generate new hashes, use: password_hash('your_password', PASSWORD_DEFAULT)

-- Visitor Access Management System (VAMS)
-- Phase 4: Check-In / Check-Out and Visitor Pass Generation
-- This file creates the visitor_passes table and adds is_currently_inside column to visits

-- Add is_currently_inside column to visits table
ALTER TABLE visits ADD COLUMN is_currently_inside TINYINT(1) DEFAULT 0 AFTER status;

-- Create visitor_passes table
CREATE TABLE visitor_passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pass_number VARCHAR(20) UNIQUE NOT NULL,
    visit_id INT NOT NULL,
    check_in_time DATETIME NOT NULL,
    check_out_time DATETIME NULL,
    checked_in_by INT NOT NULL,
    checked_out_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (checked_out_by) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_visit_id (visit_id),
    INDEX idx_check_in_time (check_in_time),
    INDEX idx_check_out_time (check_out_time),
    INDEX idx_pass_number (pass_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

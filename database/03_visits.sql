-- Visitor Access Management System (VAMS)
-- Phase 3: Visit Management and Approval Workflow
-- This file creates the visits table

-- Create visits table
CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_code VARCHAR(20) UNIQUE NOT NULL,
    visitor_id INT NOT NULL,
    host_id INT NOT NULL,
    department VARCHAR(100) NULL,
    visit_date DATE NOT NULL,
    expected_time TIME NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    number_of_visitors INT DEFAULT 1,
    notes TEXT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Checked In', 'Checked Out', 'Cancelled') DEFAULT 'Pending',
    rejection_reason TEXT NULL,
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_visit_date (visit_date),
    INDEX idx_status (status),
    INDEX idx_host_id (host_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_visit_code (visit_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

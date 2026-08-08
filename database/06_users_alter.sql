-- Visitor Access Management System (VAMS)
-- Phase 6: User Management
-- This file adds is_deleted column to users table for soft delete functionality

ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER status;

-- Add index for faster queries on is_deleted
ALTER TABLE users ADD INDEX idx_users_is_deleted (is_deleted);

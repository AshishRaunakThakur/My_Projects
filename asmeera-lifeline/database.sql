-- database.sql
-- Asmeera Lifeline Database Schema

CREATE DATABASE IF NOT EXISTS asmeera_lifeline;
USE asmeera_lifeline;

-- Roles Table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    profile_image VARCHAR(255),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    INDEX idx_email (email),
    INDEX idx_role (role_id)
);

-- Emergency Requests Table
CREATE TABLE emergency_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    request_type ENUM('food', 'water', 'medical', 'shelter', 'rescue', 'other') NOT NULL,
    title VARCHAR(200),
    description TEXT,
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'low',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_name VARCHAR(255),
    affected_people INT DEFAULT 1,
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    image_path VARCHAR(255),
    assigned_to INT,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_location (latitude, longitude)
);

-- Volunteers Table
CREATE TABLE volunteers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    skills TEXT,
    availability ENUM('available', 'busy', 'offline') DEFAULT 'available',
    total_tasks_completed INT DEFAULT 0,
    current_location_lat DECIMAL(10, 8),
    current_location_lng DECIMAL(11, 8),
    verified BOOLEAN DEFAULT FALSE,
    joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_availability (availability)
);

-- NGOs Table
CREATE TABLE ngos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    organization_name VARCHAR(150) NOT NULL,
    registration_number VARCHAR(100),
    verified BOOLEAN DEFAULT FALSE,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    contact_person VARCHAR(100),
    website VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_verified (verified)
);

-- Relief Camps Table
CREATE TABLE relief_camps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ngo_id INT,
    camp_name VARCHAR(150) NOT NULL,
    camp_type ENUM('relief', 'medical', 'food', 'shelter') NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    address TEXT,
    capacity INT,
    current_occupancy INT DEFAULT 0,
    contact_phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id),
    INDEX idx_location (latitude, longitude)
);

-- Resources Table
CREATE TABLE resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ngo_id INT,
    resource_type ENUM('food_packets', 'water_bottles', 'medicines', 'blankets', 'emergency_kits', 'other') NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(50),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id),
    INDEX idx_resource_type (resource_type)
);

-- Missing Persons Table
CREATE TABLE missing_persons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reported_by INT,
    full_name VARCHAR(100) NOT NULL,
    age INT,
    gender ENUM('male', 'female', 'other'),
    photo_path VARCHAR(255),
    last_seen_location VARCHAR(255),
    last_seen_lat DECIMAL(10, 8),
    last_seen_lng DECIMAL(11, 8),
    last_seen_date DATE,
    description TEXT,
    contact_info VARCHAR(200),
    status ENUM('missing', 'found') DEFAULT 'missing',
    found_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_name (full_name)
);

-- Safe Checkins Table
CREATE TABLE safe_checkins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_name VARCHAR(255),
    message TEXT,
    checked_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_time (checked_in_at)
);

-- Notifications Table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(200),
    message TEXT,
    type ENUM('emergency', 'assignment', 'update', 'alert', 'info') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_read (user_id, is_read)
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_time (user_id, created_at)
);

-- Insert Roles
INSERT INTO roles (role_name, description) VALUES
('citizen', 'Regular citizen user'),
('volunteer', 'Emergency volunteer'),
('ngo', 'NGO/Organization representative'),
('admin', 'System Administrator');

-- Insert Sample Admin (password: admin123)
INSERT INTO users (full_name, email, password, phone, role_id, is_active) VALUES
('System Admin', 'admin@asmeera.com', '$2y$10$/zvkKUYS6Qjl.1dQvkEGRuJZ8Puq3zTeAknntUaPdmYglMbkaSCGm', '+911234567890', 4, TRUE);

-- Insert Sample Data
INSERT INTO ngos (user_id, organization_name, registration_number, verified, latitude, longitude) VALUES
(1, 'Red Cross Society', 'RC123456', TRUE, 40.7128, -74.0060),
(1, 'Doctors Without Borders', 'MSF789012', TRUE, 40.7580, -73.9855);

INSERT INTO resources (ngo_id, resource_type, quantity, unit) VALUES
(1, 'food_packets', 500, 'packets'),
(1, 'water_bottles', 1000, 'bottles'),
(1, 'medicines', 200, 'kits'),
(2, 'medicines', 150, 'kits'),
(2, 'emergency_kits', 100, 'kits');

-- Add this to your database.sql
-- API Logs Table for Rate Limiting
CREATE TABLE IF NOT EXISTS api_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    ip_address VARCHAR(45),
    endpoint VARCHAR(100),
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ip_time (ip_address, request_time),
    INDEX idx_user_time (user_id, request_time)
);

-- Add missing columns to volunteers table
ALTER TABLE volunteers 
ADD COLUMN IF NOT EXISTS last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create index for better performance
CREATE INDEX idx_emergency_status_priority ON emergency_requests(status, priority);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
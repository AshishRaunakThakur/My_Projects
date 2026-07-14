-- Run this in XAMPP phpMyAdmin or MySQL console
-- Step 1: Create the database
CREATE DATABASE IF NOT EXISTS online_exam_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE online_exam_system;

-- Done! Tables will be auto-created by Flask SQLAlchemy when you run app.py
-- Just make sure XAMPP MySQL is running before starting the app

-- To verify database was created:
SHOW DATABASES LIKE 'online_exam_system';
-- Ashmeera Empowers Database Schema
-- MySQL Database

CREATE DATABASE IF NOT EXISTS ashmeera_empowers;
USE ashmeera_empowers;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('user', 'employer', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    profile_photo VARCHAR(255),
    resume VARCHAR(255),
    bio TEXT,
    location VARCHAR(100),
    disability_type VARCHAR(50),
    date_of_birth DATE,
    gender VARCHAR(20),
    preferred_job_type VARCHAR(50),
    preferred_location VARCHAR(100),
    expected_salary_min INT,
    expected_salary_max INT,
    work_from_home BOOLEAN DEFAULT FALSE,
    skills TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);

-- Employers table
CREATE TABLE employers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    company_logo VARCHAR(255),
    company_description TEXT,
    company_website VARCHAR(100),
    company_size VARCHAR(50),
    industry VARCHAR(50),
    founded_year INT,
    headquarters VARCHAR(100),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Jobs table
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employer_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    category VARCHAR(50),
    job_type VARCHAR(50),
    experience_level VARCHAR(50),
    location VARCHAR(100),
    salary_min INT,
    salary_max INT,
    work_from_home BOOLEAN DEFAULT FALSE,
    disability_friendly BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE CASCADE
);

-- Applications table
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'shortlisted', 'approved', 'rejected') DEFAULT 'pending',
    cover_letter TEXT,
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    match_percentage INT,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, user_id)
);

-- Saved Jobs table
CREATE TABLE saved_jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    saved_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_saved_job (user_id, job_id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Skills table
CREATE TABLE skills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    skill_name VARCHAR(50) NOT NULL,
    proficiency VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Education table
CREATE TABLE education (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    degree VARCHAR(100) NOT NULL,
    institution VARCHAR(100) NOT NULL,
    field_of_study VARCHAR(100),
    start_date DATE,
    end_date DATE,
    grade VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Certificates table
CREATE TABLE certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    certificate_name VARCHAR(100) NOT NULL,
    issuing_organization VARCHAR(100),
    issue_date DATE,
    expiry_date DATE,
    credential_id VARCHAR(100),
    certificate_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feedback table
CREATE TABLE feedbacks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    rating INT,
    subject VARCHAR(100),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Reports table
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    job_id INT,
    report_type VARCHAR(50),
    description TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- Insert sample data
INSERT INTO users (email, password_hash, full_name, user_type, is_verified, is_active) 
VALUES ('admin@ashmeera.com', '$2b$12$hashedpassword', 'Admin', 'admin', TRUE, TRUE);

INSERT INTO users (email, password_hash, full_name, user_type, is_verified, is_active, skills, location, disability_type) 
VALUES ('john@example.com', '$2b$12$hashedpassword', 'John Doe', 'user', TRUE, TRUE, 'Python, JavaScript, HTML, CSS', 'Mumbai', 'Physical');

INSERT INTO users (email, password_hash, full_name, user_type, is_verified, is_active) 
VALUES ('company@example.com', '$2b$12$hashedpassword', 'Tech Corp', 'employer', TRUE, TRUE);

INSERT INTO employers (user_id, company_name, company_description, industry, is_verified) 
VALUES (3, 'Tech Corp', 'Leading technology company', 'Technology', TRUE);

INSERT INTO jobs (employer_id, title, description, requirements, category, job_type, experience_level, location, salary_min, salary_max, is_active, is_featured)
VALUES (1, 'Senior Python Developer', 'We are looking for a Senior Python Developer to join our team.', 'Python, Flask, SQL, JavaScript, 5+ years experience', 'Technology', 'Full-time', 'Senior', 'Mumbai', 800000, 1500000, TRUE, TRUE);

INSERT INTO jobs (employer_id, title, description, requirements, category, job_type, experience_level, location, salary_min, salary_max, is_active, is_featured)
VALUES (1, 'Frontend Developer', 'Exciting opportunity for a Frontend Developer.', 'HTML, CSS, JavaScript, React, 2+ years experience', 'Technology', 'Full-time', 'Mid', 'Pune', 600000, 1000000, TRUE, FALSE);

INSERT INTO jobs (employer_id, title, description, requirements, category, job_type, experience_level, location, salary_min, salary_max, is_active, is_featured)
VALUES (1, 'Data Analyst', 'Join our data team as a Data Analyst.', 'Python, SQL, Excel, Data Visualization, 2+ years experience', 'Data Science', 'Full-time', 'Mid', 'Remote', 700000, 1200000, TRUE, FALSE);
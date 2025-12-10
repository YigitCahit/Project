CREATE DATABASE IF NOT EXISTS yatay_gecis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE yatay_gecis;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(40) DEFAULT NULL,
    department VARCHAR(150) DEFAULT NULL,
    role ENUM('student','clerk','chair','manager','executive') NOT NULL DEFAULT 'student',
    status ENUM('pending','active','disabled') NOT NULL DEFAULT 'pending',
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    current_school VARCHAR(150) NOT NULL,
    current_faculty VARCHAR(150) NOT NULL,
    current_department VARCHAR(150) NOT NULL,
    student_number VARCHAR(50) NOT NULL,
    current_instruction VARCHAR(20) NOT NULL,
    completed_semester TINYINT NOT NULL,
    target_faculty VARCHAR(150) NOT NULL,
    target_department VARCHAR(150) NOT NULL,
    target_instruction VARCHAR(20) NOT NULL,
    student_first_name VARCHAR(100) NOT NULL,
    student_last_name VARCHAR(100) NOT NULL,
    student_address VARCHAR(255) NOT NULL,
    student_phone VARCHAR(40) NOT NULL,
    status ENUM('draft','pending_clerk','approved','rejected') NOT NULL DEFAULT 'pending_clerk',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS application_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    doc_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS application_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    actor_id INT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_user FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX idx_app_status ON applications(status);
CREATE INDEX idx_app_target_dept ON applications(target_department);

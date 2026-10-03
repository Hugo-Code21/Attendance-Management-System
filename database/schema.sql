CREATE DATABASE IF NOT EXISTS ams_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ams_db;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    class_name VARCHAR(80) NULL,
    subject VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_role_class_active (role, class_name, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_attendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Hadir', 'Izin', 'Sakit', 'Alfa') NOT NULL,
    submitted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_attendance_day (student_id, attendance_date),
    KEY idx_attendance_date_status (attendance_date, status),
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_attendance (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Hadir', 'Izin', 'Sakit') NOT NULL,
    submitted_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_teacher_attendance_day (teacher_id, attendance_date),
    CONSTRAINT fk_attendance_teacher FOREIGN KEY (teacher_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB;

/*
OPTIONAL FICTIONAL DEMO ACCOUNTS

This entire block is a SQL comment, so importing this file creates only the
tables and no demo accounts. To use the demo roster, remove the opening
comment marker above and the closing marker below. Edit the names, usernames,
class, subject, and password hash to suit your project before importing.

The example hash below is for the demo password 123456789. Generate a new
hash with PHP password_hash() if you change the demo password.

INSERT INTO users (username, password_hash, role, full_name, class_name, subject) VALUES
    ('teacher01', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'teacher', 'Raka Pradipta', 'Example Class', 'Software Development'),
    ('student01', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Nadia Kencana', 'Example Class', NULL),
    ('student02', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Faris Wicaksana', 'Example Class', NULL),
    ('student03', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Keisha Anindita', 'Example Class', NULL),
    ('student04', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Damar Wisesa', 'Example Class', NULL),
    ('student05', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Alina Permadi', 'Example Class', NULL),
    ('student06', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Bima Prakoso', 'Example Class', NULL),
    ('student07', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Citra Maheswari', 'Example Class', NULL),
    ('student08', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Daffa Ramadhan', 'Example Class', NULL),
    ('student09', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Eka Puspitasari', 'Example Class', NULL),
    ('student10', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Fikri Mahendra', 'Example Class', NULL),
    ('student11', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Gita Larasati', 'Example Class', NULL),
    ('student12', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Haris Setiawan', 'Example Class', NULL),
    ('student13', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Intan Maharani', 'Example Class', NULL),
    ('student14', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Jovan Nugraha', 'Example Class', NULL),
    ('student15', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Kirana Safitri', 'Example Class', NULL),
    ('student16', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Luthfi Akbar', 'Example Class', NULL),
    ('student17', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Maya Kurnia', 'Example Class', NULL),
    ('student18', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Naufal Arya', 'Example Class', NULL),
    ('student19', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Olivia Kartika', 'Example Class', NULL),
    ('student20', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Panji Wiratama', 'Example Class', NULL),
    ('student21', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Qaila Rahmani', 'Example Class', NULL),
    ('student22', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Rafi Firmansyah', 'Example Class', NULL),
    ('student23', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Salma Nirmala', 'Example Class', NULL),
    ('student24', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Tegar Purnama', 'Example Class', NULL),
    ('student25', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Ulya Damayanti', 'Example Class', NULL),
    ('student26', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Vino Kharisma', 'Example Class', NULL),
    ('student27', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Wulan Anggraini', 'Example Class', NULL),
    ('student28', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Yudha Prasetya', 'Example Class', NULL),
    ('student29', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Zahra Amelia', 'Example Class', NULL),
    ('student30', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Aditia Kusuma', 'Example Class', NULL),
    ('student31', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Bella Oktaviani', 'Example Class', NULL),
    ('student32', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Cakra Wijaya', 'Example Class', NULL),
    ('student33', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Dian Prameswari', 'Example Class', NULL),
    ('student34', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Elang Saputra', 'Example Class', NULL),
    ('student35', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Fitri Handayani', 'Example Class', NULL),
    ('student36', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Galang Pratama', 'Example Class', NULL),
    ('student37', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Hana Pertiwi', 'Example Class', NULL),
    ('student38', '$2y$10$8UUbmFKMAbsmzYQFJgbUcu6dSj8g23ZBNx8KYRHS1X..DN.37/TLK', 'student', 'Irfan Maulana', 'Example Class', NULL)
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    full_name = VALUES(full_name),
    class_name = VALUES(class_name),
    subject = VALUES(subject),
    is_active = 1;
*/

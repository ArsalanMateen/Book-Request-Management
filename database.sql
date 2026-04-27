-- ============================================================
-- Book Request Management System - Database Schema
-- Import: mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS book_request_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE book_request_system;

CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(80)  NOT NULL UNIQUE,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(80)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','superadmin') NOT NULL DEFAULT 'admin',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS books (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(300) NOT NULL,
    author      VARCHAR(200) DEFAULT 'Unknown',
    category    ENUM('app_development','mobile_development','ai') NOT NULL,
    UNIQUE KEY unique_book (title(200), category)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS book_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    book_title      VARCHAR(300) NOT NULL,
    category        ENUM('app_development','mobile_development','ai') NOT NULL,
    status          ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    notified        TINYINT(1) DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS api_calls (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    called_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Super Admin (password: superadmin123)
INSERT INTO admins (username, password, role) VALUES
('superadmin', '$2y$10$hoSbbxUQoUrsuvjMnxwytevamNjE9SkmtyEtftOdpFs0KamIkp9V6', 'superadmin')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role);

-- Admin (password: admin123)
INSERT INTO admins (username, password, role) VALUES
('admin', '$2y$10$9jPPy5UUi7f4wGLbUyHrNOD8.1C/bqbjyC8z0xSe5jU5vflSdr0Lq', 'admin')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role);

-- ------------------------------------------------------------
-- Demo Seed Data (password for all demo users: demo123)
-- ------------------------------------------------------------
INSERT INTO users (username, email, password, created_at) VALUES
('aya.malik',   'aya.malik@bookrequest.local',   '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-01 09:00:00'),
('liam.walker', 'liam.walker@bookrequest.local', '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-02 09:20:00'),
('noah.carter', 'noah.carter@bookrequest.local', '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-03 10:05:00'),
('zoe.hassan',  'zoe.hassan@bookrequest.local',  '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-04 11:10:00'),
('mila.khan',   'mila.khan@bookrequest.local',   '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-05 12:40:00'),
('omar.tariq',  'omar.tariq@bookrequest.local',  '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-06 14:15:00'),
('hana.qureshi','hana.qureshi@bookrequest.local','$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-07 08:35:00'),
('ivan.petrov', 'ivan.petrov@bookrequest.local', '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-08 09:10:00'),
('luna.reed',   'luna.reed@bookrequest.local',   '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-09 16:00:00'),
('reza.farid',  'reza.farid@bookrequest.local',  '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-10 17:05:00'),
('nina.shah',   'nina.shah@bookrequest.local',   '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-11 13:40:00'),
('adam.brooks', 'adam.brooks@bookrequest.local', '$2y$10$YY0hUUejPP027aRHwnDkHuvUK4Y0EzRv7p3.NQo3htgZ0vK1A8PHq', '2026-03-12 10:25:00')
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    password = VALUES(password);

INSERT IGNORE INTO books (title, author, category) VALUES
('Clean Code', 'Robert C. Martin', 'app_development'),
('Refactoring', 'Martin Fowler', 'app_development'),
('The Pragmatic Programmer', 'Andrew Hunt', 'app_development'),
('Designing Data-Intensive Applications', 'Martin Kleppmann', 'app_development'),
('Android Programming: The Big Nerd Ranch Guide', 'Bill Phillips', 'mobile_development'),
('iOS Programming: The Big Nerd Ranch Guide', 'Christian Keur', 'mobile_development'),
('Flutter in Action', 'Eric Windmill', 'mobile_development'),
('React Native in Action', 'Nader Dabit', 'mobile_development'),
('Hands-On Machine Learning', 'Aurelien Geron', 'ai'),
('Deep Learning', 'Ian Goodfellow', 'ai'),
('Pattern Recognition and Machine Learning', 'Christopher Bishop', 'ai'),
('AI Engineering', 'Chip Huyen', 'ai');

DELETE br
FROM book_requests br
INNER JOIN users u ON u.id = br.user_id
WHERE u.email LIKE '%@bookrequest.local';

INSERT INTO book_requests (user_id, book_title, category, status, notified, created_at, updated_at)
SELECT
    u.id,
    d.book_title,
    d.category,
    d.status,
    d.notified,
    d.created_at,
    d.updated_at
FROM (
    SELECT 'aya.malik' AS username, 'Clean Code' AS book_title, 'app_development' AS category, 'completed' AS status, 1 AS notified, '2026-04-01 09:10:00' AS created_at, '2026-04-03 11:20:00' AS updated_at
    UNION ALL SELECT 'aya.malik', 'Deep Learning', 'ai', 'in_progress', 0, '2026-04-08 10:45:00', '2026-04-20 15:10:00'
    UNION ALL SELECT 'aya.malik', 'Flutter in Action', 'mobile_development', 'pending', 0, '2026-04-24 17:05:00', '2026-04-24 17:05:00'
    UNION ALL SELECT 'liam.walker', 'Refactoring', 'app_development', 'completed', 1, '2026-04-02 08:15:00', '2026-04-06 10:00:00'
    UNION ALL SELECT 'liam.walker', 'Hands-On Machine Learning', 'ai', 'completed', 1, '2026-04-05 14:40:00', '2026-04-09 11:30:00'
    UNION ALL SELECT 'liam.walker', 'React Native in Action', 'mobile_development', 'in_progress', 0, '2026-04-21 09:45:00', '2026-04-26 13:15:00'
    UNION ALL SELECT 'noah.carter', 'The Pragmatic Programmer', 'app_development', 'completed', 1, '2026-04-01 13:20:00', '2026-04-04 16:10:00'
    UNION ALL SELECT 'noah.carter', 'AI Engineering', 'ai', 'pending', 0, '2026-04-23 11:05:00', '2026-04-23 11:05:00'
    UNION ALL SELECT 'noah.carter', 'Android Programming: The Big Nerd Ranch Guide', 'mobile_development', 'in_progress', 0, '2026-04-10 12:25:00', '2026-04-22 15:55:00'
    UNION ALL SELECT 'zoe.hassan', 'Designing Data-Intensive Applications', 'app_development', 'completed', 1, '2026-04-03 09:35:00', '2026-04-11 10:40:00'
    UNION ALL SELECT 'zoe.hassan', 'Pattern Recognition and Machine Learning', 'ai', 'in_progress', 0, '2026-04-18 14:05:00', '2026-04-25 11:00:00'
    UNION ALL SELECT 'zoe.hassan', 'iOS Programming: The Big Nerd Ranch Guide', 'mobile_development', 'pending', 0, '2026-04-26 08:35:00', '2026-04-26 08:35:00'
    UNION ALL SELECT 'mila.khan', 'Clean Code', 'app_development', 'completed', 1, '2026-04-01 10:00:00', '2026-04-05 09:00:00'
    UNION ALL SELECT 'mila.khan', 'Deep Learning', 'ai', 'completed', 1, '2026-04-07 12:45:00', '2026-04-12 09:20:00'
    UNION ALL SELECT 'mila.khan', 'Flutter in Action', 'mobile_development', 'in_progress', 0, '2026-04-19 16:40:00', '2026-04-25 17:10:00'
    UNION ALL SELECT 'omar.tariq', 'Refactoring', 'app_development', 'in_progress', 0, '2026-04-14 08:20:00', '2026-04-21 10:45:00'
    UNION ALL SELECT 'omar.tariq', 'Hands-On Machine Learning', 'ai', 'pending', 0, '2026-04-27 09:30:00', '2026-04-27 09:30:00'
    UNION ALL SELECT 'omar.tariq', 'React Native in Action', 'mobile_development', 'completed', 1, '2026-04-06 11:15:00', '2026-04-13 14:25:00'
    UNION ALL SELECT 'hana.qureshi', 'The Pragmatic Programmer', 'app_development', 'completed', 1, '2026-04-04 13:10:00', '2026-04-09 12:00:00'
    UNION ALL SELECT 'hana.qureshi', 'AI Engineering', 'ai', 'in_progress', 0, '2026-04-17 09:55:00', '2026-04-24 12:35:00'
    UNION ALL SELECT 'hana.qureshi', 'Android Programming: The Big Nerd Ranch Guide', 'mobile_development', 'pending', 0, '2026-04-25 10:15:00', '2026-04-25 10:15:00'
    UNION ALL SELECT 'ivan.petrov', 'Designing Data-Intensive Applications', 'app_development', 'completed', 1, '2026-04-02 16:00:00', '2026-04-08 16:00:00'
    UNION ALL SELECT 'ivan.petrov', 'Pattern Recognition and Machine Learning', 'ai', 'completed', 1, '2026-04-09 17:05:00', '2026-04-14 15:40:00'
    UNION ALL SELECT 'ivan.petrov', 'iOS Programming: The Big Nerd Ranch Guide', 'mobile_development', 'in_progress', 0, '2026-04-20 13:25:00', '2026-04-26 10:50:00'
    UNION ALL SELECT 'luna.reed', 'Clean Code', 'app_development', 'pending', 0, '2026-04-26 09:40:00', '2026-04-26 09:40:00'
    UNION ALL SELECT 'luna.reed', 'Hands-On Machine Learning', 'ai', 'completed', 1, '2026-04-03 08:45:00', '2026-04-07 11:45:00'
    UNION ALL SELECT 'luna.reed', 'Flutter in Action', 'mobile_development', 'completed', 1, '2026-04-05 15:15:00', '2026-04-10 09:50:00'
    UNION ALL SELECT 'reza.farid', 'Refactoring', 'app_development', 'completed', 1, '2026-04-04 18:00:00', '2026-04-09 18:30:00'
    UNION ALL SELECT 'reza.farid', 'AI Engineering', 'ai', 'in_progress', 0, '2026-04-16 09:10:00', '2026-04-23 15:30:00'
    UNION ALL SELECT 'reza.farid', 'React Native in Action', 'mobile_development', 'pending', 0, '2026-04-27 08:20:00', '2026-04-27 08:20:00'
    UNION ALL SELECT 'nina.shah', 'The Pragmatic Programmer', 'app_development', 'in_progress', 0, '2026-04-15 11:45:00', '2026-04-24 16:05:00'
    UNION ALL SELECT 'nina.shah', 'Deep Learning', 'ai', 'completed', 1, '2026-04-06 12:20:00', '2026-04-12 14:45:00'
    UNION ALL SELECT 'nina.shah', 'Android Programming: The Big Nerd Ranch Guide', 'mobile_development', 'completed', 1, '2026-04-01 07:55:00', '2026-04-05 09:30:00'
    UNION ALL SELECT 'adam.brooks', 'Designing Data-Intensive Applications', 'app_development', 'pending', 0, '2026-04-27 12:05:00', '2026-04-27 12:05:00'
    UNION ALL SELECT 'adam.brooks', 'Pattern Recognition and Machine Learning', 'ai', 'completed', 1, '2026-04-07 10:30:00', '2026-04-13 11:35:00'
    UNION ALL SELECT 'adam.brooks', 'iOS Programming: The Big Nerd Ranch Guide', 'mobile_development', 'in_progress', 0, '2026-04-22 14:15:00', '2026-04-26 16:55:00'
) AS d
INNER JOIN users u ON u.username = d.username;

DELETE ac
FROM api_calls ac
INNER JOIN users u ON u.id = ac.user_id
WHERE u.email LIKE '%@bookrequest.local';

INSERT INTO api_calls (user_id, called_at)
SELECT u.id, x.called_at
FROM (
    SELECT 'aya.malik' AS username, '2026-04-22 09:00:00' AS called_at
    UNION ALL SELECT 'aya.malik', '2026-04-23 12:15:00'
    UNION ALL SELECT 'liam.walker', '2026-04-22 10:40:00'
    UNION ALL SELECT 'noah.carter', '2026-04-24 13:20:00'
    UNION ALL SELECT 'mila.khan', '2026-04-25 11:05:00'
    UNION ALL SELECT 'hana.qureshi', '2026-04-26 15:45:00'
    UNION ALL SELECT 'nina.shah', '2026-04-26 16:10:00'
    UNION ALL SELECT 'adam.brooks', '2026-04-27 09:25:00'
) AS x
INNER JOIN users u ON u.username = x.username;

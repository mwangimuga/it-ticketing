CREATE DATABASE IF NOT EXISTS it_ticketing_system;
USE it_ticketing_system;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('Admin', 'IT_Head', 'IT_Agent', 'End_User') NOT NULL DEFAULT 'End_User',
    department VARCHAR(100),
    sso_id VARCHAR(100)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    default_priority ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium'
);

CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_uuid VARCHAR(36) NOT NULL UNIQUE,
    requester_id INT NOT NULL,
    assigned_agent_id INT,
    category_id INT,
    priority ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Low',
    status ENUM('New', 'Open', 'In-Progress', 'Pending', 'Resolved', 'Closed') NOT NULL DEFAULT 'New',
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (assigned_agent_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    is_internal BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE slas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    priority_level ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL UNIQUE,
    response_time_limit INT NOT NULL COMMENT 'Limit in minutes',
    resolution_time_limit INT NOT NULL COMMENT 'Limit in minutes'
);

CREATE TABLE ticket_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    changed_by_user_id INT NOT NULL,
    old_status ENUM('New', 'Open', 'In-Progress', 'Pending', 'Resolved', 'Closed'),
    new_status ENUM('New', 'Open', 'In-Progress', 'Pending', 'Resolved', 'Closed'),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
);

-- Basic inserts to set up test environment
-- Default password for all test users is 'password'. Hash is standard BCRYPT.
INSERT INTO users (username, password, email, role, department) VALUES 
('admin1', '$2y$10$U/UZv4EzGStSnrEgxgb8eei17YXPU.0mYxmknHCBht7IECYJ.rsHi', 'admin@example.com', 'Admin', 'IT'),
('head1', '$2y$10$U/UZv4EzGStSnrEgxgb8eei17YXPU.0mYxmknHCBht7IECYJ.rsHi', 'head@example.com', 'IT_Head', 'IT'),
('agent1', '$2y$10$U/UZv4EzGStSnrEgxgb8eei17YXPU.0mYxmknHCBht7IECYJ.rsHi', 'agent1@example.com', 'IT_Agent', 'IT'),
('agent2', '$2y$10$U/UZv4EzGStSnrEgxgb8eei17YXPU.0mYxmknHCBht7IECYJ.rsHi', 'agent2@example.com', 'IT_Agent', 'IT'),
('user1', '$2y$10$U/UZv4EzGStSnrEgxgb8eei17YXPU.0mYxmknHCBht7IECYJ.rsHi', 'user1@example.com', 'End_User', 'HR'),
('user2', '$2y$10$U/UZv4EzGStSnrEgxgb8eei17YXPU.0mYxmknHCBht7IECYJ.rsHi', 'user2@example.com', 'End_User', 'Finance');

INSERT INTO categories (name, default_priority) VALUES 
('Hardware', 'Medium'),
('Software Request', 'Low'),
('Network Outage', 'High'),
('System Access', 'Medium'),
('Printer Issue', 'Low'),
('Server Down', 'Critical');

INSERT INTO slas (priority_level, response_time_limit, resolution_time_limit) VALUES 
('Low', 1440, 4320),
('Medium', 480, 2880),
('High', 120, 1440),
('Critical', 30, 240);

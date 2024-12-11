DROP DATABASE IF EXISTS student_voting_system;
CREATE DATABASE student_voting_system;
USE student_voting_system;

CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    index_number VARCHAR(10) UNIQUE NOT NULL,
    has_voted BOOLEAN DEFAULT 0
);

CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- First, drop dependent tables in correct order
DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS student_voting_status;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS elections;

-- Create elections table
CREATE TABLE IF NOT EXISTS elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('pending', 'active', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create positions table
CREATE TABLE positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position_name VARCHAR(50) NOT NULL,
    election_id INT,
    position_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (election_id) REFERENCES elections(id)
);

-- Create candidates table
CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position_id INT,
    election_id INT,
    full_name VARCHAR(100) NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    photo VARCHAR(255),
    manifesto TEXT,
    FOREIGN KEY (position_id) REFERENCES positions(id),
    FOREIGN KEY (election_id) REFERENCES elections(id)
);

-- Create votes table
CREATE TABLE votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    voter_student_id VARCHAR(50),
    candidate_id INT,
    position_id INT,
    election_id INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    FOREIGN KEY (position_id) REFERENCES positions(id),
    FOREIGN KEY (election_id) REFERENCES elections(id)
);

CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default setting for results release
INSERT INTO settings (setting_name, setting_value) 
VALUES ('results_released', 'false');

-- Create student voting status table
CREATE TABLE student_voting_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50),
    election_id INT,
    voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id)
);

-- Create the activity_logs table without foreign keys first
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NULL,
    action VARCHAR(50) NOT NULL,
    election_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE activity_logs
ADD COLUMN student_id VARCHAR(50) AFTER admin_id;

ALTER TABLE election_status ADD COLUMN is_released TINYINT(1) DEFAULT 0;


<<<<<<< HEAD
-- Active: 1765878045677@@127.0.0.1@3306
CREATE DATABASE interview_room;

USE interview_room;

CREATE TABLE rooms (
    id VARCHAR(20) PRIMARY KEY,
    host_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(20),
    participant_name VARCHAR(100),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);
=======
CREATE DATABASE interview_room;
USE interview_room;

CREATE TABLE rooms (
    id VARCHAR(20) PRIMARY KEY,
    host_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(20),
    participant_name VARCHAR(100),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

select * from participants;
>>>>>>> 887b0f0878b6801db08f9596797e077635664525

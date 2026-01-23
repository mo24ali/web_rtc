CREATE DATABASE interview_room;
USE interview_room;

drop table rooms;
CREATE TABLE rooms (
    id VARCHAR(20) PRIMARY KEY,
    host_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);²

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(20),
    participant_name VARCHAR(100),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

select * from participants;
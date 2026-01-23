CREATE DATABASE interview_room;
USE interview_room;

DROP TABLE IF EXISTS participant_connections;
DROP TABLE IF EXISTS participants;
DROP TABLE IF EXISTS rooms;

CREATE TABLE rooms (
    id VARCHAR(20) PRIMARY KEY,
    host_name VARCHAR(100) NOT NULL,
    host_socket_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    max_participants INT DEFAULT 20,
    settings JSON DEFAULT NULL
);

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(20) NOT NULL,
    participant_name VARCHAR(100) NOT NULL,
    socket_id VARCHAR(50),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_connected BOOLEAN DEFAULT TRUE,
    video_enabled BOOLEAN DEFAULT TRUE,
    audio_enabled BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    UNIQUE KEY unique_socket_room (socket_id, room_id)
);

CREATE TABLE participant_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(20) NOT NULL,
    participant_id INT NOT NULL,
    peer_connection_id VARCHAR(50),
    connection_type ENUM('host_to_participant', 'participant_to_participant') NOT NULL,
    target_participant_id INT,
    status ENUM('connecting', 'connected', 'disconnected', 'failed') DEFAULT 'connecting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
    FOREIGN KEY (target_participant_id) REFERENCES participants(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX idx_room_active ON rooms(room_id, is_active);
CREATE INDEX idx_participants_room ON participants(room_id, is_connected);
CREATE INDEX idx_connections_room ON participant_connections(room_id, status);

-- Sample data for testing
INSERT INTO rooms (id, host_name, is_active) VALUES ('TEST123', 'Test Host', TRUE);
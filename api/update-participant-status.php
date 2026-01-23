<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$room_id = $data['room_id'] ?? '';
$socket_id = $data['socket_id'] ?? '';
$participant_name = $data['participant_name'] ?? '';
$action = $data['action'] ?? ''; // join, update, leave
$video_enabled = $data['video_enabled'] ?? true;
$audio_enabled = $data['audio_enabled'] ?? true;

if (empty($room_id) || empty($socket_id) || empty($participant_name)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    if ($action === 'join') {
        // Check if room exists and is active
        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$room_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Room not found or inactive']);
            exit;
        }

        // Add or update participant
        $stmt = $pdo->prepare("
            INSERT INTO participants (room_id, participant_name, socket_id, video_enabled, audio_enabled)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                last_seen = CURRENT_TIMESTAMP,
                is_connected = TRUE,
                video_enabled = VALUES(video_enabled),
                audio_enabled = VALUES(audio_enabled)
        ");
        $stmt->execute([$room_id, $participant_name, $socket_id, $video_enabled, $audio_enabled]);

        $participant_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Participant joined successfully',
            'participant_id' => $participant_id
        ]);

    } elseif ($action === 'update') {
        // Update participant status
        $stmt = $pdo->prepare("
            UPDATE participants
            SET last_seen = CURRENT_TIMESTAMP,
                video_enabled = ?,
                audio_enabled = ?
            WHERE room_id = ? AND socket_id = ?
        ");
        $stmt->execute([$video_enabled, $audio_enabled, $room_id, $socket_id]);

        echo json_encode(['success' => true, 'message' => 'Status updated']);

    } elseif ($action === 'leave') {
        // Mark participant as disconnected
        $stmt = $pdo->prepare("
            UPDATE participants
            SET is_connected = FALSE, last_seen = CURRENT_TIMESTAMP
            WHERE room_id = ? AND socket_id = ?
        ");
        $stmt->execute([$room_id, $socket_id]);

        echo json_encode(['success' => true, 'message' => 'Participant left']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
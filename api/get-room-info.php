<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/database.php';

$room_id = $_GET['room_id'] ?? '';

if (empty($room_id)) {
    echo json_encode(['success' => false, 'message' => 'Room ID required']);
    exit;
}

try {
    // Get room info
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    // Get participants
    $stmt = $pdo->prepare("
        SELECT id, participant_name, joined_at, last_seen, is_connected, video_enabled, audio_enabled
        FROM participants
        WHERE room_id = ? AND is_connected = TRUE
        ORDER BY joined_at ASC
    ");
    $stmt->execute([$room_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get connection stats
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_participants,
            SUM(CASE WHEN is_connected = TRUE THEN 1 ELSE 0 END) as connected_participants,
            SUM(CASE WHEN video_enabled = TRUE THEN 1 ELSE 0 END) as video_enabled_count,
            SUM(CASE WHEN audio_enabled = TRUE THEN 1 ELSE 0 END) as audio_enabled_count
        FROM participants
        WHERE room_id = ?
    ");
    $stmt->execute([$room_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'room' => [
            'id' => $room['id'],
            'host_name' => $room['host_name'],
            'created_at' => $room['created_at'],
            'max_participants' => $room['max_participants']
        ],
        'participants' => $participants,
        'stats' => $stats
    ]);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
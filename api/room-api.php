<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'OPTIONS') {
    exit;
}

switch ($method) {
    case 'POST':
        if ($action === 'create-room') {
            createRoom();
        } elseif ($action === 'join-room') {
            joinRoom();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
        }
        break;

    case 'GET':
        if ($action === 'room-status') {
            getRoomStatus();
        } elseif ($action === 'room-participants') {
            getRoomParticipants();
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function createRoom() {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['host_name'])) {
        echo json_encode(['success' => false, 'message' => 'Host name required']);
        exit;
    }

    // Generate unique room ID
    $room_id = substr(md5(uniqid(rand(), true)), 0, 8);
    $room_id = strtoupper($room_id);

    try {
        $stmt = $pdo->prepare("INSERT INTO rooms (id, host_name) VALUES (?, ?)");
        $stmt->execute([$room_id, $data['host_name']]);

        echo json_encode([
            'success' => true,
            'room_id' => $room_id,
            'message' => 'Room created successfully'
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function joinRoom() {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);
    $roomId = $data['room_id'] ?? '';
    $participantName = $data['participant_name'] ?? '';

    if (empty($roomId) || empty($participantName)) {
        echo json_encode(['success' => false, 'message' => 'Room ID and participant name are required']);
        exit;
    }

    try {
        // Check if room exists and is active
        $stmt = $pdo->prepare("SELECT id, host_name FROM rooms WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            echo json_encode(['success' => false, 'message' => 'Room not found or inactive']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'room' => $room,
            'message' => 'Room joined successfully'
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getRoomStatus() {
    global $pdo;

    $roomId = $_GET['room_id'] ?? '';

    if (empty($roomId)) {
        echo json_encode(['success' => false, 'message' => 'Room ID is required']);
        exit;
    }

    try {
        // Get room info
        $stmt = $pdo->prepare("
            SELECT id, host_name, created_at, is_active, max_participants
            FROM rooms
            WHERE id = ?
        ");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            echo json_encode(['success' => false, 'message' => 'Room not found']);
            exit;
        }

        // Get participant count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as participant_count
            FROM participants
            WHERE room_id = ? AND is_connected = TRUE
        ");
        $stmt->execute([$roomId]);
        $participantCount = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'room' => $room,
            'participant_count' => $participantCount['participant_count']
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function getRoomParticipants() {
    global $pdo;

    $roomId = $_GET['room_id'] ?? '';

    if (empty($roomId)) {
        echo json_encode(['success' => false, 'message' => 'Room ID is required']);
        exit;
    }

    try {
        // Get all participants
        $stmt = $pdo->prepare("
            SELECT id, participant_name, socket_id, joined_at, last_seen, is_connected, video_enabled, audio_enabled
            FROM participants
            WHERE room_id = ?
            ORDER BY joined_at ASC
        ");
        $stmt->execute([$roomId]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'participants' => $participants
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
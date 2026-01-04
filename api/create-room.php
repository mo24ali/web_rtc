<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['host_name'])) {
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
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
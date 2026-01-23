<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class SignalingServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
        global $pdo;
        $this->pdo = $pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
            echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        $roomId = $data['room'] ?? '';
        $type = $data['type'] ?? '';
        
        switch($type) {
            case 'host':
                // Verify room exists in database
                $stmt = $this->pdo->prepare("SELECT id, host_name FROM rooms WHERE id = ? AND is_active = TRUE");
                $stmt->execute([$roomId]);
                $roomData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($roomData) {
                    $this->rooms[$roomId] = [
                        'host' => $from,
                        'participants' => [],
                        'room_data' => $roomData
                    ];
                    $from->room = $roomId;
                    $from->type = 'host';
                    $from->name = $data['name'] ?? $roomData['host_name'];

                    // Update host socket ID in database
                    $stmt = $this->pdo->prepare("UPDATE rooms SET host_socket_id = ? WHERE id = ?");
                    $stmt->execute([$from->resourceId, $roomId]);

                    echo "Host connected to room: {$roomId}\n";

                    // Send current participants to host
                    $this->sendCurrentParticipantsToHost($roomId, $from);
                } else {
                    $from->send(json_encode([
                        'type' => 'error',
                        'message' => 'Room does not exist'
                    ]));
                }
                break;

            case 'participant':
                // Verify room exists and is active
                $stmt = $this->pdo->prepare("SELECT id FROM rooms WHERE id = ? AND is_active = TRUE");
                $stmt->execute([$roomId]);
                $roomExists = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($roomExists) {
                    // Initialize room if not exists in memory
                    if (!isset($this->rooms[$roomId])) {
                        $this->rooms[$roomId] = [
                            'host' => null,
                            'participants' => [],
                            'room_data' => $roomExists
                        ];
                    }

                    $from->room = $roomId;
                    $from->type = 'participant';
                    $from->name = $data['name'] ?? 'Participant';

                    // Add participant to room
                    $this->rooms[$roomId]['participants'][$from->resourceId] = $from;

                    // Update database
                    $this->updateParticipantInDB($roomId, $from->resourceId, $from->name, 'join');

                    // Notify host about new participant
                    $host = $this->rooms[$roomId]['host'];
                    if ($host) {
                        $host->send(json_encode([
                            'type' => 'newParticipant',
                            'participantId' => $from->resourceId,
                            'name' => $from->name,
                            'socketId' => $from->resourceId
                        ]));

                        // Send host info to participant
                        $from->send(json_encode([
                            'type' => 'hostInfo',
                            'hostName' => $this->rooms[$roomId]['room_data']['host_name'],
                            'hostSocketId' => $host->resourceId
                        ]));
                    }

                    // Send existing participants to new participant
                    $this->sendExistingParticipantsToNewParticipant($roomId, $from);

                    // Notify other participants about new participant
                    $this->notifyParticipantsOfNewParticipant($roomId, $from->resourceId, $from->name);

                    echo "Participant joined room: {$roomId}\n";
                } else {
                    $from->send(json_encode([
                        'type' => 'error',
                        'message' => 'Room does not exist or has ended'
                    ]));
                }
                break;

            case 'offer':
            case 'answer':
            case 'ice-candidate':
                $targetId = $data['target'] ?? null;
                $this->forwardMessage($from, $data, $targetId);
                break;


            case 'leave':
                $this->removeParticipant($from, $roomId);
                break;

            case 'participantRemoved':
                $this->removeParticipantByHost($from, $data);
                break;

            case 'updateMediaStatus':
                $this->updateMediaStatus($from, $data);
                break;

            case 'endInterview':
                $this->endInterview($roomId);
                break;
        }
    }

    private function forwardMessage($from, $data, $targetId) {
        $roomId = $data['room'] ?? '';
        
        if (isset($this->rooms[$roomId])) {
            $room = $this->rooms[$roomId];
            
            // Add sender info to message
            $data['from'] = $from->resourceId;
            
            // Find target client
            if ($from->type === 'host' && isset($room['participants'][$targetId])) {
                $room['participants'][$targetId]->send(json_encode($data));
            } elseif ($from->type === 'participant' && $room['host']->resourceId == $targetId) {
                $room['host']->send(json_encode($data));
            }
        }
    }

    private function removeParticipant($conn, $roomId) {
        if (isset($this->rooms[$roomId])) {
            if ($conn->type === 'participant') {
                if (isset($this->rooms[$roomId]['participants'][$conn->resourceId])) {
                    unset($this->rooms[$roomId]['participants'][$conn->resourceId]);

                    // Update database
                    $this->updateParticipantInDB($roomId, $conn->resourceId, $conn->name, 'leave');

                    // Notify host
                    $host = $this->rooms[$roomId]['host'];
                    if ($host && $host->resourceId !== $conn->resourceId) {
                        $host->send(json_encode([
                            'type' => 'participantLeft',
                            'participantId' => $conn->resourceId
                        ]));
                    }

                    // Notify other participants
                    foreach ($this->rooms[$roomId]['participants'] as $participant) {
                        $participant->send(json_encode([
                            'type' => 'participantLeft',
                            'participantId' => $conn->resourceId
                        ]));
                    }
                }
            } elseif ($conn->type === 'host') {
                // Host disconnected - mark room as inactive
                $stmt = $this->pdo->prepare("UPDATE rooms SET is_active = FALSE WHERE id = ?");
                $stmt->execute([$roomId]);

                // Notify all participants
                foreach ($this->rooms[$roomId]['participants'] as $participant) {
                    $participant->send(json_encode([
                        'type' => 'hostDisconnected'
                    ]));
                }

                unset($this->rooms[$roomId]);
            }
        }
    }


    private function removeParticipantByHost($from, $data) {
        $roomId = $data['room'] ?? '';
        $targetId = $data['target'] ?? '';

        if (isset($this->rooms[$roomId]) && $from->type === 'host') {
            $room = $this->rooms[$roomId];

            // Notify the participant to leave
            if (isset($room['participants'][$targetId])) {
                $room['participants'][$targetId]->send(json_encode([
                    'type' => 'removedByHost'
                ]));
            }
        }
    }

    private function updateMediaStatus($from, $data) {
        $roomId = $data['room'] ?? '';
        $mediaType = $data['mediaType'] ?? '';
        $enabled = $data['enabled'] ?? true;

        if (isset($this->rooms[$roomId])) {
            $column = $mediaType === 'video' ? 'video_enabled' : 'audio_enabled';

            try {
                $stmt = $this->pdo->prepare("
                    UPDATE participants
                    SET {$column} = ?
                    WHERE room_id = ? AND socket_id = ?
                ");
                $stmt->execute([$enabled ? 1 : 0, $roomId, $from->resourceId]);

                // Broadcast status update to all participants in the room
                $this->broadcastMediaStatusUpdate($roomId, $from->resourceId, $mediaType, $enabled);
            } catch(PDOException $e) {
                echo "Database error in updateMediaStatus: " . $e->getMessage() . "\n";
            }
        }
    }

    private function broadcastMediaStatusUpdate($roomId, $participantId, $mediaType, $enabled) {
        if (isset($this->rooms[$roomId])) {
            $room = $this->rooms[$roomId];

            $message = [
                'type' => 'mediaStatusUpdate',
                'participantId' => $participantId,
                'mediaType' => $mediaType,
                'enabled' => $enabled
            ];

            // Send to host
            if ($room['host']) {
                $room['host']->send(json_encode($message));
            }

            // Send to all participants
            foreach ($room['participants'] as $participant) {
                $participant->send(json_encode($message));
            }
        }
    }

    private function updateParticipantInDB($roomId, $socketId, $name, $action) {
        try {
            if ($action === 'join') {
                $stmt = $this->pdo->prepare("
                    INSERT INTO participants (room_id, participant_name, socket_id, video_enabled, audio_enabled)
                    VALUES (?, ?, ?, TRUE, TRUE)
                    ON DUPLICATE KEY UPDATE
                        last_seen = CURRENT_TIMESTAMP,
                        is_connected = TRUE
                ");
                $stmt->execute([$roomId, $name, $socketId]);
            } elseif ($action === 'leave') {
                $stmt = $this->pdo->prepare("
                    UPDATE participants
                    SET is_connected = FALSE, last_seen = CURRENT_TIMESTAMP
                    WHERE room_id = ? AND socket_id = ?
                ");
                $stmt->execute([$roomId, $socketId]);
            }
        } catch(PDOException $e) {
            echo "Database error in updateParticipantInDB: " . $e->getMessage() . "\n";
        }
    }

    private function sendCurrentParticipantsToHost($roomId, $host) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT socket_id, participant_name, video_enabled, audio_enabled
                FROM participants
                WHERE room_id = ? AND is_connected = TRUE
            ");
            $stmt->execute([$roomId]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($participants)) {
                $host->send(json_encode([
                    'type' => 'currentParticipants',
                    'participants' => $participants
                ]));
            }
        } catch(PDOException $e) {
            echo "Database error in sendCurrentParticipantsToHost: " . $e->getMessage() . "\n";
        }
    }

    private function sendExistingParticipantsToNewParticipant($roomId, $newParticipant) {
        if (isset($this->rooms[$roomId])) {
            $room = $this->rooms[$roomId];

            // Get existing participants from database
            $stmt = $this->pdo->prepare("
                SELECT socket_id, participant_name, video_enabled, audio_enabled
                FROM participants
                WHERE room_id = ? AND is_connected = TRUE AND socket_id != ?
            ");
            $stmt->execute([$roomId, $newParticipant->resourceId]);
            $existingParticipants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($existingParticipants)) {
                $newParticipant->send(json_encode([
                    'type' => 'existingParticipants',
                    'participants' => $existingParticipants
                ]));
            }
        }
    }

    private function notifyParticipantsOfNewParticipant($roomId, $newParticipantId, $newParticipantName) {
        if (isset($this->rooms[$roomId])) {
            $room = $this->rooms[$roomId];

            // Notify all existing participants about the new participant
            foreach ($room['participants'] as $participantId => $participant) {
                if ($participantId !== $newParticipantId) {
                    $participant->send(json_encode([
                        'type' => 'newParticipant',
                        'participantId' => $newParticipantId,
                        'name' => $newParticipantName,
                        'socketId' => $newParticipantId
                    ]));
                }
            }
        }
    }

    private function endInterview($roomId) {
        if (isset($this->rooms[$roomId])) {
            $room = $this->rooms[$roomId];

            // Notify all participants
            foreach ($room['participants'] as $participant) {
                $participant->send(json_encode([
                    'type' => 'endInterview'
                ]));
            }

            // Close all connections
            foreach ($room['participants'] as $participant) {
                $participant->close();
            }
            $room['host']->close();

            // Remove room
            unset($this->rooms[$roomId]);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($conn->room)) {
            $this->removeParticipant($conn, $conn->room);
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
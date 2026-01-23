<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class SignalingServer implements MessageComponentInterface {
    protected $clients;
    protected $rooms;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
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
                $this->rooms[$roomId] = [
                    'host' => $from,
                    'participants' => []
                ];
                $from->room = $roomId;
                $from->type = 'host';
                $from->name = $data['name'] ?? 'Host';
                echo "Host created room: {$roomId}\n";
                break;

            case 'participant':
                if (isset($this->rooms[$roomId])) {
                    $from->room = $roomId;
                    $from->type = 'participant';
                    $from->name = $data['name'] ?? 'Participant';
                    
                    $this->rooms[$roomId]['participants'][$from->resourceId] = $from;
                    
                    // Notify host about new participant
                    $host = $this->rooms[$roomId]['host'];
                    $host->send(json_encode([
                        'type' => 'newParticipant',
                        'participantId' => $from->resourceId,
                        'name' => $from->name
                    ]));
                    
                    echo "Participant joined room: {$roomId}\n";
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
                unset($this->rooms[$roomId]['participants'][$conn->resourceId]);
                
                // Notify host
                $host = $this->rooms[$roomId]['host'];
                $host->send(json_encode([
                    'type' => 'participantLeft',
                    'participantId' => $conn->resourceId
                ]));
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
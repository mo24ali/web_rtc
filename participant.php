<?php
$room_id = $_GET['room'] ?? '';
$participant_name = $_GET['name'] ?? 'Participant';

if(empty($room_id)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Room - Participant</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Interview Room: <?php echo htmlspecialchars($room_id); ?></h1>
            <p>Participant: <?php echo htmlspecialchars($participant_name); ?></p>
            <div class="controls">
                <button id="toggleVideo" class="btn-secondary">Toggle Video</button>
                <button id="toggleAudio" class="btn-secondary">Toggle Audio</button>
                <button id="leaveRoom" class="btn-danger">Leave Room</button>
            </div>
        </header>

        <div class="video-container">
            <div class="video-wrapper">
                <h3>You</h3>
                <video id="localVideo" autoplay muted playsinline></video>
            </div>

            <div class="video-wrapper">
                <h3>Host</h3>
                <video id="hostVideo" autoplay playsinline></video>
            </div>

            <div id="otherParticipants" class="remote-videos">
                <h3>Other Participants</h3>
            </div>
        </div>
    </div>

    <script src="js/participant.js"></script>
    <script>
        const roomId = "<?php echo $room_id; ?>";
        const userName = "<?php echo $participant_name; ?>";
        
        initializeParticipant(roomId, userName);
    </script>
</body>
</html>
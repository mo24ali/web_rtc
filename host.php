<?php
$room_id = $_GET['room'] ?? '';
$host_name = $_GET['name'] ?? 'Host';

if (empty($room_id)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Room - Host</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>Interview Room: <?php echo htmlspecialchars($room_id); ?></h1>
            <p>Host: <?php echo htmlspecialchars($host_name); ?></p>
            <div class="controls">
                <button id="startInterview" class="btn-primary">Start Interview</button>
                <button id="endInterview" class="btn-danger">End Interview</button>
                <button id="toggleVideo" class="btn-secondary">Toggle Video</button>
                <button id="toggleAudio" class="btn-secondary">Toggle Audio</button>
            </div>
        </header>

        <div class="main-content">
            <div class="video-section">
                <div class="video-container">
                    <!-- Local Video -->
                    <div class="video-wrapper">
                        <h3>You (Host)</h3>
                        <video id="localVideo" autoplay muted playsinline></video>
                    </div>

                    <!-- Remote Videos Container -->
                    <div id="remoteVideos" class="remote-videos">
                        <h3>Participants</h3>
                        <!-- Participant videos will be added here dynamically -->
                    </div>
                </div>

                <div class="participants-list">
                    <h3>Connected Participants</h3>
                    <ul id="participantsList"></ul>
                </div>
            </div>

            <div class="editor-section">
                <h3>Collaborative Code Editor</h3>
                <textarea id="code-editor"></textarea>
            </div>
        </div>
    </div>

    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>

    <script src="js/host.js"></script>
    <script>
        const roomId = "<?php echo $room_id; ?>";
        const userName = "<?php echo $host_name; ?>";

        // Initialize host functionality
        initializeHost(roomId, userName);
    </script>
</body>

</html>
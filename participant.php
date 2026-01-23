<?php
$room_id = $_GET['room'] ?? '';
$participant_name = $_GET['name'] ?? 'Participant';

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
    <title>Interview Room - Participant</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- CodeMirror CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
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

        <div class="main-content">
            <div class="video-section">
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
        </main>

            <div class="editor-section">
                <h3>Collaborative Code Editor</h3>
                <textarea id="code-editor"></textarea>
            </div>
        </footer>
    </div>

    <!-- CodeMirror JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>

    <script src="js/participant.js"></script>
    <script>
        const roomId = "<?php echo $room_id; ?>";
        const userName = "<?php echo $participant_name; ?>";
        
        // Initialize call timer
        let callSeconds = 0;
        const callTimer = setInterval(() => {
            callSeconds++;
            const hours = Math.floor(callSeconds / 3600).toString().padStart(2, '0');
            const minutes = Math.floor((callSeconds % 3600) / 60).toString().padStart(2, '0');
            const seconds = (callSeconds % 60).toString().padStart(2, '0');
            
            document.getElementById('callTimer').textContent = `${hours}:${minutes}:${seconds}`;
            document.getElementById('callDuration').textContent = `${hours}:${minutes}:${seconds}`;
        }, 1000);
        
        // Toggle button states
        let videoEnabled = true;
        let audioEnabled = true;
        
        document.getElementById('toggleVideo').addEventListener('click', function() {
            videoEnabled = !videoEnabled;
            const status = document.getElementById('videoStatus');
            const icon = this.querySelector('i');
            
            if(videoEnabled) {
                status.textContent = 'ON';
                status.className = 'bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
                icon.className = 'fas fa-video text-gray-700';
            } else {
                status.textContent = 'OFF';
                status.className = 'bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
                icon.className = 'fas fa-video-slash text-gray-700';
            }
        });
        
        document.getElementById('toggleAudio').addEventListener('click', function() {
            audioEnabled = !audioEnabled;
            const status = document.getElementById('audioStatus');
            const icon = this.querySelector('i');
            
            if(audioEnabled) {
                status.textContent = 'ON';
                status.className = 'bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
                icon.className = 'fas fa-microphone text-gray-700';
            } else {
                status.textContent = 'OFF';
                status.className = 'bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
                icon.className = 'fas fa-microphone-slash text-gray-700';
            }
        });
        
        // Sync controls
        document.getElementById('toggleVideoMain').addEventListener('click', () => {
            document.getElementById('toggleVideo').click();
        });
        
        document.getElementById('toggleAudioMain').addEventListener('click', () => {
            document.getElementById('toggleAudio').click();
        });
        
        document.getElementById('leaveRoomMain').addEventListener('click', () => {
            document.getElementById('leaveRoom').click();
        });
        
        // Leave room handler
        document.getElementById('leaveRoom').addEventListener('click', function() {
            if(confirm('Are you sure you want to leave the interview room?')) {
                clearInterval(callTimer);
                // Add leaving animation
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Leaving...';
                this.disabled = true;
                
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            }
        });
        
        // Simulate participant joining (demo purposes)
        setTimeout(() => {
            const participantCount = document.getElementById('participantCount');
            participantCount.textContent = '2 online';
            participantCount.className = 'bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full';
            
            // Add sample participants
            const participantsContainer = document.getElementById('otherParticipants');
            const template = document.getElementById('participantTemplate').innerHTML;
            
            participantsContainer.innerHTML = '';
            
            // Add first participant
            participantsContainer.innerHTML += `
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:border-blue-300 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="relative">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold">JD</span>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-800 truncate">Jane Doe</h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Video ON</span>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Audio ON</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg aspect-video flex items-center justify-center">
                        <i class="fas fa-user text-white text-4xl"></i>
                    </div>
                </div>
            `;
            
            
            // Add second participant
            participantsContainer.innerHTML += `
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:border-blue-300 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="relative">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold">RS</span>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-800 truncate">Robert Smith</h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded">Video OFF</span>
                                <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Audio ON</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg aspect-video flex items-center justify-center">
                        <i class="fas fa-user text-white text-4xl"></i>
                    </div>
                </div>
            `;
        }, 3000);
        
        // Initialize your participant logic
        initializeParticipant(roomId, userName);
    </script>
</body>

</html>
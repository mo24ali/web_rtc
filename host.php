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
        
        // Session Timer
        let sessionSeconds = 0;
        const sessionTimer = setInterval(() => {
            sessionSeconds++;
            const hours = Math.floor(sessionSeconds / 3600).toString().padStart(2, '0');
            const minutes = Math.floor((sessionSeconds % 3600) / 60).toString().padStart(2, '0');
            const seconds = (sessionSeconds % 60).toString().padStart(2, '0');
            
            document.getElementById('sessionTimer').textContent = `${hours}:${minutes}:${seconds}`;
            document.getElementById('controlTimer').textContent = `${hours}:${minutes}:${seconds}`;
            document.getElementById('bottomTimer').textContent = `${hours}:${minutes}:${seconds}`;
        }, 1000);
        
        // Control Toggles
        let videoEnabled = true;
        let audioEnabled = true;
        let interviewStarted = false;
        
        // Video Toggle
        document.getElementById('toggleVideo').addEventListener('click', toggleVideo);
        document.getElementById('toggleVideoBottom').addEventListener('click', toggleVideo);
        
        function toggleVideo() {
            videoEnabled = !videoEnabled;
            const status = document.getElementById('videoStatus');
            
            if(videoEnabled) {
                status.textContent = 'ON';
                status.className = 'bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full';
            } else {
                status.textContent = 'OFF';
                status.className = 'bg-red-100 text-red-800 text-xs font-bold px-2.5 py-1 rounded-full';
            }
        }
        
        // Audio Toggle
        document.getElementById('toggleAudio').addEventListener('click', toggleAudio);
        document.getElementById('toggleAudioBottom').addEventListener('click', toggleAudio);
        
        function toggleAudio() {
            audioEnabled = !audioEnabled;
            const status = document.getElementById('audioStatus');
            
            if(audioEnabled) {
                status.textContent = 'ON';
                status.className = 'bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full';
            } else {
                status.textContent = 'OFF';
                status.className = 'bg-red-100 text-red-800 text-xs font-bold px-2.5 py-1 rounded-full';
            }
        }
        
        // Start Interview
        document.getElementById('startInterview').addEventListener('click', startInterview);
        document.getElementById('startInterviewBottom').addEventListener('click', startInterview);
        
        function startInterview() {
            interviewStarted = true;
            alert('Interview session started! Participants can now join.');
            
            // Simulate participants joining
            setTimeout(() => {
                document.getElementById('participantCount').textContent = '3';
                document.getElementById('activeParticipants').textContent = '3';
                document.getElementById('connectedCount').textContent = '3';
                
                // Update participants list
                const participantsList = document.getElementById('participantsList');
                participantsList.innerHTML = `
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">JD</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Jane Doe</p>
                                <p class="text-xs text-gray-500">Candidate • Joined 2 min ago</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Video ON</span>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Audio ON</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">RS</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Robert Smith</p>
                                <p class="text-xs text-gray-500">Interviewer • Joined 1 min ago</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Video OFF</span>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Audio ON</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">MJ</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Mike Johnson</p>
                                <p class="text-xs text-gray-500">Observer • Joined 30 sec ago</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Video ON</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Audio OFF</span>
                        </div>
                    </div>
                `;
                
                // Update remote videos grid
                const remoteVideos = document.getElementById('remoteVideos');
                remoteVideos.innerHTML = `
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                        <div class="relative bg-gray-800 aspect-video">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center">
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-400 to-cyan-500 flex items-center justify-center">
                                    <span class="text-white font-bold text-2xl">JD</span>
                                </div>
                            </div>
                            <div class="absolute top-3 left-3">
                                <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">VIDEO ON</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-gray-800">Jane Doe</h4>
                                    <p class="text-sm text-gray-500">Candidate</p>
                                </div>
                                <button class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                        <div class="relative bg-gray-800 aspect-video">
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center">
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center">
                                    <span class="text-white font-bold text-2xl">RS</span>
                                </div>
                            </div>
                            <div class="absolute top-3 left-3">
                                <span class="bg-red-100 text-red-800 text-xs font-bold px-2.5 py-1 rounded-full">VIDEO OFF</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-gray-800">Robert Smith</h4>
                                    <p class="text-sm text-gray-500">Interviewer</p>
                                </div>
                                <button class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
                        <div class="relative bg-gray-800 aspect-video">
                            <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center">
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center">
                                    <span class="text-white font-bold text-2xl">MJ</span>
                                </div>
                            </div>
                            <div class="absolute top-3 left-3">
                                <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">VIDEO ON</span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-bold text-gray-800">Mike Johnson</h4>
                                    <p class="text-sm text-gray-500">Observer</p>
                                </div>
                                <button class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }, 1000);
        }
        
        // End Interview
        document.getElementById('endInterview').addEventListener('click', endInterview);
        document.getElementById('endInterviewBottom').addEventListener('click', endInterview);
        
        function endInterview() {
            if(confirm('Are you sure you want to end the interview for all participants?')) {
                clearInterval(sessionTimer);
                alert('Interview ended. All participants will be disconnected.');
                // In real app, you would call your backend to end the session
                // window.location.href = 'index.php';
            }


        }
        
        // Initialize your host logic
        initializeHost(roomId, userName);
    </script>
</body>

</html>
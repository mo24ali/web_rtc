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
    <title>Interview Room - Participant | InterviewPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Video aspect ratio */
        .video-container video {
            object-fit: cover;
            border-radius: 12px;
        }
        
        /* Pulse animation for active speaker */
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        .speaking { animation: pulse-ring 2s infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Header -->
        <header class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-gray-200">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="bg-gradient-to-br from-blue-600 to-purple-600 w-12 h-12 rounded-xl flex items-center justify-center shadow-md">
                        <i class="fas fa-video text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Interview Room</h1>
                        <div class="flex flex-wrap items-center gap-4 mt-2">
                            <div class="flex items-center gap-2 bg-blue-50 px-3 py-1.5 rounded-lg">
                                <i class="fas fa-hashtag text-blue-600 text-sm"></i>
                                <span class="font-mono font-semibold text-blue-700"><?php echo htmlspecialchars($room_id); ?></span>
                            </div>
                            <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                                <i class="fas fa-user text-gray-600 text-sm"></i>
                                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($participant_name); ?></span>
                            </div>
                            <div class="flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg">
                                <i class="fas fa-circle text-green-500 text-xs"></i>
                                <span class="text-green-700 font-medium text-sm">Connected</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Controls -->
                <div class="flex flex-wrap gap-3">
                    <button id="toggleVideo" class="flex items-center gap-2 px-5 py-3 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow">
                        <i class="fas fa-video text-gray-700"></i>
                        <span class="font-medium text-gray-700">Video</span>
                        <span id="videoStatus" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">ON</span>
                    </button>
                    
                    <button id="toggleAudio" class="flex items-center gap-2 px-5 py-3 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow">
                        <i class="fas fa-microphone text-gray-700"></i>
                        <span class="font-medium text-gray-700">Audio</span>
                        <span id="audioStatus" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">ON</span>
                    </button>
                    
                    <button id="leaveRoom" class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-phone-slash"></i>
                        <span class="font-medium">Leave Room</span>
                    </button>
                </div>
            </div>
            
            <!-- Status Bar -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-gray-600">Connection: <span class="font-medium text-gray-800">Excellent</span></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shield-alt text-blue-500"></i>
                            <span class="text-sm text-gray-600">Call is <span class="font-medium text-gray-800">encrypted</span></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="far fa-clock text-gray-500"></i>
                            <span id="callTimer" class="text-sm font-medium text-gray-800">00:00:00</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button class="p-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors" title="Screen Share">
                            <i class="fas fa-desktop text-gray-700"></i>
                        </button>
                        <button class="p-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors" title="Chat">
                            <i class="far fa-comment text-gray-700"></i>
                        </button>
                        <button class="p-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors" title="Participants">
                            <i class="fas fa-users text-gray-700"></i>
                        </button>
                        <button class="p-2.5 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors" title="Settings">
                            <i class="fas fa-cog text-gray-700"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Video Area -->
        <main class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Local Video (You) -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                <div class="p-5 bg-gradient-to-r from-gray-800 to-gray-900">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                        <h2 class="text-lg font-semibold text-white">You</h2>
                        <div class="ml-auto flex items-center gap-2">
                            <div class="bg-black/30 backdrop-blur-sm px-3 py-1 rounded-full">
                                <span class="text-white text-sm font-medium">1080p • 60fps</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative bg-gray-900 aspect-video">
                    <video id="localVideo" autoplay muted playsinline class="w-full h-full"></video>
                    
                    <!-- Video Controls Overlay -->
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex items-center gap-3 bg-black/50 backdrop-blur-sm px-4 py-2.5 rounded-full">
                        <button class="p-2 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                            <i class="fas fa-expand text-white text-sm"></i>
                        </button>
                        <button class="p-2 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                            <i class="fas fa-camera-rotate text-white text-sm"></i>
                        </button>
                        <button class="p-2 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                            <i class="fas fa-lightbulb text-white text-sm"></i>
                        </button>
                    </div>
                    
                    <!-- Muted Indicator -->
                    <div class="absolute top-4 right-4 bg-black/60 backdrop-blur-sm px-3 py-1.5 rounded-full">
                        <i class="fas fa-volume-mute text-white"></i>
                        <span class="text-white text-sm ml-2">Muted</span>
                    </div>
                </div>
            </div>

            <!-- Host Video -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                <div class="p-5 bg-gradient-to-r from-blue-700 to-blue-800">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-crown text-white text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-white">Host</h2>
                            <p class="text-blue-100 text-sm">Waiting for host to join...</p>
                        </div>
                    </div>
                </div>
                <div class="relative bg-gray-800 aspect-video">
                    <video id="hostVideo" autoplay playsinline class="w-full h-full"></video>
                    
                    <!-- Waiting State -->
                    <div id="hostWaiting" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-800">
                        <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-user-tie text-blue-600 text-3xl"></i>
                        </div>
                        <p class="text-gray-300 font-medium">Host will join shortly</p>
                        <p class="text-gray-400 text-sm mt-2">The interview will begin soon</p>
                    </div>
                    
                    <!-- Speaking Indicator -->
                    <div class="absolute top-4 right-4 hidden" id="hostSpeaking">
                        <div class="bg-green-500 text-white px-3 py-1 rounded-full flex items-center gap-2">
                            <i class="fas fa-volume-up"></i>
                            <span class="text-sm font-medium">Speaking</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Participants -->
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-lg border border-gray-200">
                <div class="p-5 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-bold text-gray-800">Other Participants</h2>
                            <span id="participantCount" class="bg-gray-100 text-gray-800 text-sm font-medium px-3 py-1 rounded-full">0 online</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button class="text-gray-600 hover:text-gray-800 p-2">
                                <i class="fas fa-grid"></i>
                            </button>
                            <button class="text-gray-600 hover:text-gray-800 p-2">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="otherParticipants" class="p-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 min-h-[200px]">
                    <!-- Empty State -->
                    <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-users text-gray-300 text-2xl"></i>
                        </div>
                        <p class="font-medium text-gray-500">No other participants yet</p>
                        <p class="text-sm text-gray-400 mt-2">Other participants will appear here when they join</p>
                    </div>
                    
                    <!-- Participant Template (Hidden) -->
                    <div id="participantTemplate" class="hidden">
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:border-blue-300 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="relative">
                                    <div class="w-12 h-12 bg-gradient-to-br from-gray-300 to-gray-400 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-800 truncate">Participant Name</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Video ON</span>
                                        <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Audio ON</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 bg-gray-900 rounded-lg aspect-video">
                                <!-- Remote video will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Control Bar -->
        <footer class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-300 shadow-2xl py-4 px-6">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="hidden md:block">
                        <p class="text-sm text-gray-600">Interview Room</p>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($room_id); ?></p>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i class="fas fa-lock text-green-500"></i>
                        <span class="text-sm">End-to-end encrypted</span>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Video Control -->
                    <button id="toggleVideoMain" class="flex flex-col items-center p-3 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-1">
                            <i class="fas fa-video text-blue-600 text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700">Video</span>
                    </button>
                    
                    <!-- Audio Control -->
                    <button id="toggleAudioMain" class="flex flex-col items-center p-3 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-1">
                            <i class="fas fa-microphone text-green-600 text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700">Audio</span>
                    </button>
                    
                    <!-- Leave Button (Center) -->
                    <button id="leaveRoomMain" class="flex flex-col items-center p-3 rounded-xl hover:bg-red-50 transition-colors">
                        <div class="w-14 h-14 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center mb-1 shadow-lg">
                            <i class="fas fa-phone-slash text-white text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-red-700">Leave</span>
                    </button>
                    
                    <!-- More Options -->
                    <button class="flex flex-col items-center p-3 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-1">
                            <i class="fas fa-ellipsis-h text-gray-600 text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700">More</span>
                    </button>
                </div>
                
                <div class="hidden md:flex items-center gap-4">
                    <div class="text-right">
                        <p id="callDuration" class="font-mono font-semibold text-gray-800 text-lg">00:00:00</p>
                        <p class="text-xs text-gray-500">Call duration</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

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
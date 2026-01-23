<?php
$room_id = $_GET['room'] ?? '';
$host_name = $_GET['name'] ?? 'Host';

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
    <title>Host Control Panel | InterviewPro</title>
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
        
        /* Video styles */
        .video-container video {
            object-fit: cover;
            border-radius: 12px;
        }
        
        /* Pulse for active speaker */
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        .speaking { animation: pulse-ring 2s infinite; }
        
        /* Recording animation */
        @keyframes recording-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .recording { animation: recording-pulse 1.5s infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Top Header -->
        <header class="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-gray-200">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-crown text-white text-xl"></i>
                        </div>
                        <div class="absolute -top-2 -right-2 w-8 h-8 bg-red-500 rounded-full flex items-center justify-center border-2 border-white">
                            <i class="fas fa-crown text-white text-xs"></i>
                        </div>
                    </div>
                    
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            Host Control Panel
                            <span class="text-sm bg-blue-100 text-blue-800 px-3 py-1 rounded-full font-medium">LIVE</span>
                        </h1>
                        <div class="flex flex-wrap items-center gap-4 mt-3">
                            <div class="flex items-center gap-2 bg-blue-50 px-4 py-2 rounded-xl">
                                <i class="fas fa-hashtag text-blue-600"></i>
                                <span class="font-mono font-bold text-blue-800 text-lg"><?php echo htmlspecialchars($room_id); ?></span>
                            </div>
                            <div class="flex items-center gap-2 bg-purple-50 px-4 py-2 rounded-xl">
                                <i class="fas fa-user-tie text-purple-600"></i>
                                <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($host_name); ?></span>
                            </div>
                            <div class="flex items-center gap-2 bg-green-50 px-4 py-2 rounded-xl">
                                <i class="fas fa-users text-green-600"></i>
                                <span id="participantCount" class="font-bold text-green-800">0</span>
                                <span class="text-green-700">Participants</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Controls -->
                <div class="flex flex-wrap gap-3">
                    <button id="startInterview" class="flex items-center gap-3 px-6 py-3.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-play-circle text-lg"></i>
                        <span class="font-semibold">Start Interview</span>
                    </button>
                    
                    <button id="endInterview" class="flex items-center gap-3 px-6 py-3.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-stop-circle text-lg"></i>
                        <span class="font-semibold">End Interview</span>
                    </button>
                    
                    <button id="toggleVideo" class="flex items-center gap-2 px-5 py-3.5 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow">
                        <i class="fas fa-video text-gray-700"></i>
                        <span class="font-medium text-gray-700">Video</span>
                        <span id="videoStatus" class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">ON</span>
                    </button>
                    
                    <button id="toggleAudio" class="flex items-center gap-2 px-5 py-3.5 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow">
                        <i class="fas fa-microphone text-gray-700"></i>
                        <span class="font-medium text-gray-700">Audio</span>
                        <span id="audioStatus" class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">ON</span>
                    </button>
                </div>
            </div>
            
            <!-- Status Bar -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm text-gray-700"><span class="font-semibold">Connection:</span> Excellent</span>
                            </div>
                            <div class="h-4 w-px bg-gray-300"></div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-database text-blue-500"></i>
                                <span class="text-sm text-gray-700"><span class="font-semibold">Bitrate:</span> 2.5 Mbps</span>
                            </div>
                            <div class="h-4 w-px bg-gray-300"></div>
                            <div class="flex items-center gap-2">
                                <i class="far fa-clock text-gray-600"></i>
                                <span id="sessionTimer" class="text-sm font-bold text-gray-800">00:00:00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button id="recordBtn" class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            <i class="fas fa-circle text-gray-500"></i>
                            <span class="text-sm font-medium text-gray-700">Record</span>
                        </button>
                        <button class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            <i class="fas fa-desktop text-gray-600"></i>
                            <span class="text-sm font-medium text-gray-700">Share Screen</span>
                        </button>
                        <button class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            <i class="fas fa-comment-dots text-gray-600"></i>
                            <span class="text-sm font-medium text-gray-700">Chat</span>
                        </button>
                        <button class="flex items-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-200">
                            <i class="fas fa-cog text-blue-600"></i>
                            <span class="text-sm font-medium text-blue-700">Settings</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Host Video (Left Column) -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
                    <div class="p-5 bg-gradient-to-r from-gray-800 to-gray-900">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-crown text-white"></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">Host Camera</h2>
                                    <p class="text-gray-300 text-sm">You are broadcasting to all participants</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 bg-black/30 backdrop-blur-sm px-4 py-2 rounded-full">
                                <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                                <span class="text-white text-sm font-medium">LIVE • 1080p</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative bg-gray-900 aspect-video">
                        <video id="localVideo" autoplay muted playsinline class="w-full h-full"></video>
                        
                        <!-- Host Controls Overlay -->
                        <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex items-center gap-3 bg-black/60 backdrop-blur-sm px-5 py-3 rounded-full">
                            <button class="p-3 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                                <i class="fas fa-expand text-white"></i>
                            </button>
                            <button class="p-3 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                                <i class="fas fa-camera-rotate text-white"></i>
                            </button>
                            <button class="p-3 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                                <i class="fas fa-lightbulb text-white"></i>
                            </button>
                            <button class="p-3 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
                                <i class="fas fa-filter text-white"></i>
                            </button>
                        </div>
                        
                        <!-- Muted Indicator -->
                        <div class="absolute top-6 right-6 bg-black/60 backdrop-blur-sm px-4 py-2 rounded-full flex items-center gap-2">
                            <i class="fas fa-volume-mute text-white"></i>
                            <span class="text-white text-sm font-medium">Your audio is muted</span>
                        </div>
                        
                        <!-- Host Badge -->
                        <div class="absolute top-6 left-6 bg-gradient-to-r from-yellow-500 to-orange-500 text-white px-4 py-2 rounded-full flex items-center gap-2">
                            <i class="fas fa-crown"></i>
                            <span class="font-semibold">HOST</span>
                        </div>
                    </div>
                    
                    <!-- Video Stats -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Resolution</p>
                                <p class="font-bold text-gray-800">1920×1080</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Frame Rate</p>
                                <p class="font-bold text-gray-800">60 FPS</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Bitrate</p>
                                <p class="font-bold text-gray-800">2.5 Mbps</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Codec</p>
                                <p class="font-bold text-gray-800">H.264</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Participants Grid -->
                <div class="mt-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                            <i class="fas fa-users text-blue-600"></i>
                            Active Participants
                            <span id="activeParticipants" class="bg-blue-100 text-blue-800 text-sm font-bold px-3 py-1 rounded-full">0</span>
                        </h2>
                        <div class="flex items-center gap-3">
                            <button class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg">
                                <i class="fas fa-th-large text-gray-600"></i>
                            </button>
                            <button class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg">
                                <i class="fas fa-list text-gray-600"></i>
                            </button>
                            <button class="p-2 bg-blue-100 hover:bg-blue-200 rounded-lg">
                                <i class="fas fa-plus text-blue-600"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="remoteVideos" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Empty State -->
                        <div class="col-span-full bg-white rounded-2xl p-8 text-center border-2 border-dashed border-gray-300">
                            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-user-plus text-gray-400 text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">Waiting for participants</h3>
                            <p class="text-gray-500 mb-6">Participants will appear here when they join the interview</p>
                            <button class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-medium transition-colors">
                                <i class="fas fa-share-alt"></i>
                                Share Invite Link
                            </button>
                        </div>
                        
                        <!-- Participant Template (Hidden) -->
                        <div id="participantTemplate" class="hidden">
                            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:border-blue-400 transition-all duration-200">
                                <div class="relative bg-gray-800 aspect-video">
                                    <!-- Video will be inserted here -->
                                    <div class="absolute inset-0 bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center">
                                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                            <span class="text-white font-bold text-xl">JD</span>
                                        </div>
                                    </div>
                                    <!-- Status Badges -->
                                    <div class="absolute top-3 left-3">
                                        <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">VIDEO ON</span>
                                    </div>
                                    <div class="absolute top-3 right-3">
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    </div>
                                    <!-- Controls -->
                                    <div class="absolute bottom-3 right-3 flex gap-2">
                                        <button class="p-2 bg-black/50 hover:bg-black/70 rounded-full backdrop-blur-sm">
                                            <i class="fas fa-volume-up text-white text-sm"></i>
                                        </button>
                                        <button class="p-2 bg-black/50 hover:bg-black/70 rounded-full backdrop-blur-sm">
                                            <i class="fas fa-expand text-white text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="font-bold text-gray-800">Participant Name</h4>
                                            <p class="text-sm text-gray-500">Joined 2 min ago</p>
                                        </div>
                                        <button class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Participants List & Controls -->
            <div class="lg:col-span-1">
                <!-- Participants List -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200 mb-8">
                    <div class="p-5 bg-gradient-to-r from-blue-600 to-blue-700">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            <i class="fas fa-list-check"></i>
                            Connected Participants
                            <span id="connectedCount" class="bg-white/20 text-white text-sm font-bold px-3 py-1 rounded-full">0</span>
                        </h2>
                    </div>
                    
                    <div class="p-4 max-h-[500px] overflow-y-auto">
                        <div id="participantsList" class="space-y-3">
                            <!-- Empty State -->
                            <div class="text-center py-8">
                                <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500 font-medium">No participants connected</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <h3 class="font-semibold text-gray-700 mb-3">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button class="p-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-center">
                                <i class="fas fa-microphone-slash text-red-500 mb-1"></i>
                                <p class="text-xs font-medium text-gray-700">Mute All</p>
                            </button>
                            <button class="p-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-center">
                                <i class="fas fa-video-slash text-red-500 mb-1"></i>
                                <p class="text-xs font-medium text-gray-700">Stop Videos</p>
                            </button>
                            <button class="p-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-center">
                                <i class="fas fa-user-plus text-blue-500 mb-1"></i>
                                <p class="text-xs font-medium text-gray-700">Invite More</p>
                            </button>
                            <button class="p-3 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-center">
                                <i class="fas fa-download text-green-500 mb-1"></i>
                                <p class="text-xs font-medium text-gray-700">Export List</p>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Interview Controls -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-200">
                    <div class="p-5 bg-gradient-to-r from-gray-800 to-gray-900">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            <i class="fas fa-sliders-h"></i>
                            Interview Controls
                        </h2>
                    </div>
                    
                    <div class="p-5 space-y-6">
                        <!-- Recording Control -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-semibold text-gray-800">Recording</h3>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                    <span class="text-sm text-gray-500">Off</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="flex-1 py-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg font-medium border border-red-200">
                                    <i class="fas fa-circle mr-2"></i>
                                    Start Recording
                                </button>
                                <button class="px-4 py-3 bg-gray-100 hover:bg-gray-200 rounded-lg">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Session Timer -->
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-gray-800">Session Duration</h3>
                                <i class="far fa-clock text-gray-500"></i>
                            </div>
                            <p id="controlTimer" class="text-3xl font-bold text-gray-800 font-mono text-center">00:00:00</p>
                            <p class="text-sm text-gray-500 text-center">Interview in progress</p>
                        </div>
                        
                        <!-- Interview Status -->
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">Interview Status</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Room Status</span>
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">ACTIVE</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Participants</span>
                                    <span class="font-bold text-gray-800">0/20</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Max Duration</span>
                                    <span class="font-bold text-gray-800">2 hours</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Control Bar -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-300 shadow-2xl py-4 px-6">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="hidden lg:flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-crown text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">You are hosting</p>
                            <p class="font-bold text-gray-800"><?php echo htmlspecialchars($room_id); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 text-green-600">
                        <i class="fas fa-shield-alt"></i>
                        <span class="text-sm">Room encrypted</span>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <button id="toggleVideoBottom" class="flex flex-col items-center p-3 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-1">
                            <i class="fas fa-video text-blue-600 text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700">Video</span>
                    </button>
                    
                    <button id="toggleAudioBottom" class="flex flex-col items-center p-3 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-1">
                            <i class="fas fa-microphone text-green-600 text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700">Audio</span>
                    </button>
                    
                    <button id="startInterviewBottom" class="flex flex-col items-center p-3 rounded-xl hover:bg-green-50 transition-colors">
                        <div class="w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center mb-1 shadow-lg">
                            <i class="fas fa-play text-white text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-green-700">Start</span>
                    </button>
                    
                    <button id="endInterviewBottom" class="flex flex-col items-center p-3 rounded-xl hover:bg-red-50 transition-colors">
                        <div class="w-14 h-14 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center mb-1 shadow-lg">
                            <i class="fas fa-stop text-white text-xl"></i>
                        </div>
                        <span class="text-xs font-medium text-red-700">End</span>
                    </button>
                    
                    <button class="flex flex-col items-center p-3 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-1">
                            <i class="fas fa-ellipsis-h text-gray-600 text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-gray-700">More</span>
                    </button>
                </div>
                
                <div class="hidden lg:block">
                    <div class="text-right">
                        <p id="bottomTimer" class="font-mono font-bold text-gray-800 text-lg">00:00:00</p>
                        <p class="text-xs text-gray-500">Interview duration</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
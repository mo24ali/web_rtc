<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeMeet | Virtual Interview Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        
        /* Animation for room info panel */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Custom gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #4361ee, #7209b7);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Card hover effect */
        .card-hover {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }
        
        /* Input focus effect */
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl min-h-screen flex flex-col">
        <!-- Header -->
        <header class="text-center mb-12 pt-4">
            <div class="flex items-center justify-center gap-3 mb-6">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-purple-600 text-white rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-video text-xl"></i>
                </div>
                <h1 class="text-4xl font-bold gradient-text">CodeMeet</h1>
            </div>
            <p class="text-gray-600 text-lg max-w-2xl mx-auto font-normal">
                Professional virtual interview platform with crystal-clear audio and video. Connect seamlessly with candidates anywhere.
            </p>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col justify-center">
            <h1 class="text-5xl font-bold text-center mb-4 gradient-text">
                Start Your Interview Session
            </h1>
            <p class="text-gray-600 text-xl text-center mb-12 max-w-3xl mx-auto">
                Choose to host a new interview room or join as a participant with a room ID
            </p>
            
            <!-- Cards Container -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <!-- Host Card -->
                <div class="bg-white rounded-2xl p-8 shadow-xl border border-gray-200 card-hover flex flex-col">
                    <div class="w-18 h-18 bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-user-tie text-3xl text-blue-600"></i>
                    </div>
                    
                    <h2 class="text-2xl font-semibold text-gray-800 mb-3">Host an Interview</h2>
                    <p class="text-gray-600 mb-8 text-lg">
                        Create a new interview room, invite participants, and manage the session as the host.
                    </p>
                    
                    <form id="hostForm" class="flex-1 flex flex-col">
                        <div class="mb-6">
                            <label for="hostName" class="block text-gray-700 font-medium mb-2 text-sm uppercase tracking-wider">
                                Your Name
                            </label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="hostName" 
                                       class="w-full pl-12 pr-4 py-4 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:outline-none input-focus transition-colors"
                                       placeholder="Enter your full name"
                                       required>
                            </div>
                        </div>
                        
                        <button type="submit" class="mt-auto flex items-center justify-center gap-3 w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-semibold text-lg shadow-lg hover:shadow-xl">
                            <i class="fas fa-plus-circle"></i>
                            Create Interview Room
                        </button>
                    </form>
                    
                    <!-- Room Info Panel -->
                    <div id="hostRoomInfo" class="mt-8 bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-6 border-2 border-dashed border-blue-400 hidden animate-fade-in">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center">
                                <i class="fas fa-link text-lg"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Room Created Successfully!</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Room ID</h4>
                                <div class="bg-white px-4 py-3 rounded-lg border border-gray-300 font-mono font-semibold text-blue-700">
                                    <span id="roomId">INV-8X7A-2B9C</span>
                                </div>
                            </div>
                            <div>
                                <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Host Name</h4>
                                <p id="displayHostName" class="text-lg font-medium text-gray-800">John Doe</p>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Share Interview Link</h4>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <input type="text" id="roomLink" 
                                       class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl focus:outline-none focus:border-blue-500 bg-white"
                                       readonly>
                                <button onclick="copyLink()" 
                                        class="flex items-center justify-center gap-2 px-6 py-3 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition-colors font-semibold whitespace-nowrap">
                                    <i class="fas fa-copy"></i>
                                    Copy Link
                                </button>
                            </div>
                        </div>
                        
                        <div class="text-center mt-6">
                            <p class="text-gray-600">
                                Redirecting to interview room in 
                                <span id="countdown" class="text-blue-600 font-bold text-lg ml-1">3</span> 
                                seconds...
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Participant Card -->
                <div class="bg-white rounded-2xl p-8 shadow-xl border border-gray-200 card-hover flex flex-col">
                    <div class="w-18 h-18 bg-gradient-to-br from-cyan-50 to-green-50 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-users text-3xl text-green-600"></i>
                    </div>
                    
                    <h2 class="text-2xl font-semibold text-gray-800 mb-3">Join as Participant</h2>
                    <p class="text-gray-600 mb-8 text-lg">
                        Enter the room ID provided by your host to join an existing interview session.
                    </p>
                    
                    <form id="joinForm" class="flex-1 flex flex-col">
                        <div class="mb-6">
                            <label for="participantName" class="block text-gray-700 font-medium mb-2 text-sm uppercase tracking-wider">
                                Your Name
                            </label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="participantName" 
                                       class="w-full pl-12 pr-4 py-4 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:outline-none input-focus transition-colors"
                                       placeholder="Enter your full name"
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-8">
                            <label for="roomCode" class="block text-gray-700 font-medium mb-2 text-sm uppercase tracking-wider">
                                Room ID
                            </label>
                            <div class="relative">
                                <i class="fas fa-door-closed absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="roomCode" 
                                       class="w-full pl-12 pr-4 py-4 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:outline-none input-focus transition-colors"
                                       placeholder="Enter room ID (e.g., INV-8X7A-2B9C)"
                                       required>
                            </div>
                        </div>
                        
                        <button type="submit" class="mt-auto flex items-center justify-center gap-3 w-full py-4 px-6 bg-gradient-to-r from-cyan-500 to-green-500 text-white rounded-xl hover:from-cyan-600 hover:to-green-600 transition-all duration-300 font-semibold text-lg shadow-lg hover:shadow-xl">
                            <i class="fas fa-sign-in-alt"></i>
                            Join Interview Room
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="text-center mb-8">
                <p class="text-gray-500 text-lg">
                    <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                    All interviews are encrypted and secure. Your data is protected.
                </p>
            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center py-8 border-t border-gray-200 mt-8">
            <p class="text-gray-500 mb-4">&copy; 2026 CodeMeet. All rights reserved.</p>
            <div class="flex flex-wrap justify-center gap-6">
                <a href="#" class="text-gray-500 hover:text-blue-600 transition-colors font-medium">Privacy Policy</a>
                <a href="#" class="text-gray-500 hover:text-blue-600 transition-colors font-medium">Terms of Service</a>
                <a href="#" class="text-gray-500 hover:text-blue-600 transition-colors font-medium">Support</a>
                <a href="#" class="text-gray-500 hover:text-blue-600 transition-colors font-medium">Documentation</a>
            </div>
        </footer>
    </div>

    <script>
        // Your original logic - unchanged
        document.getElementById('hostForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const hostName = document.getElementById('hostName').value;
            
            // Update display name in room info
            document.getElementById('displayHostName').textContent = hostName;
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Room...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('api/create-room.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({host_name: hostName})
                });
                
                const data = await response.json();
                if(data.success) {
                    document.getElementById('roomId').textContent = data.room_id;
                    const roomLink = `${window.location.origin}/participant.php?room=${data.room_id}`;
                    document.getElementById('roomLink').value = roomLink;
                    document.getElementById('hostRoomInfo').classList.remove('hidden');
                    
                    // Start countdown
                    let countdown = 30;
                    const countdownElement = document.getElementById('countdown');
                    const countdownInterval = setInterval(() => {
                        countdown--;
                        countdownElement.textContent = countdown;
                        
                        if (countdown <= 0) {
                            clearInterval(countdownInterval);
                            // Redirect to host page
                            window.location.href = `host.php?room=${data.room_id}&name=${encodeURIComponent(hostName)}`;
                        }
                    }, 1000);
                } else {
                    alert('Failed to create room. Please try again.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please check your connection and try again.');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        document.getElementById('joinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const participantName = document.getElementById('participantName').value;
            const roomCode = document.getElementById('roomCode').value.trim();
            
            if (!participantName || !roomCode) {
                alert('Please fill in all fields');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining...';
            submitBtn.disabled = true;
            
            // Simulate API check (you can add actual validation here)
            setTimeout(() => {
                window.location.href = `participant.php?room=${roomCode}&name=${encodeURIComponent(participantName)}`;
            }, 1000);
        });

        function copyLink() {
            const linkInput = document.getElementById('roomLink');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand('copy');
            
            // Visual feedback
            const copyBtn = document.querySelector('button[onclick="copyLink()"]');
            const originalHtml = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            copyBtn.className = 'flex items-center justify-center gap-2 px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors font-semibold whitespace-nowrap';
            
            setTimeout(() => {
                copyBtn.innerHTML = originalHtml;
                copyBtn.className = 'flex items-center justify-center gap-2 px-6 py-3 bg-gray-800 text-white rounded-xl hover:bg-gray-900 transition-colors font-semibold whitespace-nowrap';
            }, 2000);
        }

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add focus effects to inputs
            const inputs = document.querySelectorAll('input[type="text"]');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
            
            // Add hover effect to cards on load
            setTimeout(() => {
                document.querySelectorAll('.card-hover').forEach(card => {
                    card.style.opacity = '1';
                });
            }, 100);
        });
    </script>
</body>
</html>
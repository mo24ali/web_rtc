<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Room</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Interview Room</h1>
        
        <div class="card-container">
            <!-- Host Card -->
            <div class="card">
                <h2>Host an Interview</h2>
                <p>Create a new interview room</p>
                <form id="hostForm">
                    <input type="text" id="hostName" placeholder="Your Name" required>
                    <button type="submit" class="btn-primary">Create Room</button>
                </form>
                <div id="hostRoomInfo" style="display:none; margin-top:15px;">
                    <p>Room ID: <strong id="roomId"></strong></p>
                    <p>Share this link with participants:</p>
                    <input type="text" id="roomLink" readonly>
                    <button onclick="copyLink()">Copy Link</button>
                </div>
            </div>

            <!-- Participant Card -->
            <div class="card">
                <h2>Join as Participant</h2>
                <p>Enter room ID to join</p>
                <form id="joinForm">
                    <input type="text" id="participantName" placeholder="Your Name" required>
                    <input type="text" id="roomCode" placeholder="Room ID" required>
                    <button type="submit" class="btn-secondary">Join Room</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('hostForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const hostName = document.getElementById('hostName').value;
            
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
                document.getElementById('hostRoomInfo').style.display = 'block';
                
                // Redirect to host page
                setTimeout(() => {
                    window.location.href = `host.php?room=${data.room_id}&name=${encodeURIComponent(hostName)}`;
                }, 3000);
            }
        });

        document.getElementById('joinForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const participantName = document.getElementById('participantName').value;
            const roomCode = document.getElementById('roomCode').value;
            
            window.location.href = `participant.php?room=${roomCode}&name=${encodeURIComponent(participantName)}`;
        });

        function copyLink() {
            const linkInput = document.getElementById('roomLink');
            linkInput.select();
            document.execCommand('copy');
            alert('Link copied to clipboard!');
        }
    </script>
</body>
</html>
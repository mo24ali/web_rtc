class ParticipantWebRTC {
    constructor(roomId, userName) {
        this.roomId = roomId;
        this.userName = userName;
        this.peerConnections = {};
        this.localStream = null;
        this.socket = null;
        this.configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:global.stun.twilio.com:3478' }
            ]
        };
        
        this.init();
    }

    async init() {
        await this.initLocalStream();
        this.initSocket();
        this.setupEventListeners();
    }

    async initLocalStream() {
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            
            const localVideo = document.getElementById('localVideo');
            localVideo.srcObject = this.localStream;
        } catch (error) {
            console.error('Error accessing media devices:', error);
        }
    }

    initSocket() {
        this.socket = new WebSocket('ws://localhost:8080');
        
        this.socket.onopen = () => {
            console.log('Connected to signaling server');
            this.socket.send(JSON.stringify({
                type: 'participant',
                room: this.roomId,
                name: this.userName
            }));
        };

        this.socket.onmessage = async (event) => {
            const message = JSON.parse(event.data);
            console.log('Message received:', message);
            
            switch(message.type) {
                case 'offer':
                    await this.handleOffer(message);
                    break;
                case 'answer':
                    await this.handleAnswer(message);
                    break;
                case 'ice-candidate':
                    await this.handleICECandidate(message);
                    break;
                case 'newParticipant':
                    await this.handleNewParticipant(message.participantId, message.name);
                    break;
                case 'endInterview':
                    this.handleInterviewEnded();
                    break;
            }
        };
    }

    async handleOffer(message) {
        // Create peer connection for host
        const peerConnection = new RTCPeerConnection(this.configuration);
        this.peerConnections[message.from] = peerConnection;
        
        // Add local stream
        this.localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, this.localStream);
        });
        
        // Setup ICE candidate
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.socket.send(JSON.stringify({
                    type: 'ice-candidate',
                    target: message.from,
                    candidate: event.candidate,
                    room: this.roomId
                }));
            }
        };
        
        // Setup remote stream (host video)
        peerConnection.ontrack = (event) => {
            const hostVideo = document.getElementById('hostVideo');
            if (hostVideo && !hostVideo.srcObject) {
                hostVideo.srcObject = event.streams[0];
            }
        };
        
        // Set remote description and create answer
        await peerConnection.setRemoteDescription(new RTCSessionDescription(message.offer));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        
        this.socket.send(JSON.stringify({
            type: 'answer',
            target: message.from,
            answer: answer,
            room: this.roomId
        }));
    }

    async handleAnswer(message) {
        const peerConnection = this.peerConnections[message.from];
        if (peerConnection) {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(message.answer));
        }
    }

    async handleICECandidate(message) {
        const peerConnection = this.peerConnections[message.from];
        if (peerConnection && message.candidate) {
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(message.candidate));
            } catch (error) {
                console.error('Error adding ICE candidate:', error);
            }
        }
    }

    async handleNewParticipant(participantId, name) {
        // This handles connections to other participants (optional)
        if (participantId !== this.socket.id) {
            // Similar to host logic for peer-to-peer between participants
        }
    }

    handleInterviewEnded() {
        alert('The host has ended the interview.');
        this.leaveRoom();
    }

    setupEventListeners() {
        document.getElementById('toggleVideo').addEventListener('click', () => {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
            }
        });

        document.getElementById('toggleAudio').addEventListener('click', () => {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
            }
        });

        document.getElementById('leaveRoom').addEventListener('click', () => {
            if (confirm('Leave the interview room?')) {
                this.leaveRoom();
            }
        });
    }

    leaveRoom() {
        // Close all connections
        Object.values(this.peerConnections).forEach(pc => pc.close());
        
        // Stop local stream
        this.localStream.getTracks().forEach(track => track.stop());
        
        // Notify server
        if (this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify({
                type: 'leave',
                room: this.roomId
            }));
        }
        
        // Redirect to home
        window.location.href = 'index.php';
    }
}

function initializeParticipant(roomId, userName) {
    window.participantRTC = new ParticipantWebRTC(roomId, userName);
}
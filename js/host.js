class HostWebRTC {
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
        // Connect to WebSocket server
        this.socket = new WebSocket('ws://localhost:8080');
        
        this.socket.onopen = () => {
            console.log('Connected to signaling server');
            this.socket.send(JSON.stringify({
                type: 'host',
                room: this.roomId,
                name: this.userName
            }));
        };

        this.socket.onmessage = async (event) => {
            const message = JSON.parse(event.data);
            console.log('Message received:', message);
            
            switch(message.type) {
                case 'newParticipant':
                    await this.handleNewParticipant(message.participantId, message.name);
                    break;
                case 'offer':
                    await this.handleOffer(message);
                    break;
                case 'answer':
                    await this.handleAnswer(message);
                    break;
                case 'ice-candidate':
                    await this.handleICECandidate(message);
                    break;
                case 'participantLeft':
                    this.handleParticipantLeft(message.participantId);
                    break;
            }
        };
    }

    async handleNewParticipant(participantId, name) {
        // Create peer connection
        const peerConnection = new RTCPeerConnection(this.configuration);
        this.peerConnections[participantId] = peerConnection;
        
        // Add local stream to connection
        this.localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, this.localStream);
        });
        
        // Setup ICE candidate handling
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.socket.send(JSON.stringify({
                    type: 'ice-candidate',
                    target: participantId,
                    candidate: event.candidate,
                    room: this.roomId
                }));
            }
        };
        
        // Setup remote stream handling
        peerConnection.ontrack = (event) => {
            const remoteVideo = document.createElement('video');
            remoteVideo.id = `remoteVideo_${participantId}`;
            remoteVideo.autoplay = true;
            remoteVideo.playsinline = true;
            remoteVideo.srcObject = event.streams[0];
            
            const videoWrapper = document.createElement('div');
            videoWrapper.className = 'video-wrapper';
            videoWrapper.innerHTML = `<h3>${name}</h3>`;
            videoWrapper.appendChild(remoteVideo);
            
            document.getElementById('remoteVideos').appendChild(videoWrapper);
            
            // Add to participants list
            this.addToParticipantsList(participantId, name);
        };
        
        // Create and send offer
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        
        this.socket.send(JSON.stringify({
            type: 'offer',
            target: participantId,
            offer: offer,
            room: this.roomId
        }));
    }

    async handleOffer(message) {
        const peerConnection = this.peerConnections[message.from];
        if (peerConnection) {
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

    handleParticipantLeft(participantId) {
        const peerConnection = this.peerConnections[participantId];
        if (peerConnection) {
            peerConnection.close();
            delete this.peerConnections[participantId];
        }
        
        // Remove video element
        const videoElement = document.getElementById(`remoteVideo_${participantId}`);
        if (videoElement) {
            videoElement.parentElement.remove();
        }
        
        // Remove from participants list
        this.removeFromParticipantsList(participantId);
    }

    addToParticipantsList(participantId, name) {
        const listItem = document.createElement('li');
        listItem.id = `participant_${participantId}`;
        listItem.textContent = name;
        document.getElementById('participantsList').appendChild(listItem);
    }

    removeFromParticipantsList(participantId) {
        const listItem = document.getElementById(`participant_${participantId}`);
        if (listItem) {
            listItem.remove();
        }
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

        document.getElementById('endInterview').addEventListener('click', () => {
            if (confirm('End interview for all participants?')) {
                this.socket.send(JSON.stringify({
                    type: 'endInterview',
                    room: this.roomId
                }));
                
                // Close all connections
                Object.values(this.peerConnections).forEach(pc => pc.close());
                this.peerConnections = {};
                
                // Redirect to home page
                window.location.href = 'index.php';
            }
        });
    }
}

function initializeHost(roomId, userName) {
    window.hostRTC = new HostWebRTC(roomId, userName);
}
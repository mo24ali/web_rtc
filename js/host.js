class HostWebRTC {
    constructor(roomId, userName) {
        this.roomId = roomId;
        this.userName = userName;
        this.peerConnections = {};
        this.dataChannels = {};
        this.localStream = null;
        this.socket = null;
        this.configuration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:global.stun.twilio.com:3478' }
            ]
        };
        
        this.editor = null;
        this.isReceiving = false;
        
        this.init();
    }

    async init() {
        this.initEditor();
        await this.initLocalStream();
        this.initSocket();
        this.setupEventListeners();
    }

    initEditor() {
        this.editor = CodeMirror.fromTextArea(document.getElementById('code-editor'), {
            mode: 'javascript',
            theme: 'dracula',
            lineNumbers: true,
            autoCloseBrackets: true,
            matchBrackets: true
        });

        this.editor.on('change', (cm, change) => {
            if (!this.isReceiving) {
                const content = cm.getValue();
                this.broadcastCode(content);
            }
        });
    }

    broadcastCode(content) {
        Object.values(this.dataChannels).forEach(channel => {
            if (channel.readyState === 'open') {
                channel.send(content);
            }
        });
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
        
        // Create Data Channel
        const dataChannel = peerConnection.createDataChannel("code-editor");
        this.dataChannels[participantId] = dataChannel;
        
        dataChannel.onopen = () => {
            console.log(`Data channel open with ${name}`);
            // Send current code to new participant
            if (this.editor) {
                dataChannel.send(this.editor.getValue());
            }
        };

        dataChannel.onmessage = (event) => {
            if (this.editor) {
                this.isReceiving = true;
                const cursor = this.editor.getCursor();
                this.editor.setValue(event.data);
                this.editor.setCursor(cursor);
                this.isReceiving = false;
                
                // Re-broadcast to other participants (except sender)
                Object.entries(this.dataChannels).forEach(([id, channel]) => {
                    if (id !== participantId && channel.readyState === 'open') {
                        channel.send(event.data);
                    }
                });
            }
        };
        
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

        if (this.dataChannels[participantId]) {
            this.dataChannels[participantId].close();
            delete this.dataChannels[participantId];
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
                
                Object.values(this.peerConnections).forEach(pc => pc.close());
                this.peerConnections = {};
                
                window.location.href = 'index.php';
            }
        });
    }
}

function initializeHost(roomId, userName) {
    window.hostRTC = new HostWebRTC(roomId, userName);
}
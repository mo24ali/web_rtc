import { FirestoreSignaling } from './signaling-firestore.js';

export class ParticipantWebRTC {
    constructor(roomId, userName) {
        this.roomId = roomId;
        this.userName = userName;
        this.peerConnections = {}; // Keyed by 'host' usually, or other participant IDs if full mesh
        this.hostId = 'host'; // Identifying the host connection

        this.localStream = null;
        this.signaling = new FirestoreSignaling(roomId, userName, false);

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
    // ... rest of class remains valid ...

    async init() {
        this.initEditor();
        await this.initLocalStream();
        await this.initSignaling();
        this.setupEventListeners();
    }

    initEditor() {
        const editorElement = document.getElementById('code-editor');
        if (!editorElement) {
            console.error('Code editor element not found');
            return;
        }

        this.editor = CodeMirror.fromTextArea(editorElement, {
            mode: 'javascript',
            theme: 'dracula',
            lineNumbers: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            readOnly: false // Participants can edit by default
        });

        // Participants can edit but changes will be sent to host
        this.editor.on('change', (cm, change) => {
            if (!this.isReceiving && !cm.getOption('readOnly')) {
                const content = cm.getValue();
                this.signaling.sendCodeUpdate(content);
            }
        });

    sendCodeUpdate(content) {
        // Send to host and all other participants
        Object.values(this.dataChannels).forEach(channel => {
            if (channel.readyState === 'open') {
                channel.send(content);
            }
        });
    }

    async initLocalStream() {
        try {
            // Request camera and microphone permissions
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                },
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });

            const localVideo = document.getElementById('localVideo');
            if (localVideo) {
                localVideo.srcObject = this.localStream;
                // Show video status
                this.updateVideoStatus(true);
            }
        } catch (error) {
            console.error('Error accessing media devices:', error);
            this.handleMediaError(error);
        }
    }

    handleMediaError(error) {
        let message = 'Unable to access camera and microphone. ';

        switch(error.name) {
            case 'NotFoundError':
                message += 'No camera or microphone found.';
                break;
            case 'NotAllowedError':
                message += 'Camera and microphone access denied. Please allow access and refresh the page.';
                break;
            case 'NotSupportedError':
                message += 'Your browser does not support camera and microphone access.';
                break;
            case 'NotReadableError':
                message += 'Camera or microphone is already in use by another application.';
                break;
            default:
                message += 'Please check your camera and microphone settings.';
                break;
        }

        // Show error message to user
        this.showErrorMessage(message);

        // Disable video controls
        this.updateVideoStatus(false);
        this.updateAudioStatus(false);
    }

    showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg shadow-lg z-50 max-w-md';
        errorDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span class="font-medium">Camera Error</span>
                <button class="ml-auto text-red-700 hover:text-red-900" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="mt-2 text-sm">${message}</p>
        `;
        document.body.appendChild(errorDiv);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            if (errorDiv.parentElement) {
                errorDiv.remove();
            }
        }, 10000);
    }

    updateVideoStatus(enabled) {
        const statusElement = document.getElementById('videoStatus');
        if (statusElement) {
            statusElement.textContent = enabled ? 'ON' : 'OFF';
            statusElement.className = enabled
                ? 'bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full'
                : 'bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
        }
    }

    updateAudioStatus(enabled) {
        const statusElement = document.getElementById('audioStatus');
        if (statusElement) {
            statusElement.textContent = enabled ? 'ON' : 'OFF';
            statusElement.className = enabled
                ? 'bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full'
                : 'bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full';
        }
    }

    showNotification(message, type = 'info') {
        const colors = {
            success: 'bg-green-100 border-green-400 text-green-700',
            warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700'
        };

        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${colors[type]} border px-4 py-3 rounded-lg shadow-lg z-50 max-w-md`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                <span class="font-medium">Notification</span>
                <button class="ml-auto hover:opacity-75" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="mt-2 text-sm">${message}</p>
        `;
        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    initSocket() {
        this.socket = new WebSocket('ws://localhost:8080');

        this.signaling.on('ice-candidate', async (candidate) => {
            console.log('Received ICE candidate from host');
            await this.handleICECandidate(candidate);
        });

        this.socket.onmessage = async (event) => {
            const message = JSON.parse(event.data);
            console.log('Message received:', message);

            switch (message.type) {
                case 'hostInfo':
                    this.handleHostInfo(message);
                    break;
                case 'existingParticipants':
                    await this.handleExistingParticipants(message.participants);
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
                case 'newParticipant':
                    await this.handleNewParticipant(message.participantId, message.name);
                    break;
                case 'participantLeft':
                    this.handleParticipantLeft(message.participantId);
                    break;
                case 'endInterview':
                    this.handleInterviewEnded();
                    break;
                case 'removedByHost':
                    this.handleRemovedByHost();
                    break;
                case 'mediaStatusUpdate':
                    this.handleMediaStatusUpdate(message);
                    break;
                case 'hostDisconnected':
                    this.handleHostDisconnected();
                    break;
            }
        };
    }

    async handleOffer(offer) {
        console.log('Handling offer');
        const peerConnection = new RTCPeerConnection(this.configuration);
        this.peerConnections[message.from] = peerConnection;

        // Listen for Data Channel
        peerConnection.ondatachannel = (event) => {
            const dataChannel = event.channel;
            this.dataChannels[message.from] = dataChannel;

            console.log("Data channel received from host");

            dataChannel.onopen = () => {
                console.log('Data channel opened with host');
                // Request current code from host
                dataChannel.send('REQUEST_CODE');
            };

            dataChannel.onmessage = (event) => {
                if (this.editor) {
                    if (event.data === 'ENABLE_EDITING') {
                        // Host has enabled editing for participants
                        this.editor.setOption('readOnly', false);
                        this.showNotification('Host has enabled code editing', 'success');
                        console.log('Participant editing enabled');
                    } else if (event.data === 'DISABLE_EDITING') {
                        // Host has disabled editing for participants
                        this.editor.setOption('readOnly', true);
                        this.showNotification('Host has disabled code editing', 'warning');
                        console.log('Participant editing disabled');
                    } else if (event.data !== 'REQUEST_CODE') {
                        this.isReceiving = true;
                        const cursor = this.editor.getCursor();
                        this.editor.setValue(event.data);
                        this.editor.setCursor(cursor);
                        this.isReceiving = false;

                        // Make editor editable after receiving initial code (if not disabled by host)
                        if (this.editor.getOption('readOnly') && event.data !== 'DISABLE_EDITING') {
                            // Only enable if host hasn't explicitly disabled editing
                            this.editor.setOption('readOnly', false);
                        }
                    }
                }
            };

            dataChannel.onclose = () => {
                console.log('Data channel closed');
                if (this.editor) {
                    this.editor.setOption('readOnly', true);
                }
            };
        };

        // Add local tracks
        this.localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, this.localStream);
        });

        // Handle ICE candidates
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                // Send to host (targetId is actually not used in method implementation for participant sending to host, 
                // but we pass something or method logic handles it based on isHost flag)
                this.signaling.sendIceCandidate('host', event.candidate);
            }
        };

        // Handle remote stream (Host video)
        peerConnection.ontrack = (event) => {
            console.log('Received host track');
            const hostVideo = document.getElementById('hostVideo');
            if (hostVideo) {
                hostVideo.srcObject = event.streams[0];

                // Hide waiting state
                const hostWaiting = document.getElementById('hostWaiting');
                if (hostWaiting) hostWaiting.style.display = 'none';

                // Update Host Info UI
                const container = hostVideo.closest('.bg-white'); // parent card
                if (container) {
                    const statusText = container.querySelector('.text-blue-100');
                    if (statusText) statusText.textContent = 'Connected';
                }
            }
        };

        try {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            console.log('Sending answer');
            await this.signaling.sendAnswer(answer);
        } catch (e) {
            console.error('Error handling offer:', e);
        }
    }

    async handleParticipantOffer(message) {
        // Create peer connection for incoming participant offer
        const peerConnection = new RTCPeerConnection(this.configuration);
        this.peerConnections[message.from] = peerConnection;

        // Listen for data channel from the other participant
        peerConnection.ondatachannel = (event) => {
            const receivedDataChannel = event.channel;
            this.dataChannels[message.from] = receivedDataChannel;

            console.log(`Received data channel from peer participant ${message.fromName}`);

            receivedDataChannel.onmessage = (event) => {
                if (this.editor && event.data !== 'REQUEST_CODE') {
                    this.isReceiving = true;
                    const cursor = this.editor.getCursor();
                    this.editor.setValue(event.data);
                    this.editor.setCursor(cursor);
                    this.isReceiving = false;

                    // Re-broadcast to other participants (except sender)
                    Object.entries(this.dataChannels).forEach(([id, channel]) => {
                        if (id !== message.from && channel.readyState === 'open') {
                            channel.send(event.data);
                        }
                    });
                }
            };
        };

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

        // Setup remote stream for participant video
        peerConnection.ontrack = (event) => {
            const remoteVideo = document.createElement('video');
            remoteVideo.id = `remoteVideo_${message.from}`;
            remoteVideo.autoplay = true;
            remoteVideo.playsinline = true;
            remoteVideo.srcObject = event.streams[0];
            remoteVideo.className = 'w-full h-full object-cover rounded-lg';

            // Add to participants list
            this.addParticipantVideo(message.from, message.fromName, remoteVideo);
        };

        // Set remote description and create answer
        await peerConnection.setRemoteDescription(new RTCSessionDescription(message.offer));
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);

        this.socket.send(JSON.stringify({
            type: 'participant-answer',
            target: message.from,
            answer: answer,
            room: this.roomId
        }));
    }

    async handleParticipantAnswer(message) {
        const peerConnection = this.peerConnections[message.from];
        if (peerConnection) {
            await peerConnection.setRemoteDescription(new RTCSessionDescription(message.answer));
        }
    }

    async handleExistingParticipants(participants) {
        console.log('Adding existing participants to UI:', participants);
        for (const participant of participants) {
            // Add participant to the UI list (they'll be visible through host's video feed)
            this.addParticipantToList(participant.socket_id, participant.participant_name);
        }
        this.updateParticipantCount();
    }

    addParticipantToList(participantId, name) {
        // Add participant to the UI list for display purposes
        const otherParticipants = document.getElementById('otherParticipants');
        if (!otherParticipants) return;

        // Check if already exists
        if (document.getElementById(`participant-card-${participantId}`)) return;

        // Create participant card
        const participantCard = document.createElement('div');
        participantCard.className = 'bg-gray-50 rounded-xl p-4 border border-gray-200 hover:border-blue-300 transition-colors';
        participantCard.id = `participant-card-${participantId}`;

        participantCard.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="relative">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold">${name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-800 truncate">${name}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Connected</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 bg-gray-800 rounded-lg aspect-video flex items-center justify-center">
                <div class="text-center text-gray-400">
                    <i class="fas fa-video text-2xl mb-2"></i>
                    <p class="text-sm">Video available through host</p>
                </div>
            </div>
        `;

        otherParticipants.appendChild(participantCard);
    }

    async handleICECandidate(message) {
        const peerConnection = this.peerConnections[message.from];
        if (peerConnection && message.candidate) {
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
            } catch (e) {
                console.error('Error adding ICE candidate:', e);
            }
        }
    }

    async handleNewParticipant(participantId, name) {
        console.log(`👤 New participant joined: ${name} (${participantId})`);
        // Add to UI list
        this.addParticipantToList(participantId, name);
        this.updateParticipantCount();
    }

    handleInterviewEnded() {
        alert('The host has ended the interview.');
        this.leaveRoom();
    }

    handleHostInfo(message) {
        console.log('Host info received:', message);
        // Update host status in UI
        const hostElement = document.getElementById('hostVideo');
        const hostWaiting = document.getElementById('hostWaiting');
        const hostHeader = hostElement.closest('.bg-white').querySelector('h2');

        if (hostElement && hostWaiting) {
            hostWaiting.innerHTML = `
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-user-tie text-blue-600 text-3xl"></i>
                </div>
                <p class="text-gray-300 font-medium">${message.hostName} (Host)</p>
                <p class="text-gray-400 text-sm mt-2">Host is connected and ready</p>
            `;
        }

        // Update status indicators
        this.updateHostStatus(true, message.hostName);
    }

    handleParticipantLeft(participantId) {
        // Remove participant from UI
        const participantCard = document.getElementById(`participant-card-${participantId}`);
        if (participantCard) {
            participantCard.remove();
            this.updateParticipantCount();
        }

        // Close peer connection if exists
        if (this.peerConnections[participantId]) {
            this.peerConnections[participantId].close();
            delete this.peerConnections[participantId];
        }

        if (this.dataChannels[participantId]) {
            this.dataChannels[participantId].close();
            delete this.dataChannels[participantId];
        }
    }

    handleRemovedByHost() {
        alert('You have been removed from the interview by the host.');
        this.leaveRoom();
    }

    handleMediaStatusUpdate(message) {
        // Update participant status in UI
        const participantCard = document.getElementById(`participant-card-${message.participantId}`);
        if (participantCard) {
            const statusContainer = participantCard.querySelector('.flex.items-center.gap-2');
            if (statusContainer && statusContainer.children.length >= 2) {
                const videoSpan = statusContainer.children[0];
                const audioSpan = statusContainer.children[1];

                if (message.mediaType === 'video') {
                    videoSpan.className = `text-xs ${message.enabled ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'} px-2 py-0.5 rounded`;
                    videoSpan.textContent = `Video ${message.enabled ? 'ON' : 'OFF'}`;
                } else if (message.mediaType === 'audio') {
                    audioSpan.className = `text-xs ${message.enabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'} px-2 py-0.5 rounded`;
                    audioSpan.textContent = `Audio ${message.enabled ? 'ON' : 'OFF'}`;
                }
            }
        }
    }

    handleHostDisconnected() {
        alert('The host has disconnected. The interview session has ended.');
        this.updateHostStatus(false);
        // Optionally redirect after a delay
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 3000);
    }

    updateHostStatus(isConnected, hostName = 'Host') {
        const hostStatus = document.querySelector('.bg-green-50.px-3.py-1.5.rounded-lg');
        if (hostStatus) {
            if (isConnected) {
                hostStatus.innerHTML = `
                    <i class="fas fa-circle text-green-500 text-xs"></i>
                    <span class="text-green-700 font-medium text-sm">${hostName} - Connected</span>
                `;
            } else {
                hostStatus.innerHTML = `
                    <i class="fas fa-circle text-red-500 text-xs"></i>
                    <span class="text-red-700 font-medium text-sm">Host Disconnected</span>
                `;
            }
        }
    }

    setupEventListeners() {
        document.getElementById('toggleVideo').addEventListener('click', () => {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                this.updateVideoStatus(videoTrack.enabled);
                this.updateMediaStatusInDB('video', videoTrack.enabled);
            }
        });

        document.getElementById('toggleAudio').addEventListener('click', () => {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                this.updateAudioStatus(audioTrack.enabled);
                this.updateMediaStatusInDB('audio', audioTrack.enabled);
            }
        });

        document.getElementById('leaveRoom').addEventListener('click', async () => {
            if (confirm('Leave the interview room?')) {
                await this.signaling.leaveRoom();
                window.location.href = 'index.php';
            }
        });
    }

    addParticipantVideo(participantId, name, videoElement) {
        const participantsContainer = document.getElementById('otherParticipants');
        if (!participantsContainer) return;

        // Remove empty state if it exists
        const emptyState = participantsContainer.querySelector('.col-span-full');
        if (emptyState) {
            emptyState.remove();
        }

        // Create participant card
        const participantCard = document.createElement('div');
        participantCard.className = 'bg-gray-50 rounded-xl p-4 border border-gray-200 hover:border-blue-300 transition-colors';
        participantCard.id = `participant-card-${participantId}`;
        participantCard.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="relative">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold">${name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white rounded-full"></div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-800 truncate">${name}</h4>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Video ON</span>
                        <span class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Audio ON</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 bg-gray-900 rounded-lg aspect-video relative overflow-hidden">
                <div id="video-container-${participantId}" class="w-full h-full flex items-center justify-center">
                    <i class="fas fa-user text-white text-4xl"></i>
                </div>
            </div>
        `;

        participantsContainer.appendChild(participantCard);

        // Add video to container
        const videoContainer = document.getElementById(`video-container-${participantId}`);
        if (videoContainer) {
            videoContainer.innerHTML = '';
            videoContainer.appendChild(videoElement);
        }

        // Update participant count
        this.updateParticipantCount();
    }

    updateParticipantCount() {
        const participantCards = document.querySelectorAll('#otherParticipants > div:not(.hidden)');
        const count = participantCards.length;
        const countElement = document.getElementById('participantCount');
        if (countElement) {
            countElement.textContent = `${count} online`;
        }
    }

    updateMediaStatusInDB(mediaType, enabled) {
        // Send status update to server
        this.socket.send(JSON.stringify({
            type: 'updateMediaStatus',
            mediaType: mediaType,
            enabled: enabled,
            room: this.roomId
        }));
    }

    leaveRoom() {
        try {
            // Close all connections
            Object.values(this.peerConnections).forEach(pc => {
                if (pc.signalingState !== 'closed') {
                    pc.close();
                }
            });
            Object.values(this.dataChannels).forEach(dc => {
                if (dc.readyState !== 'closed') {
                    dc.close();
                }
            });

            // Stop local stream
            if (this.localStream) {
                this.localStream.getTracks().forEach(track => {
                    track.stop();
                });
            }

            // Close socket connection
            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                this.socket.send(JSON.stringify({
                    type: 'leave',
                    room: this.roomId
                }));
                this.socket.close();
            }

            // Clear references
            this.peerConnections = {};
            this.dataChannels = {};
            this.localStream = null;

            console.log('Successfully left the room');
        } catch (error) {
            console.error('Error leaving room:', error);
        }

        window.addEventListener('beforeunload', () => {
            this.signaling.leaveRoom();
        });
    }
}


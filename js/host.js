import { FirestoreSignaling } from './signaling-firestore.js';

export class HostWebRTC {
    constructor(roomId, userName) {
        this.roomId = roomId;
        this.userName = userName;
        this.peerConnections = {};
        this.dataChannels = {};

        this.localStream = null;
        this.signaling = new FirestoreSignaling(roomId, userName, true);

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
    // ... rest of class logic stays implicitly same if I don't touch it?
    // Wait, replace_file_content replaces the BLOCK.
    // I need to be careful not to delete the methods.
    // The previous view_file showed lines 3 to 308. 
    // I will use multi_replace to target the start and end only.


    async init() {
        this.initEditor();
        await this.initLocalStream();
        await this.initSignaling();
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

        // Listen for local changes
        this.editor.on('change', (cm, change) => {
            if (!this.isReceiving) {
                const content = cm.getValue();
                this.signaling.sendCodeUpdate(content);
            }
        });

        // Listen for remote changes
        this.signaling.on('codeUpdate', (content) => {
            if (this.editor.getValue() !== content) {
                this.isReceiving = true;
                const cursor = this.editor.getCursor();
                this.editor.setValue(content);
                this.editor.setCursor(cursor);
                this.isReceiving = false;
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
                ? 'bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full'
                : 'bg-red-100 text-red-800 text-xs font-bold px-2.5 py-1 rounded-full';
        }
    }

    updateAudioStatus(enabled) {
        const statusElement = document.getElementById('audioStatus');
        if (statusElement) {
            statusElement.textContent = enabled ? 'ON' : 'OFF';
            statusElement.className = enabled
                ? 'bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full'
                : 'bg-red-100 text-red-800 text-xs font-bold px-2.5 py-1 rounded-full';
        }
    }

    async initSignaling() {
        // Setup event listeners
        this.signaling.on('newParticipant', async (participant) => {
            console.log('New participant:', participant);
            await this.handleNewParticipant(participant.id, participant.name);
        });

        this.socket.onmessage = async (event) => {
            const message = JSON.parse(event.data);
            console.log('Message received:', message);

            switch(message.type) {
                case 'currentParticipants':
                    this.handleCurrentParticipants(message.participants);
                    break;
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
                case 'mediaStatusUpdate':
                    this.handleMediaStatusUpdate(message);
                    break;
                case 'participantStatusUpdate':
                    this.handleParticipantStatusUpdate(message);
                    break;
            }
        };
    }

    async handleNewParticipant(participantId, name) {
        if (this.peerConnections[participantId]) return;

        console.log(`Creating PeerConnection for ${name} (${participantId})`);
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
            // Enable editing for the participant
            setTimeout(() => {
                dataChannel.send('ENABLE_EDITING');
            }, 1000);
        };

        dataChannel.onmessage = (event) => {
            if (event.data === 'REQUEST_CODE') {
                // Send current code to requesting participant
                if (this.editor) {
                    dataChannel.send(this.editor.getValue());
                }
            } else if (this.editor) {
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

        // Handle ICE candidates
        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                this.signaling.sendIceCandidate(participantId, event.candidate);
            }
        };

        // Handle remote stream
        peerConnection.ontrack = (event) => {
            console.log('Received remote track from', name);

            let videoWrapper = document.getElementById(`wrapper_${participantId}`);
            let remoteVideo = document.getElementById(`remoteVideo_${participantId}`);

            if (!remoteVideo) {
                remoteVideo = document.createElement('video');
                remoteVideo.id = `remoteVideo_${participantId}`;
                remoteVideo.autoplay = true;
                remoteVideo.playsinline = true;
                remoteVideo.className = "w-full h-full object-cover";

                videoWrapper = document.createElement('div');
                videoWrapper.id = `wrapper_${participantId}`;
                videoWrapper.className = 'bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200';
                videoWrapper.innerHTML = `
                    <div class="relative bg-gray-800 aspect-video">
                        <div class="video-container h-full"></div>
                        <div class="absolute bottom-3 left-3">
                             <span class="bg-black/50 text-white text-xs px-2 py-1 rounded-full">${name}</span>
                        </div>
                    </div>
                `;
                videoWrapper.querySelector('.video-container').appendChild(remoteVideo);

                const remoteVideosContainer = document.getElementById('remoteVideos');
                // Remove empty state if present
                const emptyState = remoteVideosContainer.querySelector('.col-span-full');
                if (emptyState) emptyState.style.display = 'none';

                remoteVideosContainer.appendChild(videoWrapper);
                this.addToParticipantsList(participantId, name);

                // Update count
                this.updateParticipantCount();
            }

            remoteVideo.srcObject = event.streams[0];
            remoteVideo.className = 'w-full h-full object-cover rounded-lg';

            // Add participant video to the grid
            this.addParticipantVideoToGrid(participantId, name, remoteVideo);

            // Add to participants list
            this.addToParticipantsList(participantId, name);
        };

        // Create and send offer
        try {
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            console.log('Sending offer to', participantId);
            await this.signaling.sendOffer(participantId, offer);
        } catch (e) {
            console.error('Error creating/sending offer:', e);
        }
    }

    async handleAnswer(participantId, answer) {
        const peerConnection = this.peerConnections[participantId];
        if (peerConnection) {
            try {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
            } catch (e) {
                console.error('Error setting remote description (answer):', e);
            }
        }
    }

    async handleICECandidate(participantId, candidate) {
        const peerConnection = this.peerConnections[participantId];
        if (peerConnection) {
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
            } catch (e) {
                console.error('Error adding ICE candidate:', e);
            }
        }
    }

    handleParticipantLeft(participantId) {
        const peerConnection = this.peerConnections[participantId];
        if (peerConnection) {
            peerConnection.close();
            delete this.peerConnections[participantId];
        }

        const videoWrapper = document.getElementById(`wrapper_${participantId}`);
        if (videoWrapper) {
            videoWrapper.remove();
        }

        this.removeFromParticipantsList(participantId);
        this.updateParticipantCount();

        // Show empty state if no participants
        if (Object.keys(this.peerConnections).length === 0) {
            const emptyState = document.querySelector('#remoteVideos .col-span-full');
            if (emptyState) emptyState.style.display = 'block';
        }
    }

    addToParticipantsList(participantId, name) {
        const list = document.getElementById('participantsList');
        // Clear empty state
        if (list.querySelector('.text-center')) {
            list.innerHTML = '';
        }

        const item = document.createElement('div');
        item.id = `participant_list_${participantId}`;
        item.className = 'flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200';
        item.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">
                    ${name.charAt(0).toUpperCase()}
                </div>
                <div>
                    <p class="font-semibold text-gray-800 text-sm">${name}</p>
                    <p class="text-xs text-gray-500">Participant</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
            </div>
        `;
        list.appendChild(item);
    }

    addParticipantVideoToGrid(participantId, name, videoElement) {
        const remoteVideos = document.getElementById('remoteVideos');
        if (!remoteVideos) return;

        // Remove empty state if it exists
        const emptyState = remoteVideos.querySelector('.col-span-full');
        if (emptyState) {
            emptyState.remove();
        }

        // Create participant card
        const participantCard = document.createElement('div');
        participantCard.className = 'bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:border-blue-400 transition-all duration-200';
        participantCard.id = `participant-card-${participantId}`;

        const initials = name.split(' ').map(n => n[0]).join('').toUpperCase();

        participantCard.innerHTML = `
            <div class="relative bg-gray-800 aspect-video">
                <div id="video-container-${participantId}" class="w-full h-full flex items-center justify-center">
                    <i class="fas fa-user text-white text-4xl"></i>
                </div>
                <div class="absolute top-3 left-3">
                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-1 rounded-full">VIDEO ON</span>
                </div>
                <div class="absolute top-3 right-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                </div>
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
                        <h4 class="font-bold text-gray-800">${name}</h4>
                        <p class="text-sm text-gray-500">Participant</p>
                    </div>
                    <button class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg" onclick="removeParticipant('${participantId}')">
                        <i class="fas fa-user-slash"></i>
                    </button>
                </div>
            </div>
        `;

        remoteVideos.appendChild(participantCard);

        // Add video to container
        const videoContainer = document.getElementById(`video-container-${participantId}`);
        if (videoContainer) {
            videoContainer.innerHTML = '';
            videoContainer.appendChild(videoElement);
        }
    }

    removeFromParticipantsList(participantId) {
        const item = document.getElementById(`participant_list_${participantId}`);
        if (item) item.remove();

        const list = document.getElementById('participantsList');
        if (list.children.length === 0) {
            list.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500 font-medium">No participants connected</p>
                </div>
            `;
        }

        // Also remove from video grid
        const participantCard = document.getElementById(`participant-card-${participantId}`);
        if (participantCard) {
            participantCard.remove();
        }
    }

    updateParticipantCount() {
        const count = Object.keys(this.peerConnections).length;
        document.getElementById('participantCount').textContent = count;
        document.getElementById('activeParticipants').textContent = count;
        document.getElementById('connectedCount').textContent = count;
    }

    setupEventListeners() {
        // Video toggle
        document.getElementById('toggleVideo').addEventListener('click', () => {
            const videoTrack = this.localStream.getVideoTracks()[0];
            if (videoTrack) {
                videoTrack.enabled = !videoTrack.enabled;
                this.updateVideoStatus(videoTrack.enabled);
                this.updateMediaStatusInDB('video', videoTrack.enabled);
            }
        });

        // Audio toggle
        document.getElementById('toggleAudio').addEventListener('click', () => {
            const audioTrack = this.localStream.getAudioTracks()[0];
            if (audioTrack) {
                audioTrack.enabled = !audioTrack.enabled;
                this.updateAudioStatus(audioTrack.enabled);
                this.updateMediaStatusInDB('audio', audioTrack.enabled);
            }
        });

        // Run code button
        const runCodeBtn = document.getElementById('runCode');
        if (runCodeBtn) {
            runCodeBtn.addEventListener('click', () => {
                if (this.editor) {
                    try {
                        // Simple code execution (for demo purposes)
                        const code = this.editor.getValue();
                        console.log('Running code:', code);
                        // You could integrate with a code execution service here
                        alert('Code execution feature would be implemented here');
                    } catch (error) {
                        console.error('Code execution error:', error);
                    }
                }
            });
        }

        // Toggle participant editing
        const toggleEditBtn = document.getElementById('toggleParticipantEditing');
        if (toggleEditBtn) {
            let editingEnabled = true;
            toggleEditBtn.addEventListener('click', () => {
                editingEnabled = !editingEnabled;
                const command = editingEnabled ? 'ENABLE_EDITING' : 'DISABLE_EDITING';

                // Send command to all participants
                Object.values(this.dataChannels).forEach(channel => {
                    if (channel.readyState === 'open') {
                        channel.send(command);
                    }
                });

                // Update button text
                toggleEditBtn.innerHTML = editingEnabled
                    ? '<i class="fas fa-edit"></i> Allow Editing'
                    : '<i class="fas fa-lock"></i> Lock Editing';

                toggleEditBtn.className = editingEnabled
                    ? 'text-gray-400 hover:text-white text-sm px-3 py-1 rounded'
                    : 'text-red-400 hover:text-red-300 text-sm px-3 py-1 rounded';
            });
        }

        // End interview
        document.getElementById('endInterview').addEventListener('click', () => {
            if (confirm('End interview for all participants?')) {
                this.socket.send(JSON.stringify({
                    type: 'endInterview',
                    room: this.roomId
                }));

                Object.values(this.peerConnections).forEach(pc => pc.close());
                Object.values(this.dataChannels).forEach(dc => dc.close());
                this.peerConnections = {};
                this.dataChannels = {};

                window.location.href = 'index.php';
            }
        });

        // Handle window unload
        window.addEventListener('beforeunload', () => {
            this.signaling.leaveRoom();
        });
    }

    async handleCurrentParticipants(participants) {
        console.log('Loading current participants:', participants);
        for (const participant of participants) {
            // Establish WebRTC connection with existing participant
            await this.establishConnectionWithExistingParticipant(participant.socket_id, participant.participant_name);
            // Add to UI
            this.addToParticipantsList(participant.socket_id, participant.participant_name);
        }
        this.updateParticipantCount();
    }

    async establishConnectionWithExistingParticipant(participantId, name) {
        console.log(`Establishing connection with existing participant: ${name} (${participantId})`);

        // Create peer connection
        const peerConnection = new RTCPeerConnection(this.configuration);
        this.peerConnections[participantId] = peerConnection;

        // Create Data Channel
        const dataChannel = peerConnection.createDataChannel("code-editor");
        this.dataChannels[participantId] = dataChannel;

        dataChannel.onopen = () => {
            console.log(`Data channel open with existing participant ${name}`);
            // Send current code to participant
            if (this.editor) {
                dataChannel.send(this.editor.getValue());
            }
            // Enable editing for the participant
            setTimeout(() => {
                dataChannel.send('ENABLE_EDITING');
            }, 1000);
        };

        dataChannel.onmessage = (event) => {
            if (event.data === 'REQUEST_CODE') {
                // Send current code to requesting participant
                if (this.editor) {
                    dataChannel.send(this.editor.getValue());
                }
            } else if (this.editor) {
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
            remoteVideo.className = 'w-full h-full object-cover rounded-lg';

            // Add participant video to the grid
            this.addParticipantVideoToGrid(participantId, name, remoteVideo);
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

    handleParticipantStatusUpdate(message) {
        // Update participant status in UI
        const participantElement = document.getElementById(`participant_${message.participantId}`);
        if (participantElement) {
            const statusDiv = participantElement.querySelector('.flex.items-center.gap-2');
            if (statusDiv) {
                statusDiv.innerHTML = `
                    <span class="bg-${message.videoEnabled ? 'green' : 'red'}-100 text-${message.videoEnabled ? 'green' : 'red'}-800 text-xs px-2 py-1 rounded">Video ${message.videoEnabled ? 'ON' : 'OFF'}</span>
                    <span class="bg-${message.audioEnabled ? 'green' : 'red'}-100 text-${message.audioEnabled ? 'green' : 'red'}-800 text-xs px-2 py-1 rounded">Audio ${message.audioEnabled ? 'ON' : 'OFF'}</span>
                `;
            }
        }
    }

    handleMediaStatusUpdate(message) {
        // Update participant status in UI
        const participantElement = document.getElementById(`participant_${message.participantId}`);
        if (participantElement) {
            const statusDiv = participantElement.querySelector('.flex.items-center.gap-2');
            if (statusDiv && statusDiv.children.length >= 2) {
                const videoSpan = statusDiv.children[0];
                const audioSpan = statusDiv.children[1];

                if (message.mediaType === 'video') {
                    videoSpan.className = `bg-${message.enabled ? 'green' : 'red'}-100 text-${message.enabled ? 'green' : 'red'}-800 text-xs px-2 py-1 rounded`;
                    videoSpan.textContent = `Video ${message.enabled ? 'ON' : 'OFF'}`;
                } else if (message.mediaType === 'audio') {
                    audioSpan.className = `bg-${message.enabled ? 'green' : 'red'}-100 text-${message.enabled ? 'green' : 'red'}-800 text-xs px-2 py-1 rounded`;
                    audioSpan.textContent = `Audio ${message.enabled ? 'ON' : 'OFF'}`;
                }
            }
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

    removeParticipant(participantId) {
        if (confirm('Remove this participant from the interview?')) {
            const peerConnection = this.peerConnections[participantId];
            if (peerConnection) {
                peerConnection.close();
                delete this.peerConnections[participantId];
            }

            if (this.dataChannels[participantId]) {
                this.dataChannels[participantId].close();
                delete this.dataChannels[participantId];
            }

            // Remove from UI
            this.removeFromParticipantsList(participantId);

            // Notify the participant to leave
            this.socket.send(JSON.stringify({
                type: 'participantRemoved',
                target: participantId,
                room: this.roomId
            }));
        }
    }
}

function initializeHost(roomId, userName) {
    window.hostRTC = new HostWebRTC(roomId, userName);
}

function removeParticipant(participantId) {
    if (window.hostRTC) {
        window.hostRTC.removeParticipant(participantId);
    }
}

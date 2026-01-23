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
            this.localStream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });

            const localVideo = document.getElementById('localVideo');
            localVideo.srcObject = this.localStream;
        } catch (error) {
            console.error('Error accessing media devices:', error);
            alert('Could not access camera/microphone. Please check permissions.');
        }
    }

    async initSignaling() {
        // Setup event listeners
        this.signaling.on('newParticipant', async (participant) => {
            console.log('New participant:', participant);
            await this.handleNewParticipant(participant.id, participant.name);
        });

        this.signaling.on('participantLeft', (participantId) => {
            console.log('Participant left:', participantId);
            this.handleParticipantLeft(participantId);
        });

        this.signaling.on('answer', async ({ from, answer }) => {
            console.log('Received answer from:', from);
            await this.handleAnswer(from, answer);
        });

        this.signaling.on('ice-candidate', async ({ from, candidate }) => {
            console.log('Received ICE candidate from:', from);
            await this.handleICECandidate(from, candidate);
        });

        // Initialize/Create Room
        try {
            await this.signaling.createRoom();
            console.log('Room created in Firestore');
        } catch (e) {
            console.error('Error creating room:', e);
            alert('Failed to initialize room in database.');
        }
    }

    async handleNewParticipant(participantId, name) {
        if (this.peerConnections[participantId]) return;

        console.log(`Creating PeerConnection for ${name} (${participantId})`);
        const peerConnection = new RTCPeerConnection(this.configuration);
        this.peerConnections[participantId] = peerConnection;

        // Add local tracks
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
    }

    updateParticipantCount() {
        const count = Object.keys(this.peerConnections).length;
        document.getElementById('participantCount').textContent = count;
        document.getElementById('activeParticipants').textContent = count;
        document.getElementById('connectedCount').textContent = count;
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

        document.getElementById('endInterview').addEventListener('click', async () => {
            if (confirm('End interview for all participants?')) {
                await this.signaling.leaveRoom();
                window.location.href = 'index.php';
            }
        });

        // Handle window unload
        window.addEventListener('beforeunload', () => {
            this.signaling.leaveRoom();
        });
    }
}

// Global exposure for debugging

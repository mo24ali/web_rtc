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
                this.signaling.sendCodeUpdate(content);
            }
        });

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
        // Event Listeners
        this.signaling.on('offer', async (offer) => {
            console.log('Received offer from host');
            await this.handleOffer(offer);
        });

        this.signaling.on('ice-candidate', async (candidate) => {
            console.log('Received ICE candidate from host');
            await this.handleICECandidate(candidate);
        });

        this.signaling.on('endInterview', () => {
            alert('Host has ended the interview.');
            window.location.href = 'index.php';
        });

        // Join Room
        try {
            await this.signaling.joinRoom();
            console.log('Joined room in Firestore');
        } catch (e) {
            console.error('Error joining room:', e);
            alert('Failed to join room. It might not exist or connection failed.');
            window.location.href = 'index.php';
        }
    }

    async handleOffer(offer) {
        console.log('Handling offer');
        const peerConnection = new RTCPeerConnection(this.configuration);
        this.peerConnections[this.hostId] = peerConnection;

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

    async handleICECandidate(candidate) {
        const peerConnection = this.peerConnections[this.hostId];
        if (peerConnection) {
            try {
                await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
            } catch (e) {
                console.error('Error adding ICE candidate:', e);
            }
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

        document.getElementById('leaveRoom').addEventListener('click', async () => {
            if (confirm('Leave the interview room?')) {
                await this.signaling.leaveRoom();
                window.location.href = 'index.php';
            }
        });

        window.addEventListener('beforeunload', () => {
            this.signaling.leaveRoom();
        });
    }
}


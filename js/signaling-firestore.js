import { db } from './firebase-config.js';
import {
    collection,
    doc,
    setDoc,
    onSnapshot,
    addDoc,
    updateDoc,
    deleteDoc,
    getDoc,
    serverTimestamp,
    query,
    where,
    getDocs
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";

export class FirestoreSignaling {
    constructor(roomId, userName, isHost) {
        this.roomId = roomId;
        this.userName = userName;
        this.isHost = isHost;
        this.callbacks = {};
        this.unsubscribeParticipants = null;
        this.participantUnsubscribes = {};
    }

    on(event, callback) {
        this.callbacks[event] = callback;
    }

    emit(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event](data);
        }
    }

    // Host: Create room
    async createRoom() {
        const roomRef = doc(db, 'rooms', this.roomId);
        await setDoc(roomRef, {
            hostName: this.userName,
            createdAt: serverTimestamp(),
            isActive: true
        });

        // Listen for participants
        const participantsRef = collection(db, 'rooms', this.roomId, 'participants');
        this.unsubscribeParticipants = onSnapshot(participantsRef, (snapshot) => {
            snapshot.docChanges().forEach((change) => {
                if (change.type === 'added') {
                    const participantId = change.doc.id;
                    const data = change.doc.data();

                    // Don't modify own data if somehow reflected, though structure prevents it usually
                    this.emit('newParticipant', {
                        id: participantId,
                        name: data.name
                    });

                    // Listen for signals from this participant
                    this.listenToParticipantSignals(participantId);
                }
                else if (change.type === 'removed') {
                    this.emit('participantLeft', change.doc.id);
                    if (this.participantUnsubscribes[change.doc.id]) {
                        this.participantUnsubscribes[change.doc.id]();
                        delete this.participantUnsubscribes[change.doc.id];
                    }
                }
            });
        });

        // Listen for code changes (global room level)
        this.listenForCodeChanges();
    }

    // Participant: Join room
    async joinRoom() {
        const roomRef = doc(db, 'rooms', this.roomId);
        const roomSnap = await getDoc(roomRef);

        if (!roomSnap.exists()) {
            throw new Error('Room does not exist');
        }

        // Add self to participants
        const participantRef = doc(collection(db, 'rooms', this.roomId, 'participants'));
        this.myId = participantRef.id;

        await setDoc(participantRef, {
            name: this.userName,
            joinedAt: serverTimestamp()
        });

        // Listen for signals sent to me (offer, ice candidates from host)
        this.unsubscribeMySignals = onSnapshot(participantRef, (doc) => {
            const data = doc.data();
            if (data && data.offer) {
                this.emit('offer', data.offer);
                // Clear offer after reading to avoid re-processing? 
                // Better: keep it, we handle idempotency or just react to changes.
                // For simplicity in this structure, we assume offer comes once.
                // Actually, to update we probably want a subcollection or array for queue.
                // But simple field update works for 1:1 host-participant per connection.
            }
            if (data && data.iceCandidatesHost) {
                // Process latest batch or new ones
                const candidates = data.iceCandidatesHost;
                candidates.forEach(c => this.emit('ice-candidate', c));
            }
        });

        // Listen for room closing
        this.unsubscribeRoom = onSnapshot(roomRef, (doc) => {
            if (!doc.exists() || !doc.data().isActive) {
                this.emit('endInterview');
            }
        });

        // Listen for code changes
        this.listenForCodeChanges();

        return this.myId;
    }

    listenToParticipantSignals(participantId) {
        const participantRef = doc(db, 'rooms', this.roomId, 'participants', participantId);

        const unsubscribe = onSnapshot(participantRef, (doc) => {
            const data = doc.data();
            if (data) {
                if (data.answer) {
                    this.emit('answer', { from: participantId, answer: data.answer });
                }
                if (data.iceCandidatesParticipant) {
                    data.iceCandidatesParticipant.forEach(c => {
                        this.emit('ice-candidate', { from: participantId, candidate: c });
                    });
                }
            }
        });

        this.participantUnsubscribes[participantId] = unsubscribe;
    }

    async sendOffer(targetId, offer) {
        // Host writes offer to participant's document
        const participantRef = doc(db, 'rooms', this.roomId, 'participants', targetId);
        await updateDoc(participantRef, {
            offer: JSON.parse(JSON.stringify(offer)) // ensure plain object
        });
    }

    async sendAnswer(answer) {
        // Participant writes answer to their own document
        const participantRef = doc(db, 'rooms', this.roomId, 'participants', this.myId);
        await updateDoc(participantRef, {
            answer: JSON.parse(JSON.stringify(answer))
        });
    }

    async sendIceCandidate(targetId, candidate) {
        const candidatePlain = JSON.parse(JSON.stringify(candidate));

        if (this.isHost) {
            // Host sending to participant
            const participantRef = doc(db, 'rooms', this.roomId, 'participants', targetId);
            const docSnap = await getDoc(participantRef);
            let current = docSnap.data().iceCandidatesHost || [];
            current.push(candidatePlain);

            await updateDoc(participantRef, {
                iceCandidatesHost: current
            });
        } else {
            // Participant sending to host (stored in own doc)
            const participantRef = doc(db, 'rooms', this.roomId, 'participants', this.myId);
            const docSnap = await getDoc(participantRef);
            let current = docSnap.data().iceCandidatesParticipant || [];
            current.push(candidatePlain);

            await updateDoc(participantRef, {
                iceCandidatesParticipant: current
            });
        }
    }

    // Code Sharing
    async sendCodeUpdate(content) {
        const roomRef = doc(db, 'rooms', this.roomId);
        await updateDoc(roomRef, {
            code: content,
            lastUpdateBy: this.isHost ? 'host' : this.myId
        });
    }

    listenForCodeChanges() {
        const roomRef = doc(db, 'rooms', this.roomId);
        onSnapshot(roomRef, (doc) => {
            const data = doc.data();
            if (data && data.code !== undefined) {
                // Check if update is from us
                const fromMe = this.isHost ? (data.lastUpdateBy === 'host') : (data.lastUpdateBy === this.myId);
                if (!fromMe) {
                    this.emit('codeUpdate', data.code);
                }
            }
        });
    }

    async leaveRoom() {
        if (!this.isHost && this.myId) {
            const participantRef = doc(db, 'rooms', this.roomId, 'participants', this.myId);
            await deleteDoc(participantRef);
        } else if (this.isHost) {
            // Deactivating room
            const roomRef = doc(db, 'rooms', this.roomId);
            await updateDoc(roomRef, { isActive: false });
        }

        // Unsubscribe all
        if (this.unsubscribeParticipants) this.unsubscribeParticipants();
        Object.values(this.participantUnsubscribes).forEach(unsub => unsub());
        if (this.unsubscribeMySignals) this.unsubscribeMySignals();
        if (this.unsubscribeRoom) this.unsubscribeRoom();
    }
}

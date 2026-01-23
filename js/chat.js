import { db, auth } from './firebase-config.js';
import {
    collection,
    addDoc,
    onSnapshot,
    query,
    orderBy,
    serverTimestamp
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
import { signInAnonymously, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

export class ChatRoom {
    constructor(roomId, userName, containerId) {
        this.roomId = roomId;
        this.userName = userName;
        this.container = document.getElementById(containerId);
        this.messagesContainer = this.container.querySelector('.messages-list');
        this.input = this.container.querySelector('input');
        this.sendBtn = this.container.querySelector('button.send-btn');
        this.userId = null;

        this.init();
    }

    async init() {
        // Authenticate
        try {
            await signInAnonymously(auth);
            onAuthStateChanged(auth, (user) => {
                if (user) {
                    this.userId = user.uid;
                    this.setupListeners();
                    this.subscribeToMessages();
                    console.log("Chat: Signed in as", this.userId);
                }
            });
        } catch (error) {
            console.error("Chat Auth Error:", error);
        }
    }

    setupListeners() {
        this.sendBtn.addEventListener('click', () => this.sendMessage());
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
    }

    async sendMessage() {
        const text = this.input.value.trim();
        if (!text) return;

        try {
            this.input.value = ''; // Clear early for better UX
            await addDoc(collection(db, 'rooms', this.roomId, 'messages'), {
                text: text,
                sender: this.userName,
                uid: this.userId,
                timestamp: serverTimestamp()
            });
        } catch (error) {
            console.error("Error sending message:", error);
            alert("Failed to send message");
        }
    }

    subscribeToMessages() {
        const q = query(
            collection(db, 'rooms', this.roomId, 'messages'),
            orderBy('timestamp', 'asc')
        );

        onSnapshot(q, (snapshot) => {
            snapshot.docChanges().forEach((change) => {
                if (change.type === "added") {
                    this.appendMessage(change.doc.data());
                }
            });
        });
    }

    appendMessage(data) {
        const isMe = data.uid === this.userId;
        const div = document.createElement('div');
        div.className = `flex flex-col ${isMe ? 'items-end' : 'items-start'} mb-4`;

        // Format time
        let timeStr = '';
        if (data.timestamp) {
            const date = data.timestamp.toDate();
            timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        div.innerHTML = `
            <div class="flex items-end gap-2 max-w-[80%] ${isMe ? 'flex-row-reverse' : 'flex-row'}">
                <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white ${isMe ? 'bg-blue-500' : 'bg-gray-500'}">
                    ${data.sender.charAt(0).toUpperCase()}
                </div>
                <div>
                    <div class="px-4 py-2 rounded-2xl ${isMe ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white border border-gray-200 text-gray-800 rounded-tl-none'} shadow-sm">
                        <p class="text-sm">${this.escapeHtml(data.text)}</p>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1 ${isMe ? 'text-right' : 'text-left'}">
                        ${!isMe ? `<span class="font-bold mr-1">${this.escapeHtml(data.sender)}</span>` : ''}
                        ${timeStr}
                    </p>
                </div>
            </div>
        `;

        this.messagesContainer.appendChild(div);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

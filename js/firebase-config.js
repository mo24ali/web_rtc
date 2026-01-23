// Import the functions you need from the SDKs you need
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

const firebaseConfig = {
    apiKey: "AIzaSyB2quYcK2tHEvsHNNE2lZx4gBuwMdlvNlk",
    authDomain: "codemeet-9051e.firebaseapp.com",
    projectId: "codemeet-9051e",
    storageBucket: "codemeet-9051e.firebasestorage.app",
    messagingSenderId: "163350691015",
    appId: "1:163350691015:web:fae8199d72273941b812e2",
    measurementId: "G-M39Q14P47W"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

export { db, auth };

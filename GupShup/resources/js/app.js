// GupShup Application Entry Point
import { initChat } from './chat.js';
import { generateKeyPair, exportPublicKey, savePrivateKey } from './crypto.js';

// Expose crypto functions globally for register page inline script
window.GupShupCrypto = {
    generateKeyPair,
    exportPublicKey,
    savePrivateKey,
};

// Initialize chat if on chat page
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('chat-app')) {
        initChat();
    }
});

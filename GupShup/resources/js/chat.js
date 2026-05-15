/**
 * GupShup Chat Application
 * Manages conversations, messages, polling, and E2EE integration.
 */

import {
    loadPrivateKey,
    importPublicKey,
    deriveSharedSecret,
    encryptMessage,
    decryptMessage,
    hasPrivateKey,
} from './crypto.js';

// ===== State =====
let currentUser = null;
let privateKey = null;
let selfSharedKey = null;
let conversations = [];
let activeConversation = null;
let activeSharedKey = null;
let lastPollTime = null;
let pollTimer = null;
let heartbeatTimer = null;
let sharedKeyCache = {};
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || window.GUPSHUP_CSRF || '';

// ===== Init =====
export async function initChat() {
    currentUser = window.GUPSHUP_USER;
    if (!currentUser) return;

    // Load private key
    privateKey = await loadPrivateKey();
    if (!privateKey) {
        console.warn('No private key found. Generating a new keypair for this device...');
        
        try {
            const { generateKeyPair, exportPublicKey, savePrivateKey } = await import('./crypto.js');
            
            // Generate new keys
            const keyPair = await generateKeyPair();
            await savePrivateKey(keyPair.privateKey);
            privateKey = keyPair.privateKey;
            
            // Export and upload the new Public Key to the server
            const publicKeyJwk = await exportPublicKey(keyPair.publicKey);
            await apiPost('/api/update-public-key', { 
                public_key: JSON.stringify(publicKeyJwk) 
            });
            
            // Update our local user object so the selfSharedKey derives correctly
            currentUser.public_key = JSON.stringify(publicKeyJwk);
            console.log('New keys generated and synced to server!');
            
        } catch (e) {
            console.error('Failed to generate new keys:', e);
            return; // Stop initialization if crypto fails
        }
    } 
    
    // Derive a key using our OWN public key for decrypting our own message history
    selfSharedKey = await getSharedKey(currentUser.public_key);

    // Bind events
    bindEvents();

    // Load conversations
    await loadConversations();

    // Start polling
    startPolling();
    startHeartbeat();
}

// ===== API Helpers =====
async function apiGet(url) {
    const res = await fetch(url, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        credentials: 'same-origin',
    });
    if (res.status === 401) { window.location.href = '/login'; return null; }
    return res.json();
}

async function apiPost(url, data = {}) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });
    if (res.status === 401) { window.location.href = '/login'; return null; }
    
    return res.json();
}

// ===== Shared Key Derivation =====
async function getSharedKey(otherPublicKeyStr) {
    if (!privateKey || !otherPublicKeyStr) return null;

    // Cache by public key string
    if (sharedKeyCache[otherPublicKeyStr]) {
        return sharedKeyCache[otherPublicKeyStr];
    }

    try {
        const otherPublicKey = await importPublicKey(otherPublicKeyStr);
        const shared = await deriveSharedSecret(privateKey, otherPublicKey);
        sharedKeyCache[otherPublicKeyStr] = shared;
        return shared;
    } catch (e) {
        console.error('Failed to derive shared key:', e);
        return null;
    }
}

// ===== Conversations =====
async function loadConversations() {
    const data = await apiGet('/api/conversations');
    if (!data) return;
    conversations = data;
    renderConversationList();
    document.getElementById('conversations-loading').style.display = 'none';
}

function renderConversationList() {
    const container = document.getElementById('conversation-list');
    const loading = document.getElementById('conversations-loading');

    // Clear existing items (keep loading element)
    const items = container.querySelectorAll('.conversation-item');
    items.forEach(item => item.remove());

    if (conversations.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'conversation-list-empty';
        empty.innerHTML = '<p>No conversations yet</p><p class="text-sm">Start a new chat!</p>';
        container.appendChild(empty);
        return;
    }

    conversations.forEach(conv => {
        const el = createConversationItem(conv);
        container.appendChild(el);
    });
}

function createConversationItem(conv) {
    const el = document.createElement('div');
    el.className = 'conversation-item' + (activeConversation?.id === conv.id ? ' conversation-item--active' : '');
    el.dataset.id = conv.id;

    const initial = conv.other_user.name.charAt(0).toUpperCase();
    const timeStr = conv.last_message_at ? formatRelativeTime(conv.last_message_at) : '';
    const unread = conv.unread_count > 0
        ? `<span class="conversation-unread">${conv.unread_count > 99 ? '99+' : conv.unread_count}</span>`
        : '';
    const onlineDot = conv.other_user.is_online
        ? '<span class="status-dot status-dot--online conversation-online-dot"></span>'
        : '';

    el.innerHTML = `
        <div class="avatar conversation-avatar" style="background:${conv.other_user.avatar_color}">
            ${initial}
            ${onlineDot}
        </div>
        <div class="conversation-info">
            <div class="conversation-top">
                <span class="conversation-name">${escapeHtml(conv.other_user.name)}</span>
                <span class="conversation-time">${timeStr}</span>
            </div>
            <div class="conversation-bottom">
                <span class="conversation-preview">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.5;vertical-align:middle;margin-right:2px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                    Encrypted message
                </span>
                ${unread}
            </div>
        </div>
    `;

    el.addEventListener('click', () => selectConversation(conv));
    return el;
}

async function selectConversation(conv) {
    activeConversation = conv;

    // Update sidebar active state
    document.querySelectorAll('.conversation-item').forEach(el => {
        el.classList.toggle('conversation-item--active', el.dataset.id === conv.id);
    });

    // Show chat panel, hide empty state
    document.getElementById('chat-empty').style.display = 'none';
    document.getElementById('chat-active').style.display = 'flex';

    // On mobile, hide sidebar
    document.getElementById('chat-sidebar').classList.add('sidebar-hidden');
    document.getElementById('chat-main').classList.add('main-visible');

    // Update header
    const initial = conv.other_user.name.charAt(0).toUpperCase();
    const headerAvatar = document.getElementById('chat-header-avatar');
    headerAvatar.textContent = initial;
    headerAvatar.style.background = conv.other_user.avatar_color;
    document.getElementById('chat-header-name').textContent = conv.other_user.name;

    const dot = document.getElementById('chat-header-dot');
    const statusText = document.getElementById('chat-header-status-text');
    if (conv.other_user.is_online) {
        dot.className = 'status-dot status-dot--online';
        statusText.textContent = 'Online';
    } else {
        dot.className = 'status-dot status-dot--offline';
        statusText.textContent = 'Offline';
    }

    // Derive shared key
    activeSharedKey = await getSharedKey(conv.other_user.public_key);

    // Load messages
    await loadMessages(conv.id);

    // Mark as read
    
    await apiPost(`/api/messages/${conv.id}/read`);

    // Update unread count in sidebar
    conv.unread_count = 0;
    renderConversationList();

    // Focus input
    document.getElementById('message-input').focus();
}

// ===== Messages =====
async function loadMessages(conversationId) {
    const messagesContainer = document.getElementById('chat-messages');
    const loadingEl = document.getElementById('messages-loading');

    loadingEl.style.display = 'flex';
    messagesContainer.innerHTML = '';
    messagesContainer.appendChild(loadingEl);

    const data = await apiGet(`/api/messages/${conversationId}`);
    if (!data) return;

    loadingEl.style.display = 'none';

    // Add E2EE notice at top
    const notice = document.createElement('div');
    notice.className = 'chat-e2ee-notice';
    notice.innerHTML = `
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        <span>Messages are end-to-end encrypted. No one outside this chat can read them.</span>
    `;
    messagesContainer.appendChild(notice);

    // Render messages
    for (const msg of data) {
        const el = await createMessageBubble(msg);
        messagesContainer.appendChild(el);
    }

    // Set last poll time
    if (data.length > 0) {
        lastPollTime = data[data.length - 1].created_at;
    } else {
        lastPollTime = new Date().toISOString();
    }

    // Scroll to bottom
    scrollToBottom();
}

async function createMessageBubble(msg) {
    const el = document.createElement('div');
    el.className = 'message ' + (msg.is_mine ? 'message--sent' : 'message--received');
    el.dataset.id = msg.id;

    // Decrypt message based on ownership
    let text = '🔒 Encrypted message';
    const keyToUse = msg.is_mine ? selfSharedKey : activeSharedKey;

    
    if (keyToUse) {
        try {
            text = await decryptMessage(msg.ciphertext, msg.iv, keyToUse);
        } catch (e) {
            text = '🔒 Unable to decrypt';
            console.error('Decryption failed:', e);
        }
    }

    const time = formatMessageTime(msg.created_at);
    const readCheck = msg.is_mine
        ? `<span class="message-read ${msg.read_at ? 'message-read--seen' : ''}">${msg.read_at ? '✓✓' : '✓'}</span>`
        : '';

    el.innerHTML = `
        <div class="message-bubble">
            <span class="message-text">${escapeHtml(text)}</span>
            <span class="message-meta">
                <span class="message-time">${time}</span>
                ${readCheck}
            </span>
        </div>
    `;

    return el;
}

async function sendMessage() {
    const input = document.getElementById('message-input');
    const text = input.value.trim();
    if (!text || !activeConversation || !activeSharedKey || !selfSharedKey) return;

    input.value = '';
    updateSendButton();

    try {
        // ENCRYPT TWICE
        const recipientPayload = await encryptMessage(text, activeSharedKey);
        const senderPayload = await encryptMessage(text, selfSharedKey);

        // Optimistically render
        const optimisticMsg = {
            id: 'temp-' + Date.now(),
            sender_id: currentUser.id,
            ciphertext: senderPayload.ciphertext,
            iv: senderPayload.iv,
            is_mine: true,
            read_at: null,
            created_at: new Date().toISOString(),
        };
        const bubble = await createOptimisticBubble(text, optimisticMsg);
        document.getElementById('chat-messages').appendChild(bubble);
        scrollToBottom();

        // Send BOTH payloads to server
        const result = await apiPost('/api/messages', {
            conversation_id: activeConversation.id,
            recipient_ciphertext: recipientPayload.ciphertext,
            recipient_iv: recipientPayload.iv,
            sender_ciphertext: senderPayload.ciphertext,
            sender_iv: senderPayload.iv,
        });

        if (result && result.id) {
            bubble.dataset.id = result.id;
            lastPollTime = result.created_at;

            // Update conversation in sidebar
            activeConversation.last_message_text = recipientPayload.ciphertext;
            activeConversation.last_message_at = result.created_at;

            // Move to top
            const idx = conversations.findIndex(c => c.id === activeConversation.id);
            if (idx > 0) {
                conversations.splice(idx, 1);
                conversations.unshift(activeConversation);
            }
            renderConversationList();
        }
    } catch (e) {
        console.error('Failed to send message:', e);
    }
}

async function createOptimisticBubble(plaintext, msg) {
    const el = document.createElement('div');
    el.className = 'message message--sent message--sending';
    el.dataset.id = msg.id;

    const time = formatMessageTime(msg.created_at);

    el.innerHTML = `
        <div class="message-bubble">
            <span class="message-text">${escapeHtml(plaintext)}</span>
            <span class="message-meta">
                <span class="message-time">${time}</span>
                <span class="message-read">✓</span>
            </span>
        </div>
    `;

    // Remove sending animation after a moment
    setTimeout(() => el.classList.remove('message--sending'), 500);

    return el;
}

// ===== Polling =====
function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(pollForUpdates, 2500);
}

async function pollForUpdates() {
    if (!lastPollTime) {
        lastPollTime = new Date(Date.now() - 5000).toISOString();
    }

    try {
        const data = await apiGet(`/api/poll?since=${encodeURIComponent(lastPollTime)}`);
        if (!data) return;

        // Process new messages
        if (data.messages && data.messages.length > 0) {
            for (const msg of data.messages) {
                // Update last poll time
                if (msg.created_at > lastPollTime) {
                    lastPollTime = msg.created_at;
                }

                // If this message is in the active conversation, render it
                if (activeConversation && msg.conversation_id === activeConversation.id) {
                    // Check if already rendered
                    if (!document.querySelector(`[data-id="${msg.id}"]`)) {
                        const bubble = await createMessageBubble(msg);
                        document.getElementById('chat-messages').appendChild(bubble);
                        scrollToBottom();
                    }
                }
            }

            // Refresh conversation list
            await loadConversations();
        }

        // Update conversation metadata
        if (data.conversations && data.conversations.length > 0) {
            for (const updatedConv of data.conversations) {
                const idx = conversations.findIndex(c => c.id === updatedConv.id);
                if (idx !== -1) {
                    conversations[idx] = { ...conversations[idx], ...updatedConv };
                } else {
                    conversations.unshift(updatedConv);
                }
            }
            renderConversationList();
        }
    } catch (e) {
        // Silently fail on poll errors
    }
}

function startHeartbeat() {
    if (heartbeatTimer) clearInterval(heartbeatTimer);
    heartbeatTimer = setInterval(() => {
        apiPost('/api/heartbeat').catch(() => {});
    }, 30000);
}

// ===== User Search & New Chat =====
async function searchUsers(query) {
    const results = await apiGet(`/api/users/search?q=${encodeURIComponent(query)}`);
    if (!results) return;

    const container = document.getElementById('user-search-results');

    if (results.length === 0) {
        container.innerHTML = '<div class="modal-empty">No users found</div>';
        return;
    }

    container.innerHTML = '';
    results.forEach(user => {
        const el = document.createElement('div');
        el.className = 'user-result';
        const initial = user.name.charAt(0).toUpperCase();

        el.innerHTML = `
            <div class="avatar" style="background:${user.avatar_color}">${initial}</div>
            <div class="user-result-info">
                <span class="user-result-name">${escapeHtml(user.name)}</span>
                <span class="user-result-email">${escapeHtml(user.email)}</span>
            </div>
            ${user.is_online ? '<span class="status-dot status-dot--online"></span>' : ''}
        `;

        el.addEventListener('click', async () => {
            const conv = await apiPost('/api/conversations', { user_id: user.id });
            if (conv) {
                closeModal();

                // Add to conversations if not exists
                const existing = conversations.find(c => c.id === conv.id);
                if (!existing) {
                    conversations.unshift(conv);
                }
                renderConversationList();
                selectConversation(conv);
            }
        });

        container.appendChild(el);
    });
}

// ===== Modal =====
function openModal() {
    document.getElementById('new-chat-modal').style.display = 'flex';
    document.getElementById('user-search-input').value = '';
    document.getElementById('user-search-results').innerHTML = '<div class="modal-empty">Type to search for users</div>';
    setTimeout(() => document.getElementById('user-search-input').focus(), 100);
}

function closeModal() {
    document.getElementById('new-chat-modal').style.display = 'none';
}

// ===== Event Bindings =====
function bindEvents() {
    // Send message
    const input = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');

    if (input) {
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        input.addEventListener('input', updateSendButton);
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    // New chat button
    const backupBtn = document.getElementById('backup-key-btn');
    if (backupBtn) {
        backupBtn.addEventListener('click', downloadRecoveryKey);
    }
    const newChatBtn = document.getElementById('new-chat-btn');
    if (newChatBtn) {
        newChatBtn.addEventListener('click', openModal);
    }

    // Close modal
    const closeBtn = document.getElementById('close-modal-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    // Modal overlay click
    const modalOverlay = document.getElementById('new-chat-modal');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
        });
    }

    // User search
    let searchDebounce;
    const userSearchInput = document.getElementById('user-search-input');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => {
                const q = userSearchInput.value.trim();
                if (q.length >= 2) {
                    searchUsers(q);
                } else {
                    document.getElementById('user-search-results').innerHTML = '<div class="modal-empty">Type to search for users</div>';
                }
            }, 300);
        });
    }

    // Mobile back button
    const backBtn = document.getElementById('chat-back-btn');
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            document.getElementById('chat-sidebar').classList.remove('sidebar-hidden');
            document.getElementById('chat-main').classList.remove('main-visible');
        });
    }

    // Conversation search / filter
    const convSearch = document.getElementById('conversation-search');
    if (convSearch) {
        convSearch.addEventListener('input', () => {
            const q = convSearch.value.toLowerCase();
            document.querySelectorAll('.conversation-item').forEach(el => {
                const name = el.querySelector('.conversation-name')?.textContent?.toLowerCase() || '';
                el.style.display = name.includes(q) ? '' : 'none';
            });
        });
    }

    // Escape key closes modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
}

function updateSendButton() {
    const input = document.getElementById('message-input');
    const btn = document.getElementById('send-btn');
    if (input && btn) {
        btn.disabled = !input.value.trim();
    }
}

// ===== Utility Functions =====
function scrollToBottom() {
    const container = document.getElementById('chat-messages');
    if (container) {
        requestAnimationFrame(() => {
            container.scrollTop = container.scrollHeight;
        });
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatRelativeTime(isoString) {
    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'now';
    if (diffMins < 60) return `${diffMins}m`;
    if (diffHours < 24) return `${diffHours}h`;
    if (diffDays < 7) return `${diffDays}d`;

    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function formatMessageTime(isoString) {
    const date = new Date(isoString);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function downloadRecoveryKey() {
    const keyData = localStorage.getItem('gupshup_private_key');
    if (!keyData) {
        alert('No private key found to backup!');
        return;
    }

    // Create a secure Blob from the local storage data
    const blob = new Blob([keyData], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    
    // Create a temporary link to trigger the download
    const a = document.createElement('a');
    a.href = url;
    const safeName = currentUser.name.replace(/\s+/g, '_').toLowerCase();
    a.download = `gupshup_recovery_key_${safeName}.txt`;
    
    document.body.appendChild(a);
    a.click();
    
    // Cleanup
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
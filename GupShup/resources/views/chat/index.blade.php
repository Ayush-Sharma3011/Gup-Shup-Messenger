@extends('layouts.app')

@section('title', 'Chat — GupShup')

@section('content')
<div class="chat-app" id="chat-app">
    {{-- Left Sidebar --}}
    <aside class="chat-sidebar" id="chat-sidebar">
        {{-- User Header --}}
        <div class="sidebar-header">
            <div class="sidebar-user">
                <div class="avatar" style="--avatar-color: {{ $currentUser->avatar_color ?? '#6C5CE7' }};">
                    {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                </div>
                <div class="sidebar-user-info">
                    <span class="sidebar-user-name">{{ $currentUser->name }}</span>
                    <span class="sidebar-user-status">
                        <span class="status-dot status-dot--online"></span>
                        Online
                    </span>
                </div>
            </div>
            <div class="sidebar-actions">
                <button class="icon-btn" id="new-chat-btn" title="New Chat">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
                        <line x1="12" y1="8" x2="12" y2="16" />
                        <line x1="8" y1="12" x2="16" y2="12" />
                    </svg>
                </button>
                <button id="backup-key-btn" class="icon-btn" title="Backup Encryption Key">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" />
                        <line x1="12" y1="8" x2="12" y2="16" />
                        <line x1="8" y1="12" x2="16" y2="12" />
                    </svg>
                </button>
                <form method="POST" action="/logout" style="display:inline;">
                    @csrf
                    <button type="submit" class="icon-btn" title="Logout">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Search --}}
        <div class="sidebar-search">
            <svg class="sidebar-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <path d="M21 21l-4.35-4.35" />
            </svg>
            <input type="text" class="sidebar-search-input" id="conversation-search" placeholder="Search conversations...">
        </div>

        {{-- Conversation List --}}
        <div class="conversation-list" id="conversation-list">
            <div class="conversation-list-loading" id="conversations-loading">
                <div class="loading-spinner"></div>
                <span>Loading conversations...</span>
            </div>
        </div>
    </aside>

    {{-- Right Chat Panel --}}
    <main class="chat-main" id="chat-main">
        {{-- Empty State --}}
        <div class="chat-empty" id="chat-empty">
            <div class="chat-empty-icon">
                <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                    <rect width="80" height="80" rx="20" fill="url(#empty-grad)" opacity="0.15" />
                    <path d="M24 56V28a4 4 0 014-4h24a4 4 0 014 4v20a4 4 0 01-4 4H36l-8 8v-8h-4v4z" fill="url(#empty-grad)" opacity="0.6" />
                    <circle cx="36" cy="38" r="2.5" fill="white" opacity="0.8" />
                    <circle cx="44" cy="38" r="2.5" fill="white" opacity="0.8" />
                    <circle cx="52" cy="38" r="2.5" fill="white" opacity="0.8" />
                    <defs>
                        <linearGradient id="empty-grad" x1="0" y1="0" x2="80" y2="80">
                            <stop stop-color="#8B5CF6" />
                            <stop offset="1" stop-color="#06B6D4" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h2 class="chat-empty-title">GupShup Messenger</h2>
            <p class="chat-empty-desc">Select a conversation or start a new chat to begin messaging with end-to-end encryption.</p>
            <div class="chat-empty-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" />
                    <path d="M7 11V7a5 5 0 0110 0v4" />
                </svg>
                <span>All messages are end-to-end encrypted</span>
            </div>
        </div>

        {{-- Active Chat (hidden initially) --}}
        <div class="chat-active" id="chat-active" style="display:none;">
            {{-- Chat Header --}}
            <div class="chat-header" id="chat-header">
                <button class="icon-btn chat-back-btn" id="chat-back-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7" />
                    </svg>
                </button>
                <div class="avatar chat-header-avatar" id="chat-header-avatar">A</div>
                <div class="chat-header-info">
                    <span class="chat-header-name" id="chat-header-name">User</span>
                    <span class="chat-header-status" id="chat-header-status">
                        <span class="status-dot" id="chat-header-dot"></span>
                        <span id="chat-header-status-text">Offline</span>
                    </span>
                </div>
                <div class="chat-header-e2ee">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0110 0v4" />
                    </svg>
                    <span>Encrypted</span>
                </div>
            </div>

            {{-- Messages Area --}}
            <div class="chat-messages" id="chat-messages">
                <div class="chat-messages-loading" id="messages-loading" style="display:none;">
                    <div class="loading-spinner"></div>
                </div>
            </div>

            {{-- Message Input --}}
            <div class="chat-input-bar">
                <div class="chat-input-wrapper">
                    <div id="sticker-menu" style="display: flex; gap: 10px; padding: 10px; background: #f3f4f6; border-radius: 8px; margin-bottom: 10px;">
                    <button onclick="sendSticker('thumbs_up')">👍</button>
                    <button onclick="sendSticker('heart')">❤️</button>
                    <button onclick="sendSticker('laugh')">😂</button>
                    <button onclick="sendSticker('angle_halo')">😇</button>
                    <button onclick="sendSticker('cowboy')">🤠</button>
                    <button onclick="sendSticker('crying')">😢</button>
                    <button onclick="sendSticker('dizzy')">😵</button>
                    <button onclick="sendSticker('drooling')">🤤</button>
                    <button onclick="sendSticker('loudly_crying')">😭</button>
                    <button onclick="sendSticker('slightly_smiling')">🙂</button>
                    <button onclick="sendSticker('upside_down_crying')">🙃</button>


                </div>
                    <input type="text" class="chat-input" id="message-input" placeholder="Type an encrypted message..." autocomplete="off">
                </div>
                <button class="send-btn" id="send-btn" disabled>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13" />
                        <polygon points="22 2 15 22 11 13 2 9 22 2" />
                    </svg>
                </button>
            </div>
        </div>
    </main>
</div>

{{-- New Chat Modal --}}
<div class="modal-overlay" id="new-chat-modal" style="display:none;">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">New Conversation</h3>
            <button class="icon-btn" id="close-modal-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="modal-search">
            <svg class="modal-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <path d="M21 21l-4.35-4.35" />
            </svg>
            <input type="text" class="modal-search-input" id="user-search-input" placeholder="Search by name or email..." autofocus>
        </div>
        <div class="modal-results" id="user-search-results">
            <div class="modal-empty">Type to search for users</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.GUPSHUP_USER = {!! json_encode([
        'id'           => (string) $currentUser->_id,
        'name'         => $currentUser->name,
        'email'        => $currentUser->email,
        'avatar_color' => $currentUser->avatar_color,
        'public_key'   => $currentUser->public_key,
    ]) !!};
    
    window.GUPSHUP_CSRF = '{{ csrf_token() }}';
</script>
@endpush
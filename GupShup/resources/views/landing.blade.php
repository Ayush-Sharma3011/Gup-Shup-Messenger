@extends('layouts.app')

@section('title', 'GupShup — Private Encrypted Messaging')

@section('content')
<div class="landing-page">
    {{-- Animated background orbs --}}
    <div class="landing-bg">
        <div class="landing-bg-orb landing-bg-orb--1"></div>
        <div class="landing-bg-orb landing-bg-orb--2"></div>
        <div class="landing-bg-orb landing-bg-orb--3"></div>
        <div class="landing-bg-orb landing-bg-orb--4"></div>
    </div>

    {{-- Navigation --}}
    <nav class="landing-nav">
        <a href="/" class="landing-nav-logo">
            <svg width="32" height="32" viewBox="0 0 40 40" fill="none">
                <rect width="40" height="40" rx="12" fill="url(#nav-grad)"/>
                <path d="M12 28V14a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H18l-4 4v-4h-2v2z" fill="white" opacity="0.9"/>
                <circle cx="18" cy="19" r="1.5" fill="url(#nav-grad)"/>
                <circle cx="22" cy="19" r="1.5" fill="url(#nav-grad)"/>
                <circle cx="26" cy="19" r="1.5" fill="url(#nav-grad)"/>
                <defs><linearGradient id="nav-grad" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#8B5CF6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            </svg>
            <span class="landing-nav-logo-text">GupShup</span>
        </a>
        <div class="landing-nav-links">
            <a href="/login" class="landing-nav-link landing-nav-link--ghost">Sign In</a>
            <a href="/register" class="landing-nav-link landing-nav-link--primary">Get Started</a>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="landing-hero">
        <h1>Your Conversations,<br>Truly Private</h1>
        <p>GupShup uses end-to-end encryption so only you and the person you're talking to can read your messages. Not even we can see them.</p>
        <div class="landing-cta-buttons">
            <a href="/register" class="landing-cta landing-cta--primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                Start Messaging Free
            </a>
            <a href="/login" class="landing-cta landing-cta--outline">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Sign In
            </a>
        </div>
    </section>

    {{-- Features --}}
    <section class="landing-features">
        <div class="landing-feature-card">
            <div class="landing-feature-icon landing-feature-icon--purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            </div>
            <h3 class="landing-feature-title">End-to-End Encrypted</h3>
            <p class="landing-feature-desc">Messages are encrypted on your device using AES-256-GCM before being sent. Only the recipient can decrypt them.</p>
        </div>

        <div class="landing-feature-card">
            <div class="landing-feature-icon landing-feature-icon--cyan">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h3 class="landing-feature-title">Zero-Knowledge Server</h3>
            <p class="landing-feature-desc">Your private keys never leave your device. The server only stores encrypted data it cannot read.</p>
        </div>

        <div class="landing-feature-card">
            <div class="landing-feature-icon landing-feature-icon--pink">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
            </div>
            <h3 class="landing-feature-title">Instant Messaging</h3>
            <p class="landing-feature-desc">Real-time message delivery with read receipts, online status indicators, and a beautiful modern interface.</p>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="landing-footer">
        <p>&copy; {{ date('Y') }} GupShup Messenger &mdash; Built with Laravel & MongoDB Atlas</p>
    </footer>
</div>
@endsection

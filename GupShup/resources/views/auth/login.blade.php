@extends('layouts.app')

@section('title', 'Login — GupShup')

@section('content')
<div class="auth-page">
    {{-- Animated background --}}
    <div class="auth-bg">
        <div class="auth-bg-orb auth-bg-orb--1"></div>
        <div class="auth-bg-orb auth-bg-orb--2"></div>
        <div class="auth-bg-orb auth-bg-orb--3"></div>
    </div>

    <div class="auth-container">
        {{-- Logo --}}
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                    <rect width="40" height="40" rx="12" fill="url(#logo-grad)"/>
                    <path d="M12 28V14a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H18l-4 4v-4h-2v2z" fill="white" opacity="0.9"/>
                    <circle cx="18" cy="19" r="1.5" fill="url(#logo-grad)"/>
                    <circle cx="22" cy="19" r="1.5" fill="url(#logo-grad)"/>
                    <circle cx="26" cy="19" r="1.5" fill="url(#logo-grad)"/>
                    <defs>
                        <linearGradient id="logo-grad" x1="0" y1="0" x2="40" y2="40">
                            <stop stop-color="#8B5CF6"/>
                            <stop offset="1" stop-color="#06B6D4"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1 class="auth-logo-text">GupShup</h1>
            <p class="auth-logo-subtitle">End-to-end encrypted messaging</p>
        </div>

        {{-- Login Card --}}
        <div class="auth-card">
            <h2 class="auth-card-title">Welcome back</h2>
            <p class="auth-card-desc">Sign in to continue your encrypted conversations</p>

            @if($errors->any())
                <div class="auth-error">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1a7 7 0 100 14A7 7 0 008 1zm0 10.5a.75.75 0 110-1.5.75.75 0 010 1.5zM8.75 4.5v4a.75.75 0 01-1.5 0v-4a.75.75 0 011.5 0z"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/login" id="login-form">
                @csrf
                <div class="auth-field">
                    <label for="email" class="auth-label">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                           class="auth-input" placeholder="you@example.com">
                </div>

                <div class="auth-field">
                    <label for="password" class="auth-label">Password</label>
                    <input type="password" id="password" name="password" required
                           class="auth-input" placeholder="••••••••">
                </div>

                <div class="auth-options">
                    <label class="auth-checkbox-label">
                        <input type="checkbox" name="remember" class="auth-checkbox">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="auth-btn" id="login-btn">
                    <span>Sign in</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </form>

            <div class="auth-footer">
                <span>Don't have an account?</span>
                <a href="/register" class="auth-link">Create one</a>
            </div>
        </div>

        {{-- E2EE Badge --}}
        <div class="auth-e2ee-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            <span>Protected with end-to-end encryption</span>
        </div>
    </div>
</div>
@endsection

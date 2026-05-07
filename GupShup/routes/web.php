<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing Page
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/chat');
    }
    return view('landing');
})->name('home');

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Main chat page
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');

    // API-style routes for AJAX
    Route::prefix('api')->group(function () {
        // Conversations
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::post('/conversations', [ChatController::class, 'createConversation']);

        // Messages
        Route::get('/messages/{conversationId}', [ChatController::class, 'getMessages']);
        Route::post('/messages', [ChatController::class, 'sendMessage']);
        Route::post('/messages/{conversationId}/read', [ChatController::class, 'markAsRead']);

        // Polling
        Route::get('/poll', [ChatController::class, 'pollMessages']);

        // Users
        Route::get('/users/search', [ChatController::class, 'searchUsers']);

        // Key management
        Route::post('/user/public-key', [AuthController::class, 'updatePublicKey']);

        // Heartbeat
        Route::post('/heartbeat', [ChatController::class, 'heartbeat']);
    });
});

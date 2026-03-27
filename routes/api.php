<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/health', [Api\HealthController::class, 'index']);
Route::get('/metrics', [Api\MetricsController::class, 'index']);

// Widget Config (public — API key validated inside handler)
Route::middleware('throttle:api')->group(function () {
    Route::get('/widget/config', [Api\WidgetConfigController::class, 'show']);
});

// Auth
Route::middleware('throttle:auth-login')->group(function () {
    Route::post('/auth/login', [Auth\LoginController::class, 'login']);
});

// Widget (API Key + XSS middleware)
Route::middleware(['api.key', 'xss', 'throttle:api'])->group(function () {
    // Room creation — stricter limit
    Route::middleware('throttle:room-creation')->group(function () {
        Route::post('/rooms', [Api\RoomController::class, 'store']);
    });

    Route::get('/rooms', [Api\RoomController::class, 'visitorRooms']);
    Route::post('/feedbacks', [Api\FeedbackController::class, 'store']);
    Route::post('/upload', [Api\UploadController::class, 'store']);
    Route::post('/events', [Api\EventController::class, 'store']);
    Route::get('/link-preview', [Api\LinkPreviewController::class, 'show']);
    Route::post('/rooms/{id}/transcript', [Api\TranscriptController::class, 'store']);

    // Messages — dedicated limit
    Route::middleware('throttle:message-send')->group(function () {
        Route::post('/rooms/{id}/messages', [Api\MessageController::class, 'store']);
    });
    Route::get('/rooms/{id}/messages', [Api\MessageController::class, 'index']);

    // Typing indicator — dedicated limit
    Route::middleware('throttle:typing-indicator')->group(function () {
        Route::post('/rooms/{id}/typing', [Api\TypingController::class, 'store']);
    });

    // Reactions
    Route::post('/rooms/{id}/reactions', [Api\ReactionController::class, 'store']);

    // Read receipts
    Route::post('/rooms/{id}/read', [Api\ReadReceiptController::class, 'store']);
});

// Broadcasting auth (custom — API key or bearer token)
Route::post('/broadcasting/auth', [\App\Http\Controllers\BroadcastAuthController::class, 'authenticate']);

// Admin (Built-in / Keycloak auth + XSS + admin rate limit)
Route::middleware(['admin.auth', 'xss', 'throttle:admin-api'])->prefix('admin')->group(function () {
    // Rooms
    Route::get('/rooms', [Admin\RoomController::class, 'index']);
    Route::patch('/rooms/{id}', [Admin\RoomController::class, 'update']);
    Route::post('/rooms/{id}/read', [Admin\RoomController::class, 'markRead']);
    Route::get('/rooms/{id}/messages', [Admin\RoomController::class, 'messages']);
    Route::post('/rooms/{id}/messages', [Admin\RoomController::class, 'sendMessage']);
    Route::post('/rooms/{id}/typing', [Admin\RoomController::class, 'sendTyping']);

    // Tenants
    Route::get('/tenants', [Admin\TenantController::class, 'index']);
    Route::post('/tenants', [Admin\TenantController::class, 'store']);
    Route::patch('/tenants/{id}', [Admin\TenantController::class, 'update']);
    Route::post('/tenants/{id}/rotate-key', [Admin\TenantController::class, 'rotateKey']);

    // Feedbacks
    Route::get('/feedbacks', [Admin\FeedbackController::class, 'index']);

    // Agents
    Route::get('/agents', [Admin\AgentController::class, 'index']);
    Route::get('/agents/online', [Admin\AgentController::class, 'online']);
    Route::post('/agents', [Admin\AgentController::class, 'store']);
    Route::delete('/agents/{id}', [Admin\AgentController::class, 'destroy']);

    // FAQ
    Route::get('/faq', [Admin\FaqController::class, 'index']);
    Route::post('/faq', [Admin\FaqController::class, 'store']);
    Route::delete('/faq/{id}', [Admin\FaqController::class, 'destroy']);

    // Stats
    Route::get('/stats', [Admin\StatsController::class, 'index']);
});

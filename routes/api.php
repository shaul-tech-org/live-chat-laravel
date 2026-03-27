<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/health', [Api\HealthController::class, 'index']);

// Auth
Route::post('/auth/login', [Auth\LoginController::class, 'login']);

// Widget (API Key middleware)
Route::middleware('api.key')->group(function () {
    Route::post('/rooms', [Api\RoomController::class, 'store']);
});

// Admin (Built-in / Keycloak auth)
Route::middleware('admin.auth')->prefix('admin')->group(function () {
    Route::get('/rooms', [Admin\RoomController::class, 'index']);
});

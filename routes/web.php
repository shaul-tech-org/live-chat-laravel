<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AgentDashboardController;
use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/admin', [AdminController::class, 'dashboard'])
    ->middleware('admin.web')
    ->name('admin.dashboard');

Route::get('/agent', [AgentDashboardController::class, 'index'])
    ->middleware('admin.web')
    ->name('agent.dashboard');

Route::get('/demo', fn () => view('pages.demo'))->name('demo');
Route::get('/m/{roomId}', fn (string $roomId) => view('pages.mobile-chat', ['roomId' => $roomId]))->name('mobile-chat');

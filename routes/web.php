<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BaileysAPIWebhookController;
use App\Http\Controllers\BaileysV7APIWebhookController;
use App\Livewire\Dashboard;
use App\Livewire\DeviceManager;
use App\Livewire\GroupManager;
use App\Livewire\MessageManager;
use App\Livewire\TokenManager;
use App\Livewire\UserManager;
use Illuminate\Support\Facades\Route;

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Authenticated Dashboard & Core Features
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/messages', MessageManager::class)->name('messages.index');
    Route::get('/devices', DeviceManager::class)->name('devices.index');

    // Legacy Filament URL redirect
    Route::redirect('/admin', '/');

    // Admin-only Settings
    Route::middleware('admin')->group(function () {
        Route::get('/tokens', TokenManager::class)->name('tokens.index');
        Route::get('/groups', GroupManager::class)->name('groups.index');
        Route::get('/users', UserManager::class)->name('users.index');
    });
});

// Webhooks (CSRF-exempt in VerifyCsrfToken)
Route::post('webhook/baileys', [BaileysAPIWebhookController::class, 'handle'])
    ->name('webhook.baileys');
Route::post('webhook/baileys-v7', [BaileysV7APIWebhookController::class, 'handle'])
    ->name('webhook.baileys.v7');

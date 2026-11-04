<?php

use App\Http\Controllers\Admin\ShelterModerationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Cabinet\ShelterApplicationController;
use App\Http\Controllers\CabinetStubController;
use Illuminate\Support\Facades\Route;

// Временная заглушка главной; настоящие страницы появляются с этапа 7.
Route::view('/', 'home')->name('home');

// --- Аутентификация -------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register')->name('register.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// --- Кабинет волонтёра (доступен всем вошедшим: права приюта ⊇ волонтёра) ----------------------
Route::middleware('auth')->prefix('cabinet')->name('cabinet.')->group(function () {
    Route::get('/', [CabinetStubController::class, 'volunteer'])->name('dashboard');

    Route::get('/shelter-request', [ShelterApplicationController::class, 'create'])->name('shelter-request');
    Route::post('/shelter-request', [ShelterApplicationController::class, 'store'])
        ->middleware('throttle:forms')->name('shelter-request.store');
});

// --- Кабинет приюта ---------------------------------------------------------------------------
Route::middleware('role:shelter_staff')->prefix('shelter')->name('shelter.')->group(function () {
    Route::get('/', [CabinetStubController::class, 'shelter'])->name('dashboard');
});

// --- Админка ----------------------------------------------------------------------------------
Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [CabinetStubController::class, 'admin'])->name('dashboard');

    Route::controller(ShelterModerationController::class)->prefix('shelters/{shelter}')->name('shelters.')
        ->group(function () {
            Route::post('/approve', 'approve')->name('approve');
            Route::post('/reject', 'reject')->name('reject');
            Route::post('/block', 'block')->name('block');
            Route::post('/unblock', 'unblock')->name('unblock');
        });
});

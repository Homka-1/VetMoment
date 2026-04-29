<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnimalRequestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;

Route::get('/', [AnimalRequestController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    
    Route::post('/requests', [AnimalRequestController::class, 'store'])->name('requests.store');
    Route::post('/requests/{animalRequest}/accept', [AnimalRequestController::class, 'accept'])->name('requests.accept');
    Route::post('/requests/{animalRequest}/close', [AnimalRequestController::class, 'close'])->name('requests.close');
});

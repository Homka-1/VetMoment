<?php

use App\Http\Controllers\Admin\ShelterModerationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Cabinet\FavoriteController as CabinetFavoriteController;
use App\Http\Controllers\Cabinet\ShelterApplicationController;
use App\Http\Controllers\CabinetStubController;
use App\Http\Controllers\Pets\AdoptionApplicationController;
use App\Http\Controllers\Pets\FavoriteController;
use App\Http\Controllers\Pets\PetController;
use App\Http\Controllers\Shelter\ApplicationController as ShelterApplicationsController;
use App\Http\Controllers\Shelter\PetController as ShelterPetController;
use App\Http\Controllers\StyleguideController;
use Illuminate\Support\Facades\Route;

// Главная: на этапе 7 — статичные блоки (герой, три пути помощи), блоки с данными — после этапов 8–10.
Route::view('/', 'home')->name('home');

// Витрина UI-кита — только local/testing (в production контроллер отдаёт 404).
Route::get('/styleguide', StyleguideController::class)->name('styleguide');

// --- Питомцы: каталог, карточка, заявки, избранное -----------------------------------------
Route::get('/pets', [PetController::class, 'index'])->name('pets.index');
Route::get('/pets/{pet}', [PetController::class, 'show'])->name('pets.show');

Route::middleware('auth')->group(function () {
    Route::get('/pets/{pet}/apply', [AdoptionApplicationController::class, 'create'])->name('pets.apply');
    Route::post('/pets/{pet}/apply', [AdoptionApplicationController::class, 'store'])
        ->middleware('throttle:forms')->name('pets.apply.store');
    Route::post('/applications/{application}/withdraw', [AdoptionApplicationController::class, 'withdraw'])
        ->name('applications.withdraw');
    Route::post('/pets/{pet}/favorite', [FavoriteController::class, 'toggle'])->name('pets.favorite');
});

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
    Route::get('/favorites', [CabinetFavoriteController::class, 'index'])->name('favorites');

    Route::get('/shelter-request', [ShelterApplicationController::class, 'create'])->name('shelter-request');
    Route::post('/shelter-request', [ShelterApplicationController::class, 'store'])
        ->middleware('throttle:forms')->name('shelter-request.store');
});

// --- Кабинет приюта ---------------------------------------------------------------------------
Route::middleware('role:shelter_staff')->prefix('shelter')->name('shelter.')->group(function () {
    Route::get('/', [CabinetStubController::class, 'shelter'])->name('dashboard');

    Route::resource('pets', ShelterPetController::class)->except('show');

    Route::get('/applications', [ShelterApplicationsController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [ShelterApplicationsController::class, 'show'])->name('applications.show');
    Route::patch('/applications/{application}', [ShelterApplicationsController::class, 'update'])->name('applications.update');
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

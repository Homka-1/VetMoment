<?php

use App\Http\Controllers\Admin\ApplicationController as AdminApplicationController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PetController as AdminPetController;
use App\Http\Controllers\Admin\ShelterController as AdminShelterController;
use App\Http\Controllers\Admin\ShelterModerationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Cabinet\ApplicationController as CabinetApplicationController;
use App\Http\Controllers\Cabinet\DashboardController as CabinetDashboardController;
use App\Http\Controllers\Cabinet\DonationController as CabinetDonationController;
use App\Http\Controllers\Cabinet\FavoriteController as CabinetFavoriteController;
use App\Http\Controllers\Cabinet\ProfileController as CabinetProfileController;
use App\Http\Controllers\Cabinet\ShelterApplicationController;
use App\Http\Controllers\Cabinet\WalkController as CabinetWalkController;
use App\Http\Controllers\Campaigns\CampaignController;
use App\Http\Controllers\Donations\DonationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Payments\FakePaymentController;
use App\Http\Controllers\Payments\WebhookController;
use App\Http\Controllers\Pets\AdoptionApplicationController;
use App\Http\Controllers\Pets\FavoriteController;
use App\Http\Controllers\Pets\PetController;
use App\Http\Controllers\Public\ShelterController as PublicShelterController;
use App\Http\Controllers\Shelter\ApplicationController as ShelterApplicationsController;
use App\Http\Controllers\Shelter\CampaignController as ShelterCampaignController;
use App\Http\Controllers\Shelter\CampaignReportController;
use App\Http\Controllers\Shelter\DashboardController as ShelterDashboardController;
use App\Http\Controllers\Shelter\DonationController as ShelterDonationController;
use App\Http\Controllers\Shelter\PetController as ShelterPetController;
use App\Http\Controllers\Shelter\ProfileController as ShelterProfileController;
use App\Http\Controllers\Shelter\WalkBookingController as ShelterWalkBookingController;
use App\Http\Controllers\Shelter\WalkSlotController as ShelterWalkSlotController;
use App\Http\Controllers\Shelter\WalkTemplateController;
use App\Http\Controllers\StyleguideController;
use App\Http\Controllers\Walks\BookingController as WalkBookingController;
use App\Http\Controllers\Walks\WalkController;
use Illuminate\Support\Facades\Route;

// Главная: герой, три пути помощи и блоки с данными.
Route::get('/', HomeController::class)->name('home');

// Информационные страницы.
Route::view('/help', 'help')->name('help');
Route::view('/privacy', 'privacy')->name('privacy');

// Приюты: список и страница приюта.
Route::get('/shelters', [PublicShelterController::class, 'index'])->name('shelters.index');
Route::get('/shelters/{shelter}', [PublicShelterController::class, 'show'])->name('shelters.show');

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

// --- Сборы и пожертвования ----------------------------------------------------------------------
Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
Route::get('/campaigns/{campaign}/donations', [CampaignController::class, 'donations'])->name('campaigns.donations');

Route::controller(DonationController::class)->group(function () {
    Route::get('/campaigns/{campaign}/donate', 'campaignForm')->name('campaigns.donate');
    Route::post('/campaigns/{campaign}/donate', 'storeForCampaign')->middleware('throttle:forms')->name('campaigns.donate.store');
    Route::get('/shelters/{shelter}/donate', 'shelterForm')->name('shelters.donate');
    Route::post('/shelters/{shelter}/donate', 'storeForShelter')->middleware('throttle:forms')->name('shelters.donate.store');
    Route::get('/donate', 'chooseFund')->name('donate.fund');
    Route::get('/donations/{donation}', 'show')->name('donations.show');
});

// Платёжный слой: демо-«банк» FakePaymentGateway и webhook провайдеров (без CSRF — см. bootstrap/app.php).
Route::get('/payments/fake/{donation}', [FakePaymentController::class, 'show'])->name('payments.fake.show');
Route::post('/payments/fake/{donation}', [FakePaymentController::class, 'pay'])->middleware('throttle:forms')->name('payments.fake.pay');
Route::post('/payments/webhook/{provider}', WebhookController::class)->name('payments.webhook');

// --- Запись на выгул: приют и время → подтверждение → запись ----------------------------------
Route::get('/walks', [WalkController::class, 'index'])->name('walks.index');
Route::get('/walks/continue', [WalkController::class, 'continue'])->name('walks.continue');

Route::middleware('auth')->group(function () {
    Route::get('/walks/slots/{slot}', [WalkController::class, 'show'])->name('walks.slots.show');
    Route::post('/walks/slots/{slot}/book', [WalkController::class, 'book'])->middleware('throttle:forms')->name('walks.book');

    Route::get('/walks/bookings/{booking}', [WalkBookingController::class, 'show'])->name('walks.bookings.show');
    Route::post('/walks/bookings/{booking}/cancel', [WalkBookingController::class, 'cancel'])->name('walks.bookings.cancel');
    Route::get('/walks/bookings/{booking}/calendar.ics', [WalkBookingController::class, 'calendar'])->name('walks.bookings.calendar');
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
    Route::get('/', CabinetDashboardController::class)->name('dashboard');
    Route::get('/applications', [CabinetApplicationController::class, 'index'])->name('applications');
    Route::get('/profile', [CabinetProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [CabinetProfileController::class, 'update'])->middleware('throttle:forms')->name('profile.update');
    Route::put('/profile/password', [CabinetProfileController::class, 'password'])->middleware('throttle:forms')->name('profile.password');
    Route::get('/favorites', [CabinetFavoriteController::class, 'index'])->name('favorites');
    Route::get('/donations', [CabinetDonationController::class, 'index'])->name('donations');
    Route::get('/walks', [CabinetWalkController::class, 'index'])->name('walks');

    Route::get('/shelter-request', [ShelterApplicationController::class, 'create'])->name('shelter-request');
    Route::post('/shelter-request', [ShelterApplicationController::class, 'store'])
        ->middleware('throttle:forms')->name('shelter-request.store');
});

// --- Кабинет приюта ---------------------------------------------------------------------------
Route::middleware('role:shelter_staff')->prefix('shelter')->name('shelter.')->group(function () {
    Route::get('/', ShelterDashboardController::class)->name('dashboard');
    Route::get('/profile', [ShelterProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ShelterProfileController::class, 'update'])->middleware('throttle:forms')->name('profile.update');

    Route::resource('pets', ShelterPetController::class)->except('show');

    Route::get('/applications', [ShelterApplicationsController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [ShelterApplicationsController::class, 'show'])->name('applications.show');
    Route::patch('/applications/{application}', [ShelterApplicationsController::class, 'update'])->name('applications.update');

    Route::resource('campaigns', ShelterCampaignController::class)->except('show');
    Route::post('/campaigns/{campaign}/close', [ShelterCampaignController::class, 'close'])->name('campaigns.close');
    Route::get('/campaigns/{campaign}/reports', [CampaignReportController::class, 'index'])->name('campaigns.reports.index');
    Route::post('/campaigns/{campaign}/reports', [CampaignReportController::class, 'store'])->name('campaigns.reports.store');
    Route::delete('/campaigns/{campaign}/reports/{report}', [CampaignReportController::class, 'destroy'])->name('campaigns.reports.destroy');

    Route::get('/donations', [ShelterDonationController::class, 'index'])->name('donations.index');
    Route::get('/donations/export', [ShelterDonationController::class, 'export'])->name('donations.export');

    Route::get('/slots', [ShelterWalkSlotController::class, 'index'])->name('slots.index');
    Route::post('/slots', [ShelterWalkSlotController::class, 'store'])->name('slots.store');
    Route::get('/slots/templates', [WalkTemplateController::class, 'index'])->name('slot-templates.index');
    Route::post('/slots/templates', [WalkTemplateController::class, 'store'])->name('slot-templates.store');
    Route::delete('/slots/templates/{template}', [WalkTemplateController::class, 'destroy'])->name('slot-templates.destroy');
    Route::get('/slots/{slot}', [ShelterWalkSlotController::class, 'show'])->name('slots.show');
    Route::patch('/slots/{slot}', [ShelterWalkSlotController::class, 'update'])->name('slots.update');
    Route::post('/slots/{slot}/cancel', [ShelterWalkSlotController::class, 'cancel'])->name('slots.cancel');

    Route::get('/bookings', [ShelterWalkBookingController::class, 'index'])->name('bookings.index');
    Route::patch('/bookings/{booking}', [ShelterWalkBookingController::class, 'update'])->name('bookings.update');
});

// --- Админка ----------------------------------------------------------------------------------
Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/shelters', [AdminShelterController::class, 'index'])->name('shelters.index');

    Route::get('/pets', [AdminPetController::class, 'index'])->name('pets.index');
    Route::post('/pets/{pet}/hide', [AdminPetController::class, 'hide'])->name('pets.hide');
    Route::post('/pets/{pet}/restore', [AdminPetController::class, 'restore'])->name('pets.restore');

    Route::get('/campaigns', [AdminCampaignController::class, 'index'])->name('campaigns.index');
    Route::post('/campaigns/{campaign}/hide', [AdminCampaignController::class, 'hide'])->name('campaigns.hide');
    Route::post('/campaigns/{campaign}/restore', [AdminCampaignController::class, 'restore'])->name('campaigns.restore');

    Route::get('/applications', [AdminApplicationController::class, 'index'])->name('applications.index');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
    Route::post('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');

    Route::controller(ShelterModerationController::class)->prefix('shelters/{shelter}')->name('shelters.')
        ->group(function () {
            Route::post('/approve', 'approve')->name('approve');
            Route::post('/reject', 'reject')->name('reject');
            Route::post('/block', 'block')->name('block');
            Route::post('/unblock', 'unblock')->name('unblock');
        });
});

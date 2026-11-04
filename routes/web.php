<?php

use Illuminate\Support\Facades\Route;

// Временная заглушка главной; настоящие страницы появляются с этапа 7.
Route::view('/', 'home')->name('home');

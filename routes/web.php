<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('username', 'pages::auth.choose-username')
    ->middleware('auth')
    ->name('username.create');

Route::middleware(['auth', 'verified', 'has-username'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('partner/search', 'pages::partner.search')->name('partner.search');
});

require __DIR__.'/settings.php';

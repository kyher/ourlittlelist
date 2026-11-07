<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::livewire('username', 'pages::auth.choose-username')
    ->middleware('auth')
    ->name('username.create');

Route::middleware(['auth', 'verified', 'has-username'])->group(function () {
    Route::livewire('partner/search', 'pages::partner.search')->name('partner.search');
    Route::livewire('couple', 'pages::couple.show')->name('couple.show');

    Route::middleware('in-couple')->group(function () {
        Route::livewire('reviews', 'pages::reviews.index')->name('reviews.index');
        Route::livewire('reviews/new', 'pages::reviews.create')->name('reviews.create');
        Route::livewire('reviews/{item}', 'pages::reviews.show')->whereNumber('item')->name('reviews.show');
    });
});

require __DIR__.'/settings.php';

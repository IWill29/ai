<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::inertia('chat', 'chat/index')->name('chat.index');
    Route::inertia('stores', 'stores/index')->name('stores.index');
    Route::inertia('stores/connect', 'stores/connect')->name('stores.connect');
    Route::inertia('settings/openrouter', 'settings/open-router')->name('settings.openrouter');
    Route::inertia('settings/billing', 'settings/billing')->name('settings.billing');
});

require __DIR__.'/settings.php';

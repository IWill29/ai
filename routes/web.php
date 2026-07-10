<?php

use App\Http\Controllers\Settings\OpenRouterController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::inertia('chat', 'chat/index')->name('chat.index');
    Route::inertia('stores', 'stores/index')->name('stores.index');
    Route::inertia('stores/connect', 'stores/connect')->name('stores.connect');
    Route::get('settings/openrouter', [OpenRouterController::class, 'edit'])->name('settings.openrouter');
    Route::post('settings/openrouter', [OpenRouterController::class, 'store'])
        ->middleware('throttle:openrouter-validate')
        ->name('settings.openrouter.store');
    Route::inertia('settings/billing', 'settings/billing')->name('settings.billing');
});

require __DIR__.'/settings.php';

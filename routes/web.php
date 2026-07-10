<?php

use App\Http\Controllers\Settings\OpenRouterController;
use App\Http\Controllers\Stores\ConnectStoreController;
use App\Http\Controllers\Stores\StoreController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::inertia('chat', 'chat/index')->name('chat.index');
    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('stores/connect', [ConnectStoreController::class, 'create'])->name('stores.connect');
    Route::post('stores/connect', [ConnectStoreController::class, 'store'])->name('stores.store');
    Route::delete('stores/{storeConnection}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::put('stores/{storeConnection}/reconnect', [StoreController::class, 'reconnect'])->name('stores.reconnect');
    Route::get('settings/openrouter', [OpenRouterController::class, 'edit'])->name('settings.openrouter');
    Route::post('settings/openrouter', [OpenRouterController::class, 'store'])
        ->middleware('throttle:openrouter-validate')
        ->name('settings.openrouter.store');
    Route::inertia('settings/billing', 'settings/billing')->name('settings.billing');
});

require __DIR__.'/settings.php';

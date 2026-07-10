<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Settings\OpenRouterController;
use App\Http\Controllers\Stores\ConnectStoreController;
use App\Http\Controllers\Stores\StoreController;
use App\Http\Controllers\Webhooks\ShopifyWebhookController;
use App\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('/webhooks/shopify/{storeConnectionId}', [ShopifyWebhookController::class, 'handle'])
    ->middleware(VerifyShopifyWebhook::class)
    ->name('webhooks.shopify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::get('stores/connect', [ConnectStoreController::class, 'create'])->name('stores.connect');
    Route::post('stores/connect', [ConnectStoreController::class, 'store'])->name('stores.store');
    Route::post('stores/{storeConnection}/sync', [StoreController::class, 'sync'])->name('stores.sync');
    Route::get('stores/{storeConnection}/sync-status', [StoreController::class, 'syncStatus'])->name('stores.sync-status');
    Route::delete('stores/{storeConnection}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::put('stores/{storeConnection}/reconnect', [StoreController::class, 'reconnect'])->name('stores.reconnect');
    Route::get('settings/openrouter', [OpenRouterController::class, 'edit'])->name('settings.openrouter');
    Route::post('settings/openrouter', [OpenRouterController::class, 'store'])
        ->middleware('throttle:openrouter-validate')
        ->name('settings.openrouter.store');
    Route::inertia('settings/billing', 'settings/billing')->name('settings.billing');
});

require __DIR__.'/settings.php';

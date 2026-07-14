<?php

use App\Http\Controllers\Chat\AgentStreamController;
use App\Http\Controllers\Chat\AttachmentController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\ConfirmationController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\ModelAllowListController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\OpenRouterController;
use App\Http\Controllers\Stores\ConnectStoreController;
use App\Http\Controllers\Stores\StoreController;
use App\Http\Controllers\Webhooks\ShopifyWebhookController;
use App\Http\Middleware\CacheLandingResponse;
use App\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])
    ->middleware(CacheLandingResponse::class)
    ->name('home');

Route::post('/webhooks/shopify/{storeConnectionId}', [ShopifyWebhookController::class, 'handle'])
    ->middleware([VerifyShopifyWebhook::class, 'throttle:webhooks'])
    ->name('webhooks.shopify');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('agent/models', ModelAllowListController::class)->name('agent.models');
    Route::post('conversations', [ConversationController::class, 'store'])
        ->middleware('throttle:agent')
        ->name('conversations.store');
    Route::post('conversations/{conversation}/stream', [AgentStreamController::class, 'store'])
        ->middleware('throttle:agent')
        ->name('conversations.stream');
    Route::post('conversations/{conversation}/stream/resume', [AgentStreamController::class, 'resume'])
        ->middleware('throttle:agent')
        ->name('conversations.stream.resume');
    Route::post('action-steps/{actionStep}/confirm', [ConfirmationController::class, 'store'])
        ->middleware('throttle:agent')
        ->name('action-steps.confirm');
    Route::post('attachments', [AttachmentController::class, 'store'])
        ->middleware('throttle:attachments')
        ->name('attachments.store');
    Route::get('attachments/{attachment}/preview', [AttachmentController::class, 'preview'])
        ->name('attachments.preview');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    Route::get('settings/billing', [BillingController::class, 'index'])->name('settings.billing');
});

require __DIR__.'/settings.php';

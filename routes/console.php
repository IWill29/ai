<?php

use App\Domains\Chat\Jobs\PurgeStaleAttachmentsJob;
use App\Domains\Stores\Jobs\NightlyReconcileJob;
use App\Domains\Stores\Jobs\RetryPendingWebhooksJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new NightlyReconcileJob)
    ->dailyAt('03:00')
    ->timezone('UTC')
    ->withoutOverlapping();

Schedule::job(new RetryPendingWebhooksJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('webhooks:prune')->daily();

Schedule::job(new PurgeStaleAttachmentsJob)
    ->daily()
    ->withoutOverlapping();

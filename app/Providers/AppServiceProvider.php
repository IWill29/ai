<?php

namespace App\Providers;

use App\Domains\Chat\Models\Conversation;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Policies\StoreConnectionPolicy;
use App\Policies\ConversationPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDevCommands();
        $this->configureDefaults();
        $this->configurePolicies();
        $this->configureRouteBindings();
    }

    protected function configureRouteBindings(): void
    {
        Route::bind('storeConnection', function (string $value): StoreConnection {
            $user = auth()->user();

            if ($user === null || $user->account_id === null) {
                abort(403);
            }

            return StoreConnection::query()
                ->where('account_id', $user->account_id)
                ->whereKey($value)
                ->firstOrFail();
        });
    }

    protected function configurePolicies(): void
    {
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(StoreConnection::class, StoreConnectionPolicy::class);
    }

    /**
     * Exclude Pail from `php artisan dev` when pcntl is unavailable (e.g. Windows).
     */
    protected function configureDevCommands(): void
    {
        if (! extension_loaded('pcntl')) {
            DevCommands::except('logs');
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        if ($rootUrl = config('app.url')) {
            URL::forceRootUrl(rtrim((string) $rootUrl, '/'));
        }

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

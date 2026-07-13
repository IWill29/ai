<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class VerifyUserEmailCommand extends Command
{
    protected $signature = 'user:verify-email {email : The user email address}';

    protected $description = 'Mark a user email as verified (local development only)';

    public function handle(): int
    {
        if (! app()->isLocal()) {
            $this->error('This command is only available when APP_ENV=local.');

            return self::FAILURE;
        }

        $email = strtolower((string) $this->argument('email'));

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found for [{$email}].");

            return self::FAILURE;
        }

        if ($user->hasVerifiedEmail()) {
            $this->info("{$email} is already verified.");

            return self::SUCCESS;
        }

        $user->markEmailAsVerified();

        $this->info("Verified {$email}.");

        return self::SUCCESS;
    }
}

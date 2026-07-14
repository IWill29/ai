<?php

namespace App\Http\Controllers\Settings;

use App\Domains\Billing\Actions\RecordAuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/security', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request, RecordAuditAction $recordAudit): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            'password' => $request->password,
        ]);

        $recordAudit->execute(
            accountId: (string) $user->account_id,
            userId: $user->id,
            storeConnectionId: null,
            action: 'password.update',
            context: [],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}

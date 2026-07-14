<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domains\Accounts\Contracts\AccountService;
use App\Domains\Billing\Actions\RecordAuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request, RecordAuditAction $recordAudit): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        $fields = [];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            $fields[] = 'email';
        }

        if ($user->isDirty('name')) {
            $fields[] = 'name';
        }

        $user->save();

        if (in_array('email', $fields, true)) {
            $user->sendEmailVerificationNotification();
        }

        if ($fields !== []) {
            $recordAudit->execute(
                accountId: (string) $user->account_id,
                userId: $user->id,
                storeConnectionId: null,
                action: 'profile.update',
                context: ['fields' => $fields],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return to_route('profile.edit');
    }

    public function destroy(ProfileDeleteRequest $request, AccountService $accounts, RecordAuditAction $recordAudit): RedirectResponse
    {
        $user = $request->user();
        $accountId = $user->account_id;

        if ($accountId !== null) {
            $recordAudit->execute(
                accountId: (string) $accountId,
                userId: $user->id,
                storeConnectionId: null,
                action: 'account.delete',
                context: [],
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($accountId !== null) {
            $accounts->deleteAccount($accountId);
        } else {
            $user->delete();
        }

        return redirect('/');
    }
}

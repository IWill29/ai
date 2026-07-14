<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $account = $request->user()->account;

        if ($account === null) {
            abort(403);
        }

        $this->authorize('viewBilling', $account);

        return Inertia::render('settings/billing');
    }
}

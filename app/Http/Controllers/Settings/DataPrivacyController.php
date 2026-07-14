<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DataPrivacyController extends Controller
{
    public function index(Request $request): Response
    {
        $account = $request->user()->account;

        if ($account === null) {
            abort(403);
        }

        $this->authorize('view', $account);

        /** @var array{title: string, updated_at: string, sections: list<array{heading: string, body: string}>} $note */
        $note = config('gdpr.data_processing_note', []);

        return Inertia::render('settings/data-privacy', [
            'note' => $note,
        ]);
    }
}

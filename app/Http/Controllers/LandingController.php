<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LandingController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('landing/index', [
            'plans' => config('marketing.plans', []),
            'faqs' => config('marketing.faqs', []),
            'agentSteps' => config('marketing.agent_steps', []),
            'canonicalUrl' => url('/'),
        ]);
    }
}

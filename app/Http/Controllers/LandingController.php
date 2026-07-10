<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Billing\Models\Plan;
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

        /** @var list<array{slug: string, name: string, price_cents: int, currency: string, store_limit: int|null, monthly_message_limit: int|null}> $plans */
        $plans = Plan::query()
            ->orderBy('price_cents')
            ->get(['slug', 'name', 'price_cents', 'currency', 'store_limit', 'monthly_message_limit'])
            ->map(fn (Plan $plan): array => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'price_cents' => $plan->price_cents,
                'currency' => $plan->currency,
                'store_limit' => $plan->store_limit,
                'monthly_message_limit' => $plan->monthly_message_limit,
            ])
            ->values()
            ->all();

        return Inertia::render('landing/index', [
            'plans' => $plans,
            'faqs' => config('marketing.faqs', []),
            'canonicalUrl' => url('/'),
        ]);
    }
}

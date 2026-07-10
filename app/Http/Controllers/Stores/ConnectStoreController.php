<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stores;

use App\Domains\Billing\Exceptions\StoreLimitReachedException;
use App\Domains\Stores\Actions\ConnectShopifyStoreAction;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Models\StoreConnection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stores\ConnectShopifyStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectStoreController extends Controller
{
    public function create(Request $request): Response
    {
        $this->authorize('create', StoreConnection::class);

        return Inertia::render('stores/connect', [
            'scopes' => config('shopify.scopes', []),
        ]);
    }

    public function store(
        ConnectShopifyStoreRequest $request,
        ConnectShopifyStoreAction $connectShopifyStore,
    ): RedirectResponse {
        try {
            $connectShopifyStore->execute(
                accountId: $request->user()->account_id,
                domain: $request->validated('domain'),
                accessToken: $request->validated('access_token'),
                apiSecret: $request->validated('api_secret'),
                name: $request->validated('name'),
            );
        } catch (InvalidCredentialsException $exception) {
            return back()->withErrors([
                'access_token' => $exception->getMessage(),
            ]);
        } catch (StoreLimitReachedException $exception) {
            return back()->withErrors([
                'domain' => $exception->getMessage(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Shopify store connected successfully.'),
        ]);

        return to_route('stores.index');
    }
}

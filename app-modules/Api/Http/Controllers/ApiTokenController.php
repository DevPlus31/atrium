<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use App\Models\User;
use App\Modules\Toast;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Api\Actions\CreateApiToken;
use Modules\Api\Actions\RevokeApiToken;
use Modules\Api\Data\ApiTokenData;
use Modules\Api\Http\Requests\CreateApiTokenRequest;

final readonly class ApiTokenController
{
    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('api::tokens', [
            'tokens' => $user->tokens()
                ->latest()
                ->get()
                ->map(ApiTokenData::fromModel(...))
                ->all(),
            'abilities' => CreateApiTokenRequest::grantableAbilities($user),
            'lifetimes' => CreateApiTokenRequest::LIFETIMES,
        ]);
    }

    /**
     * The new token is shown once, through a flash message, and never again.
     */
    public function store(CreateApiTokenRequest $request, #[CurrentUser] User $user, CreateApiToken $action): RedirectResponse
    {
        $token = $action->handle(
            $user,
            $request->name(),
            $request->abilities(),
            $request->expiresAt(),
        );

        Inertia::flash('apiToken', $token->plainTextToken);

        return to_route('api-tokens.index');
    }

    public function destroy(#[CurrentUser] User $user, string $token, RevokeApiToken $action): RedirectResponse
    {
        $action->handle($user, $user->tokens()->findOrFail($token));

        Toast::success(__('Token revoked.'));

        return to_route('api-tokens.index');
    }
}

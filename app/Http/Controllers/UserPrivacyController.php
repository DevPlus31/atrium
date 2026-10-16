<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The account's privacy settings: download your data, cookie choices, the
 * legal documents you accepted and deleting the account.
 */
final readonly class UserPrivacyController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('user-privacy/edit', [
            'termsAcceptedAt' => $user->terms_accepted_at?->toIso8601String(),
        ]);
    }
}

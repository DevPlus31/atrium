<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Data\PasskeyData;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final readonly class UserPasskeysController implements HasMiddleware
{
    public static function middleware(): array
    {
        return Features::optionEnabled(Features::passkeys(), 'confirmPassword')
            ? [new Middleware('password.confirm', only: ['show'])]
            : [];
    }

    public function show(#[CurrentUser] User $user): Response
    {
        return Inertia::render('user-passkeys/show', [
            'canManagePasskeys' => Features::canManagePasskeys(),
            'passkeys' => PasskeyData::collect($user->passkeys()->latest()->get()),
        ]);
    }
}

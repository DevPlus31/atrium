<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\User;

/**
 * Where a module's navigation items and dashboard widgets appear: the admin
 * panel, the member area that users without panel access see, or the
 * account settings every user has (navigation only).
 */
enum Area: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Settings = 'settings';

    /**
     * The area the given user works in: the admin panel when the panel gate
     * lets them in, the member area otherwise.
     */
    public static function for(User $user): self
    {
        return $user->can(User::PANEL_ABILITY) ? self::Admin : self::Member;
    }
}

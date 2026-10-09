<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Area;
use App\Models\User;
use App\Modules\Data\NavItemData;
use App\Modules\NavRegistry;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class LandingController
{
    /**
     * Land on the first page a module offers the user in their area (the
     * admin panel for panel users, the member area otherwise), or on their
     * profile when no module offers one. The shell names no module.
     */
    public function __invoke(#[CurrentUser] User $user, NavRegistry $nav): RedirectResponse
    {
        $home = array_find(
            $nav->itemsFor($user, Area::for($user)),
            static fn (NavItemData $item): bool => ! $item->external,
        );

        return $home === null ? to_route('user-profile.edit') : redirect($home->href);
    }
}

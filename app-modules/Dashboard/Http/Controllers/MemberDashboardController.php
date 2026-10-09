<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Enums\Area;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Dashboard\Queries\DashboardWidgetsQuery;

final readonly class MemberDashboardController
{
    /**
     * The home of users without panel access; panel users have the admin
     * dashboard instead.
     */
    public function __invoke(#[CurrentUser] User $user, DashboardWidgetsQuery $widgets): Response|RedirectResponse
    {
        if (Area::for($user) === Area::Admin) {
            return to_route('admin.dashboard.index');
        }

        return Inertia::render('dashboard::home', $widgets->for($user, Area::Member));
    }
}

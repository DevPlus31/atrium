<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Enums\Area;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Dashboard\Queries\DashboardWidgetsQuery;

final readonly class DashboardController
{
    #[Authorize('dashboard.view')]
    public function index(#[CurrentUser] User $user, DashboardWidgetsQuery $widgets): Response
    {
        return Inertia::render('dashboard::index', $widgets->for($user, Area::Admin));
    }
}

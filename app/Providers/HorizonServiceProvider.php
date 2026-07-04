<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Configure the Horizon authorization services without any local-environment bypass.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(static fn (Request $request): bool => Gate::check('viewHorizon'));
    }

    /**
     * Register the Horizon gate. Applies in every environment, production included.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', static fn (User $user): bool => $user->can('system.horizon.view'));
    }
}

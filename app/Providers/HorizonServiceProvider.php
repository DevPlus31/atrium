<?php

declare(strict_types=1);

namespace App\Providers;

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
     * The System module defines `viewHorizon` with its other tool gates.
     * Without that module the gate stays undefined, so Horizon is closed
     * to everyone, in every environment.
     */
    protected function gate(): void
    {
        //
    }
}

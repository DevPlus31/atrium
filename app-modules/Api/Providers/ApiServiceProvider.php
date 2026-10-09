<?php

declare(strict_types=1);

namespace Modules\Api\Providers;

use App\Enums\Area;
use App\Modules\ModuleServiceProvider;
use App\Modules\NavRegistry;

/**
 * Personal access tokens (Settings → API tokens) and the account endpoint
 * GET /api/v1/user. Sanctum itself, the token-aware permission gate and the
 * loading of every module's routes/api.php stay in the shell, so a project
 * without an API removes this module and nothing else.
 */
final class ApiServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'api';
    }

    protected function navigation(NavRegistry $nav): void
    {
        $nav->add(
            module: $this->name(),
            label: 'API tokens',
            routeName: 'api-tokens.index',
            icon: 'key-round',
            sort: 100,
            area: Area::Settings,
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\BareModule\Providers;

use App\Modules\ModuleServiceProvider;

final class BareModuleServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'bare-module';
    }
}

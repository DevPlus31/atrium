<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Providers\TypeScriptTransformerServiceProvider::class,
    Modules\Dashboard\Providers\DashboardServiceProvider::class,
    Modules\Roles\Providers\RolesServiceProvider::class,
    Modules\System\Providers\SystemServiceProvider::class,
    Modules\Users\Providers\UsersServiceProvider::class,
];

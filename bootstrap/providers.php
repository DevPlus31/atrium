<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\TypeScriptTransformerServiceProvider::class,
    Modules\Users\Providers\UsersServiceProvider::class,
];

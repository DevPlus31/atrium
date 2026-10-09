<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Disabled Modules
    |--------------------------------------------------------------------------
    |
    | Modules switched off for everyone, by name (a module provider's name(),
    | e.g. "shop,catalog"). Their routes answer 404 and their navigation and
    | dashboard widgets disappear, but their code, permissions and tables
    | stay, so switching them back on loses nothing. To remove a module for
    | good, use `php artisan module:remove`.
    |
    */

    'disabled' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('MODULES_DISABLED', '')),
    ))),

];

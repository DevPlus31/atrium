# App/Actions guidelines

- This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- Actions live in `app/Actions` (the shell) or `app-modules/<Module>/Actions` (a module); they are named based on what they do, with no suffix.
- Actions will be called from many different places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- Create dedicated Action classes for business logic with a single `handle()` method.
- Inject dependencies via constructor using private properties.
- Create shell actions with `php artisan make:action "{name}" --no-interaction`. Module actions come from `php artisan make:aggregate <Module> <Aggregate>` or are written by hand in the module's `Actions/` folder (namespace `Modules\<Module>\Actions`), each with a unit test.
- Wrap complex operations in `DB::transaction()` within actions when multiple models are involved.
- Actions own every write. When the feature has business rules, the rule lives on the model (or a value object) and the action locks, calls it, saves through the repository and flushes domain events after commit (see README "Domain-Driven Design").
- Some actions won't require dependencies via `__construct` and they can use just the `handle()` method.

@boostsnippet('Example action class', 'php')
<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class CreateFavorite
{
    public function __construct(private FavoriteService $favorites)
    {
        //
    }

    public function handle(User $user, string $favorite): bool
    {
        return $this->favorites->add($user, $favorite);
    }
}
@endboostsnippet

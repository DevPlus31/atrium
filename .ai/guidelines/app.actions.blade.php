# App/Actions guidelines

- This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- Actions live in `app/Actions` (the shell) or `app-modules/<Module>/Actions` (a module); they are named based on what they do, with no suffix.
- Actions will be called from many different places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- Create dedicated Action classes for business logic, invokable: one public `__invoke()` method and nothing else public besides the constructor (`tests/Unit/ArchTest.php` enforces it).
- Call an action by invoking it: `$action(...)` for an injected one, `resolve(CreateProduct::class)(...)` elsewhere, `($this->createUser)(...)` for a constructor-injected collaborator, and `$deleteProduct(...)` for a first-class callable (e.g. the closure handed to `DeleteEach`). Named arguments work as usual: `$action(name: $request->name())`.
- Inject dependencies via constructor using private properties.
- Create shell actions with `php artisan make:action "{name}" --no-interaction` (it appends an `Action` suffix; drop it). Module actions come from `php artisan make:aggregate <Module> <Aggregate>` or are written by hand in the module's `Actions/` folder (namespace `Modules\<Module>\Actions`), each with a unit test.
- Do every write inside `DB::transaction()` and log it there with `App\Modules\AuditLog::record()`. Compose existing actions rather than duplicating them (bulk deletes run the single-row action through `App\Actions\DeleteEach`).
- Actions own every write. When the feature has business rules, the rule lives on the model (or a value object) and the action locks, calls it, saves through the repository and flushes domain events after commit (see README "Domain-Driven Design").
- Some actions won't require dependencies via `__construct` and they can use just the `__invoke()` method.
- Write the newest PHP the project runs on (8.5) where it fits: `#[\NoDiscard]` on an action whose result every caller must use (a token shown once, a refusal flag, a pure resolver), `new Foo()->bar()` without wrapping parentheses, `array_find()`/`array_any()`/`array_all()`, `array_first()`/`array_last()`, and the pipe operator `|>` when it reads better than nested calls. Do not rely on `(void)` casts to silence `#[\NoDiscard]`: Rector's dead-code set strips them, so leave the attribute off actions whose result is legitimately ignored.

@boostsnippet('Example action class', 'php')
<?php

declare(strict_types=1);

namespace Modules\Catalog\Actions;

use App\Domain\ValueObjects\Money;
use App\Modules\AuditLog;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Domain\Repositories\ProductRepository;
use Modules\Catalog\Domain\ValueObjects\Sku;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class CreateProduct
{
    public function __construct(private ProductRepository $products)
    {
        //
    }

    public function __invoke(string $name, string $sku, int $priceCents, string $currency, ?string $description): Product
    {
        $product = Product::draft($name, new Sku($sku), new Money($priceCents, $currency), $description);

        return DB::transaction(function () use ($product): Product {
            $this->products->save($product);

            AuditLog::record(log: 'catalog', event: 'created', subject: $product, properties: [
                'attributes' => ['name' => $product->name, 'sku' => $product->sku],
            ]);

            return $product;
        });
    }
}
@endboostsnippet

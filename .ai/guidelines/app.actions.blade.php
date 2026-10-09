# App/Actions guidelines

- This application uses the Action pattern and prefers for much logic to live in reusable and composable Action classes.
- Actions live in `app/Actions` (the shell) or `app-modules/<Module>/Actions` (a module); they are named based on what they do, with no suffix.
- Actions will be called from many different places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- Create dedicated Action classes for business logic with a single `handle()` method.
- Inject dependencies via constructor using private properties.
- Create shell actions with `php artisan make:action "{name}" --no-interaction`. Module actions come from `php artisan make:aggregate <Module> <Aggregate>` or are written by hand in the module's `Actions/` folder (namespace `Modules\<Module>\Actions`), each with a unit test.
- Do every write inside `DB::transaction()` and log it there with `App\Modules\AuditLog::record()`. Compose existing actions rather than duplicating them (bulk deletes run the single-row action through `App\Actions\DeleteEach`).
- Actions own every write. When the feature has business rules, the rule lives on the model (or a value object) and the action locks, calls it, saves through the repository and flushes domain events after commit (see README "Domain-Driven Design").
- Some actions won't require dependencies via `__construct` and they can use just the `handle()` method.

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

    public function handle(string $name, string $sku, int $priceCents, string $currency, ?string $description): Product
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

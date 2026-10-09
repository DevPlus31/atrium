<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use App\Modules\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Catalog\Actions\PublishProduct;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class PublishProductController
{
    #[Authorize('publish', 'product')]
    public function __invoke(Product $product, PublishProduct $action): RedirectResponse
    {
        $action->handle($product);

        Toast::success(__('Product published.'));

        return to_route('admin.products.index');
    }
}

<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Modules\Catalog\Actions\PublishProduct;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class PublishProductController
{
    #[Authorize('publish', 'product')]
    public function __invoke(Product $product, PublishProduct $action): RedirectResponse
    {
        $action->handle($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product published.')]);

        return to_route('admin.products.index');
    }
}

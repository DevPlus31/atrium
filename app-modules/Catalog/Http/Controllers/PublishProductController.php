<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Catalog\Actions\PublishProduct;
use Modules\Catalog\Domain\Exceptions\ProductAlreadyPublished;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class PublishProductController
{
    #[Authorize('publish', 'product')]
    public function __invoke(Product $product, PublishProduct $action): RedirectResponse
    {
        try {
            $action->handle($product);
        } catch (ProductAlreadyPublished $productAlreadyPublished) {
            return back()->with('error', $productAlreadyPublished->getMessage());
        }

        return to_route('admin.products.index')->with('success', __('Product published.'));
    }
}

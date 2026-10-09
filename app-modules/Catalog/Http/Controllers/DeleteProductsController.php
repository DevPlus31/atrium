<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Modules\Catalog\Actions\DeleteProducts;
use Modules\Catalog\Http\Requests\DeleteProductsRequest;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class DeleteProductsController
{
    /**
     * Delete the products selected in the table; rows the user may not delete
     * are left alone and counted in the message.
     */
    #[Authorize('viewAny', Product::class)]
    public function __invoke(DeleteProductsRequest $request, DeleteProducts $action): RedirectResponse
    {
        $products = $request->products();

        abort_if($products->isEmpty(), 403);

        $action->handle($products);

        $skipped = $request->skipped($products->count());
        $message = trans_choice(':count product deleted.|:count products deleted.', $products->count());

        Inertia::flash('toast', ['type' => 'success', 'message' => $skipped === 0
            ? $message
            : $message.' '.trans_choice(':count could not be deleted.|:count could not be deleted.', $skipped)]);

        return to_route('admin.products.index');
    }
}

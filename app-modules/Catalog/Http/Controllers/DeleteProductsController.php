<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use App\Modules\Concerns\DeletesSelection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Modules\Catalog\Actions\DeleteProduct;
use Modules\Catalog\Http\Requests\DeleteProductsRequest;
use Modules\Catalog\Infrastructure\Models\Product;

final readonly class DeleteProductsController
{
    use DeletesSelection;

    /**
     * Delete the products selected in the table; rows the user may not delete
     * are left alone and counted in the message.
     */
    #[Authorize('viewAny', Product::class)]
    public function __invoke(DeleteProductsRequest $request, DeleteProduct $deleteProduct): RedirectResponse
    {
        $this->deleteSelection(
            $request,
            $request->products(),
            $deleteProduct->handle(...),
            static fn (int $count): string => trans_choice(':count product deleted.|:count products deleted.', $count),
        );

        return to_route('admin.products.index');
    }
}

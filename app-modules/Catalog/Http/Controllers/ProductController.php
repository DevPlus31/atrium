<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Actions\CreateProduct;
use Modules\Catalog\Actions\DeleteProduct;
use Modules\Catalog\Actions\UpdateProduct;
use Modules\Catalog\Data\ProductData;
use Modules\Catalog\Http\Requests\StoreProductRequest;
use Modules\Catalog\Http\Requests\UpdateProductRequest;
use Modules\Catalog\Infrastructure\Models\Product;
use Modules\Catalog\Queries\ProductsIndexQuery;
use Spatie\LaravelData\PaginatedDataCollection;

final readonly class ProductController
{
    #[Authorize('viewAny', Product::class)]
    public function index(ProductsIndexQuery $query): Response
    {
        return Inertia::render('catalog::index', [
            'products' => ProductData::collect($query->paginate(), PaginatedDataCollection::class),
            'can' => ['create' => Gate::allows('create', Product::class)],
        ]);
    }

    #[Authorize('create', Product::class)]
    public function create(): Response
    {
        return Inertia::render('catalog::create');
    }

    #[Authorize('create', Product::class)]
    public function store(StoreProductRequest $request, CreateProduct $action): RedirectResponse
    {
        $action->handle(
            name: $request->name(),
            sku: $request->sku(),
            priceCents: $request->priceCents(),
            currency: $request->currency(),
            description: $request->description(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('admin.products.index');
    }

    #[Authorize('update', 'product')]
    public function edit(Product $product): Response
    {
        return Inertia::render('catalog::edit', [
            'product' => ProductData::from($product),
        ]);
    }

    #[Authorize('update', 'product')]
    public function update(UpdateProductRequest $request, Product $product, UpdateProduct $action): RedirectResponse
    {
        $action->handle(
            product: $product,
            name: $request->name(),
            sku: $request->sku(),
            priceCents: $request->priceCents(),
            currency: $request->currency(),
            description: $request->description(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('admin.products.index');
    }

    #[Authorize('delete', 'product')]
    public function destroy(Product $product, DeleteProduct $action): RedirectResponse
    {
        $action->handle($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('admin.products.index');
    }
}

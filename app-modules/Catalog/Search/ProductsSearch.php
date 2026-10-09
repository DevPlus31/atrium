<?php

declare(strict_types=1);

namespace Modules\Catalog\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Infrastructure\Models\Product;

/**
 * Finds products by name or SKU for the command palette.
 */
final readonly class ProductsSearch
{
    /**
     * @return list<SearchResultData>
     */
    public function __invoke(string $term, User $user, int $limit): array
    {
        $found = Product::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereLike('name', '%'.$term.'%')
                ->orWhereLike('sku', '%'.$term.'%'))
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (Product $product): SearchResultData => new SearchResultData(
            title: $product->name,
            description: $product->sku,
            url: $user->can('update', $product)
                ? route('admin.products.edit', $product)
                : route('admin.products.index', ['filter' => ['search' => $product->sku]]),
        ))->all());
    }
}

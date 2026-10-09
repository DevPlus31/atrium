<?php

declare(strict_types=1);

namespace Modules\Catalog\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use App\Modules\TextSearch;
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
        $found = TextSearch::whereLikeAny(Product::query(), ['name', 'sku'], $term)
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (Product $product): SearchResultData => SearchResultData::forRecord(
            viewer: $user,
            record: $product,
            routes: 'admin.products',
            title: $product->name,
            description: $product->sku,
            filter: $product->sku,
        ))->all());
    }
}

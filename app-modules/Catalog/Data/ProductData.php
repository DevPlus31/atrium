<?php

declare(strict_types=1);

namespace Modules\Catalog\Data;

use Illuminate\Support\Facades\Auth;
use Modules\Catalog\Infrastructure\Models\Product;
use Spatie\LaravelData\Data;

final class ProductData extends Data
{
    /**
     * @param  array{update: bool, delete: bool, publish: bool}  $can
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $sku,
        public int $price_cents,
        public string $currency,
        public ?string $description,
        public ?string $published_at,
        public string $created_at,
        public array $can,
    ) {
        //
    }

    public static function fromModel(Product $product): self
    {
        $viewer = Auth::user();

        return new self(
            id: $product->id,
            name: $product->name,
            sku: $product->sku,
            price_cents: $product->price_cents,
            currency: $product->currency,
            description: $product->description,
            published_at: $product->published_at?->toIso8601String(),
            created_at: $product->created_at->toIso8601String(),
            can: [
                'update' => $viewer?->can('update', $product) ?? false,
                'delete' => $viewer?->can('delete', $product) ?? false,
                'publish' => $viewer?->can('publish', $product) ?? false,
            ],
        );
    }
}

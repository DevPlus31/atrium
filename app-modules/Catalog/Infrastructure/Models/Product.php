<?php

declare(strict_types=1);

namespace Modules\Catalog\Infrastructure\Models;

use App\Domain\Concerns\InteractsWithDomainEvents;
use App\Domain\Contracts\RecordsDomainEvents;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Database\Factories\ProductFactory;
use Modules\Catalog\Domain\Events\ProductPublished;
use Modules\Catalog\Domain\Exceptions\ProductAlreadyPublished;
use Modules\Catalog\Domain\ValueObjects\Sku;

/**
 * @property-read string $id
 * @property string $name
 * @property string $sku
 * @property int $price_cents
 * @property string $currency
 * @property string|null $description
 * @property CarbonInterface|null $published_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Product extends Model implements RecordsDomainEvents
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithDomainEvents;

    /**
     * A new, unpublished product. Only valid values get this far: the SKU
     * and the price are value objects.
     */
    public static function draft(string $name, Sku $sku, Money $price, ?string $description): self
    {
        $product = new self;
        $product->revise($name, $sku, $price, $description);

        return $product;
    }

    /**
     * Change the product's details; publishing is a separate step.
     */
    public function revise(string $name, Sku $sku, Money $price, ?string $description): void
    {
        $this->name = $name;
        $this->sku = (string) $sku;
        $this->price_cents = $price->amount;
        $this->currency = $price->currency;
        $this->description = $description;
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Publish the product, recording the domain event for dispatch after commit.
     */
    public function publish(): void
    {
        if ($this->isPublished()) {
            throw ProductAlreadyPublished::withId($this->id);
        }

        $this->published_at = now();

        $this->recordThat(new ProductPublished($this->id, $this->sku));
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'sku' => 'string',
            'price_cents' => 'integer',
            'currency' => 'string',
            'description' => 'string',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Enums\ConsentCategory;
use Spatie\LaravelData\Data;

/**
 * One optional cookie category as the consent banner and preference forms
 * offer it.
 */
final class ConsentCategoryData extends Data
{
    public function __construct(
        public ConsentCategory $value,
        public string $label,
        public string $description,
        public bool $sale_or_sharing,
    ) {
        //
    }

    public static function fromCategory(ConsentCategory $category): self
    {
        return new self(
            value: $category,
            label: $category->label(),
            description: $category->description(),
            sale_or_sharing: $category->isSaleOrSharing(),
        );
    }
}

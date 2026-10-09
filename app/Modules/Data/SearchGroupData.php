<?php

declare(strict_types=1);

namespace App\Modules\Data;

use Spatie\LaravelData\Data;

/**
 * The hits one module found, under its heading.
 */
final class SearchGroupData extends Data
{
    /**
     * @param  list<SearchResultData>  $results
     */
    public function __construct(
        public string $label,
        public ?string $icon,
        public array $results,
    ) {
        //
    }
}

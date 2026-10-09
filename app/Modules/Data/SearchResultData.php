<?php

declare(strict_types=1);

namespace App\Modules\Data;

use Spatie\LaravelData\Data;

/**
 * One hit of the global search: what to show and where it leads.
 */
final class SearchResultData extends Data
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $url,
    ) {
        //
    }
}

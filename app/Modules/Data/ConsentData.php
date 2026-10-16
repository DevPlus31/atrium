<?php

declare(strict_types=1);

namespace App\Modules\Data;

use App\Enums\ConsentCategory;
use Spatie\LaravelData\Data;

/**
 * The visitor's cookie consent, shared with every page (the `consent` prop):
 * the categories on offer, the ones allowed right now, whether the visitor
 * has chosen for the current cookie policy (the banner shows until they
 * have) and whether their browser sends a Global Privacy Control signal.
 */
final class ConsentData extends Data
{
    /**
     * @param  list<ConsentCategoryData>  $categories
     * @param  list<ConsentCategory>  $granted
     */
    public function __construct(
        public array $categories,
        public array $granted,
        public bool $decided,
        public bool $gpc,
    ) {
        //
    }

    /**
     * Whether the visitor allows this category now: the server-side twin of
     * the frontend's useConsent().allows().
     */
    public function allows(ConsentCategory $category): bool
    {
        return in_array($category, $this->granted, true);
    }
}

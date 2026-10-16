<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentCategory;
use App\Modules\ConsentCookie;
use App\Modules\Data\ConsentCategoryData;
use App\Modules\Data\ConsentData;
use Illuminate\Http\Request;
use NoDiscard;

final readonly class ResolveConsent
{
    /**
     * What the visitor allows right now. Nothing optional runs until they
     * choose (GDPR opt-in); a choice made against an older cookie policy
     * counts as none. A Global Privacy Control signal (`Sec-GPC: 1`) is an
     * opt-out of selling and sharing under the CCPA and the other US state
     * laws, so it switches those categories off whatever the visitor chose.
     */
    #[NoDiscard]
    public function __invoke(Request $request): ConsentData
    {
        $offered = ConsentCategory::offered();
        $gpc = $request->header('Sec-GPC') === '1';
        $choice = ConsentCookie::fromRequest($request);
        $choice = $choice?->isCurrent() === true ? $choice : null;

        return new ConsentData(
            categories: array_map(ConsentCategoryData::fromCategory(...), $offered),
            granted: array_values(array_filter(
                $offered,
                static fn (ConsentCategory $category): bool => $choice?->allows($category) === true
                    && (! $gpc || ! $category->isSaleOrSharing()),
            )),
            decided: $offered === [] || $choice instanceof ConsentCookie,
            gpc: $gpc,
        );
    }
}

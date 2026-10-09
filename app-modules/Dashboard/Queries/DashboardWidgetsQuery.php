<?php

declare(strict_types=1);

namespace Modules\Dashboard\Queries;

use App\Enums\Area;
use App\Models\User;
use App\Modules\WidgetRegistry;
use Inertia\DeferProp;
use Inertia\Inertia;
use Modules\Dashboard\Data\WidgetDescriptorData;
use Spatie\LaravelData\Data;

final readonly class DashboardWidgetsQuery
{
    public function __construct(private WidgetRegistry $registry)
    {
        //
    }

    /**
     * The page props of a dashboard: the widget descriptors of the given
     * area plus one deferred prop per permitted widget, keyed by its
     * descriptor prop, so the page renders skeletons first and each widget
     * loads after the first paint.
     *
     * @return array<string, list<WidgetDescriptorData>|DeferProp>
     */
    public function for(User $user, Area $area): array
    {
        $descriptors = array_map(
            WidgetDescriptorData::fromRegistry(...),
            $this->registry->descriptorsFor($user, $area),
        );

        $props = ['widgets' => $descriptors];

        foreach ($descriptors as $descriptor) {
            $props[$descriptor->prop] = Inertia::defer(
                fn (): ?Data => $this->registry->resolveFor($user, $descriptor->key, $area),
                'widgets',
            );
        }

        return $props;
    }
}

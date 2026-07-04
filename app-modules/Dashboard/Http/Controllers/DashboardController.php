<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Models\User;
use App\Modules\WidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\DeferProp;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Dashboard\Data\WidgetDescriptorData;
use Spatie\LaravelData\Data;

final readonly class DashboardController
{
    #[Authorize('dashboard.view')]
    public function index(Request $request, WidgetRegistry $registry): Response
    {
        $descriptors = $registry->descriptorsFor($user = $this->user($request));

        return Inertia::render('dashboard::index', [
            'widgets' => WidgetDescriptorData::collect($descriptors),
            ...$this->deferredWidgets($registry, $user, $descriptors),
        ]);
    }

    /**
     * One deferred prop per permitted widget, keyed `widget:<key>`.
     *
     * @param  list<array{key: string, sort: int}>  $descriptors
     * @return array<string, DeferProp>
     */
    private function deferredWidgets(WidgetRegistry $registry, User $user, array $descriptors): array
    {
        $props = [];

        foreach ($descriptors as $descriptor) {
            $props['widget:'.$descriptor['key']] = Inertia::defer(
                static fn (): ?Data => $registry->resolveFor($user, $descriptor['key']),
                'widgets',
            );
        }

        return $props;
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}

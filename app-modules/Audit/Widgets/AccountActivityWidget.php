<?php

declare(strict_types=1);

namespace Modules\Audit\Widgets;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Modules\Audit\Data\AccountActivityWidgetData;
use Spatie\Activitylog\Models\Activity;

final readonly class AccountActivityWidget
{
    private const int LIMIT = 5;

    /**
     * What others did to the viewer's account (edits by an administrator,
     * impersonations), newest first, so members can see who acted on it.
     */
    public function __invoke(User $user): AccountActivityWidgetData
    {
        $activities = Activity::query()
            ->whereMorphedTo('subject', $user)
            ->where(static fn (Builder $query): Builder => $query->whereNull('causer_id')->orWhereNotMorphedTo('causer', $user))
            ->with('causer')
            ->latest()
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        return new AccountActivityWidgetData(
            entries: array_values($activities->map(static fn (Activity $activity): array => [
                'id' => (string) $activity->id,
                'event' => $activity->event,
                'causer' => $activity->causer instanceof User ? $activity->causer->name : null,
                'created_at' => (string) $activity->created_at?->toIso8601String(),
            ])->all()),
        );
    }
}

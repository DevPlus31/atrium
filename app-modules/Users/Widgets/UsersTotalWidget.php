<?php

declare(strict_types=1);

namespace Modules\Users\Widgets;

use App\Models\User;
use Modules\Users\Data\UsersTotalWidgetData;

final readonly class UsersTotalWidget
{
    private const int DAYS = 14;

    public function __invoke(): UsersTotalWidgetData
    {
        $start = today()->subDays(self::DAYS - 1);

        /** @var array<string, int|string> $counts */
        $counts = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, count(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day')
            ->all();

        return new UsersTotalWidgetData(
            total: User::query()->count(),
            series: array_map(static function (int $offset) use ($start, $counts): array {
                $date = $start->copy()->addDays($offset)->toDateString();

                return ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
            }, range(0, self::DAYS - 1)),
        );
    }
}

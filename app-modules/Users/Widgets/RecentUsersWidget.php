<?php

declare(strict_types=1);

namespace Modules\Users\Widgets;

use App\Models\User;
use Modules\Users\Data\RecentUsersWidgetData;

final readonly class RecentUsersWidget
{
    public function __invoke(): RecentUsersWidgetData
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->latest()
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return new RecentUsersWidgetData(
            users: array_values($users->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->toIso8601String(),
            ])->all()),
        );
    }
}

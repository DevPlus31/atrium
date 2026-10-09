<?php

declare(strict_types=1);

namespace Modules\Users\Widgets;

use App\Models\User;
use Modules\Users\Data\RecentUsersWidgetData;

final readonly class RecentUsersWidget
{
    private const int LIMIT = 5;

    public function __invoke(): RecentUsersWidgetData
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->with('media')
            ->latest()
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        return new RecentUsersWidgetData(
            users: array_values($users->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatarUrl(),
                'created_at' => $user->created_at->toIso8601String(),
            ])->all()),
        );
    }
}

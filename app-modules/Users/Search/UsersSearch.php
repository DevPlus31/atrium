<?php

declare(strict_types=1);

namespace Modules\Users\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use Illuminate\Database\Eloquent\Builder;

/**
 * Finds users by name or email for the command palette.
 */
final readonly class UsersSearch
{
    /**
     * @return list<SearchResultData>
     */
    public function __invoke(string $term, User $user, int $limit): array
    {
        $found = User::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereLike('name', '%'.$term.'%')
                ->orWhereLike('email', '%'.$term.'%'))
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (User $match): SearchResultData => new SearchResultData(
            title: $match->name,
            description: $match->email,
            url: $user->can('update', $match)
                ? route('admin.users.edit', $match)
                : route('admin.users.index', ['filter' => ['search' => $match->email]]),
        ))->all());
    }
}

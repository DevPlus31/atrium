<?php

declare(strict_types=1);

namespace Modules\Users\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use App\Modules\TextSearch;

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
        $found = TextSearch::whereLikeAny(User::query(), ['name', 'email'], $term)
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (User $match): SearchResultData => SearchResultData::forRecord(
            viewer: $user,
            record: $match,
            routes: 'admin.users',
            title: $match->name,
            description: $match->email,
            filter: $match->email,
        ))->all());
    }
}

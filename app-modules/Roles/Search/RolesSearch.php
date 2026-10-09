<?php

declare(strict_types=1);

namespace Modules\Roles\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use App\Modules\TextSearch;
use Spatie\Permission\Models\Role;

/**
 * Finds roles by name for the command palette.
 */
final readonly class RolesSearch
{
    /**
     * @return list<SearchResultData>
     */
    public function __invoke(string $term, User $user, int $limit): array
    {
        $found = TextSearch::whereLikeAny(Role::query(), ['name'], $term)
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (Role $role): SearchResultData => SearchResultData::forRecord(
            viewer: $user,
            record: $role,
            routes: 'admin.roles',
            title: $role->name,
            description: null,
            filter: $role->name,
        ))->all());
    }
}
